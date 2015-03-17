<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/**
 *  InstallController
 */
class InstallController extends AppController
{
    public $_models = array('User', 'Assetstore', 'Setting');
    public $_daos = array('Assetstore');
    public $_components = array('Random', 'Utility');
    public $_forms = array('Install');

    /**
     */
    public function init()
    {
        if (file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini') && file_exists(
                LOCAL_CONFIGS_PATH.'/application.local.ini'
            ) && Zend_Controller_Front::getInstance()->getRequest()->getActionName() != 'step3'
        ) {
            throw new Zend_Exception('Midas is already installed.');
        }
    }

    /**
     */
    public function indexAction()
    {
        if (file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')) {
            $this->redirect('/install/step3');
        }
        $this->view->header = 'Step 1: Server Configuration';
        // Check PHP extensions / functions
        $phpextensions = array('simplexml' => array(false, ''));
        $this->view->phpextension_missing = $this->Component->Utility->checkPhpExtensions($phpextensions);
        $this->view->writable = is_writable(LOCAL_CONFIGS_PATH);
        $this->view->basePath = BASE_PATH;
        if (!empty($_POST) && $this->view->writable) {
            $this->redirect('/install/step2');
        }
    }

    /**
     */
    public function step2Action()
    {
        if (file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')) {
            $this->redirect('/install/step3');
        }
        $this->view->header = 'Step 2: Database Configuration';

        $databases = array('mysql', 'pgsql', 'sqlite');
        $this->view->databaseType = array();
        foreach ($databases as $database) {
            if (!extension_loaded('pdo_'.$database) || !file_exists(BASE_PATH.'/core/database/'.$database)
            ) {
                unset($database);
            } else {
                $form = $this->Form->Install->createDBForm();
                $host = $form->getElement('host');
                $port = $form->getElement('port');
                $username = $form->getElement('username');
                switch ($database) {
                    case 'mysql':
                        $port->setValue('3306');
                        $username->setValue('root');
                        break;
                    case 'pgsql':
                        $port->setValue('5432');
                        $username->setValue('postgres');
                        break;
                    case 'sqlite':
                        $host->setValue('');
                        $port->setValue('');
                        $username->setValue('');
                        break;
                    default:
                        break;
                }
                $this->view->databaseType[$database] = $this->getFormAsArray($form);
            }
        }

        $this->view->basePath = BASE_PATH;

        if ($this->_request->isPost()) {
            $type = $this->getParam('type');
            $form = $this->Form->Install->createDBForm();
            if ($form->isValid($this->getRequest()->getPost())) {
                require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';

                $upgradeComponent = new UpgradeComponent();
                $upgradeComponent->dir = BASE_PATH.'/core/database/'.$type;
                $upgradeComponent->init = true;
                $sqlFile = $upgradeComponent->getNewestVersion(true);
                $sqlFile = BASE_PATH.'/core/database/'.$type.'/'.$sqlFile.'.sql';

                if (!isset($sqlFile) || !file_exists($sqlFile)) {
                    throw new Zend_Exception('Unable to find sql file');
                }

                $dbtype = 'PDO_'.strtoupper($type);
                $version = str_replace('.sql', '', basename($sqlFile));

                $options = array('allowModifications' => true);
                $databaseConfig = new Zend_Config_Ini(CORE_CONFIGS_PATH.'/database.ini', null, $options);

                $databaseConfig->production->database->adapter = $dbtype;
                $databaseConfig->production->database->params->host = $form->getValue('host');
                $databaseConfig->production->database->params->port = $form->getValue('port');
                $databaseConfig->production->database->params->unix_socket = $form->getValue('unix_socket');
                $databaseConfig->production->database->params->dbname = $form->getValue('dbname');
                $databaseConfig->production->database->params->username = $form->getValue('username');
                $databaseConfig->production->database->params->password = $form->getValue('password');
                $databaseConfig->production->version = $version;

                $databaseConfig->development->database->adapter = $dbtype;
                $databaseConfig->development->database->params->host = $form->getValue('host');
                $databaseConfig->development->database->params->port = $form->getValue('port');
                $databaseConfig->development->database->params->unix_socket = $form->getValue('unix_socket');
                $databaseConfig->development->database->params->dbname = $form->getValue('dbname');
                $databaseConfig->development->database->params->username = $form->getValue('username');
                $databaseConfig->development->database->params->password = $form->getValue('password');
                $databaseConfig->development->version = $version;

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($databaseConfig);
                $writer->setFilename(LOCAL_CONFIGS_PATH.'/database.local.ini');
                $writer->write();

                $driverOptions = array();
                $params = array(
                    'dbname' => $form->getValue('dbname'),
                    'driver_options' => $driverOptions,
                );
                if ($dbtype != 'PDO_SQLITE') {
                    $params['username'] = $form->getValue('username');
                    $params['password'] = $form->getValue('password');
                    $unixsocket = $form->getValue('unix_socket');
                    if ($unixsocket) {
                        $params['unix_socket'] = $unixsocket;
                    } else {
                        $params['host'] = $form->getValue('host');
                        $params['port'] = $form->getValue('port');
                    }
                }

                $db = Zend_Db::factory($dbtype, $params);
                Zend_Db_Table::setDefaultAdapter($db);
                Zend_Registry::set('dbAdapter', $db);

                $this->Component->Utility->run_sql_from_file($db, $sqlFile);

                // Must generate and store our password salt before we create our first user
                $options = array('allowModifications' => true);
                $applicationConfig = new Zend_Config_Ini(CORE_CONFIGS_PATH.'/application.ini', null, $options);

                $prefix = $this->Component->Random->generateString(32);
                $applicationConfig->global->password->prefix = $prefix;
                $applicationConfig->global->gravatar = $form->getValue('gravatar');

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($applicationConfig);
                $writer->setFilename(LOCAL_CONFIGS_PATH.'/application.local.ini');
                $writer->write();

                $configGlobal = new Zend_Config_Ini(LOCAL_CONFIGS_PATH.'/application.local.ini', 'global');
                Zend_Registry::set('configGlobal', $configGlobal);

                $configDatabase = new Zend_Config_Ini(LOCAL_CONFIGS_PATH.'/database.local.ini', 'production');
                Zend_Registry::set('configDatabase', $configDatabase);

                require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
                $upgradeComponent = new UpgradeComponent();

                $upgradeComponent->initUpgrade('core', $db, $dbtype);
                $upgradeComponent->upgrade(str_replace('.sql', '', basename($sqlFile)));

                session_start();
                require_once BASE_PATH.'/core/models/pdo/UserModel.php';
                $userModel = new UserModel();
                $this->userSession->Dao = $userModel->createUser(
                    $form->getValue('email'),
                    $form->getValue('userpassword1'),
                    $form->getValue('firstname'),
                    $form->getValue('lastname'),
                    1
                );

                // create default assetstore
                $assetstoreDao = new AssetstoreDao();
                $assetstoreDao->setName('Local');
                $assetstoreDao->setPath($this->getDataDirectory('assetstore'));
                $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
                $this->Assetstore = new AssetstoreModel(); // reset Database adapter
                $this->Assetstore->save($assetstoreDao);
                $this->redirect('/install/step3');
            }
        }
    }

    /**
     */
    public function step3Action()
    {
        $this->requireAdminPrivileges();

        if (!file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')) {
            $this->redirect('/install/index');
        }

        $this->view->header = 'Step 3: Midas Server Configuration';
        $userDao = $this->userSession->Dao;
        if (!isset($userDao) || !$userDao->isAdmin()) {
            unlink(LOCAL_CONFIGS_PATH.'/database.local.ini');
            $this->redirect('/install/index');
        }

        $options = array('allowModifications' => true);
        $config = new Zend_Config_Ini(APPLICATION_CONFIG, null, $options);

        $form = $this->Form->Install->createConfigForm();
        $formArray = $this->getFormAsArray($form);
        $formArray['description']->setValue($config->global->application->description);
        $formArray['lang']->setValue($config->global->application->lang);
        $formArray['name']->setValue($config->global->application->name);
        $formArray['timezone']->setValue($config->global->default->timezone);
        $this->view->form = $formArray;
        $this->view->databaseType = Zend_Registry::get('configDatabase')->database->adapter;
        $assetstores = $this->Assetstore->getAll();

        if ($this->_request->isPost() && $form->isValid($this->getRequest()->getPost())
        ) {
            $allModules = $this->Component->Utility->getAllModules();
            foreach ($allModules as $key => $module) {
                $configLocal = LOCAL_CONFIGS_PATH.'/'.$key.'.local.ini';
                if (file_exists($configLocal)) {
                    unlink($configLocal);
                }
            }

            $config->global->application->description = $form->getValue('description');
            $config->global->application->lang = $form->getValue('lang');
            $config->global->application->name = $form->getValue('name');
            $config->global->default->timezone = $form->getValue('timezone');
            $config->global->environment = 'production';

            $writer = new Zend_Config_Writer_Ini();
            $writer->setConfig($config);
            $writer->setFilename(APPLICATION_CONFIG);
            $writer->write();

            $this->Setting->setConfig('default_assetstore', $assetstores[0]->getKey());

            $this->redirect('/admin#tabs-modules');
        }
    }

    /** AJAX function which tests connectivity to a database */
    public function testconnectionAction()
    {
        $this->requireAjaxRequest();
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $dbtype = 'PDO_'.strtoupper($this->getParam('type'));
        if ($dbtype === 'PDO_SQLITE') {
            $return = array(true, 'The database is reachable');
        } else {
            try {
                $driverOptions = array();
                $params = array(
                    'dbname' => $this->getParam('dbname'),
                    'username' => $this->getParam('username'),
                    'password' => $this->getParam('password'),
                    'driver_options' => $driverOptions,
                );
                $unixsocket = $this->getParam('unix_socket');
                if ($unixsocket) {
                    $params['unix_socket'] = $this->getParam('unix_socket');
                } else {
                    $params['host'] = $this->getParam('host');
                    $params['port'] = $this->getParam('port');
                }
                $db = Zend_Db::factory('PDO_'.strtoupper($this->getParam('type')), $params);
                $tables = $db->listTables();
                if (count($tables) > 0) {
                    $return = array(false, 'The database is not empty');
                } else {
                    $return = array(true, 'The database is reachable');
                }
                $db->closeConnection();
            } catch (Zend_Exception $exception) {
                $return = array(false, 'Could not connect to the database');
            }
        }
        echo JsonComponent::encode($return);
    }
}
