<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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
  public $_models = array('User', 'Assetstore');
  public $_daos = array('Assetstore');
  public $_components = array('Utility');
  public $_forms = array('Install');

  /**
   * @method init()
   */
  function init()
    {
    if(file_exists(LOCAL_CONFIGS_PATH . '/database.local.ini') &&
       file_exists(LOCAL_CONFIGS_PATH . '/application.local.ini') &&
       Zend_Controller_Front::getInstance()->getRequest()->getActionName() != 'step3')
      {
      throw new Zend_Exception('Midas is already installed.');
      }
    }

  /**
   * @method indexAction()
   */
  function indexAction()
    {
    if(file_exists(LOCAL_CONFIGS_PATH . '/database.local.ini'))
      {
      $this->_redirect('/install/step3');
      }
    $this->view->header = 'Step 1: Server Configuration';
    // Check PHP extensions / functions
    $phpextensions = array (
      'simplexml'  => array(false, ''),
    );
    $this->view->phpextension_missing = $this->Component->Utility->checkPhpExtensions($phpextensions);
    $this->view->writable = is_writable(LOCAL_CONFIGS_PATH);
    $this->view->basePath = BASE_PATH;
    if(!empty($_POST) && $this->view->writable)
      {
      $this->_redirect('/install/step2');
      }
    }

  /**
   * @method step2Action()
   */
  function step2Action()
    {
    if(file_exists(LOCAL_CONFIGS_PATH . '/database.local.ini'))
      {
      $this->_redirect('/install/step3');
      }
    $this->view->header = 'Step 2: Database Configuration';

    $databases = array('mysql', 'pgsql');
    $this->view->databaseType = array();
    foreach($databases as $database)
      {
      if(!extension_loaded($database) || !file_exists(BASE_PATH . '/core/database/' . $database))
        {
        unset($database);
        }
      else
        {
        $form = $this->Form->Install->createDBForm($database);
        $port = $form->getElement('port');
        $username = $form->getElement('username');
        switch($database)
          {
          case 'mysql':
            $port->setValue('3306');
            $username->setValue('root');
            break;
          case 'pgsql':
            $port->setValue('5432');
            $username->setValue('postgres');
            break;
          default:
            break;
          }
        $this->view->databaseType[$database] = $this->getFormAsArray($form);
        }
      }

    $this->view->writable = is_writable(BASE_PATH);
    $this->view->basePath = BASE_PATH;

    if($this->_request->isPost())
      {
      $type = $this->_getParam('type');
      $form = $this->Form->Install->createDBForm($type);
      if($form->isValid($this->getRequest()->getPost()))
        {
        $configDatabase = parse_ini_file(CORE_CONFIGS_PATH . '/database.ini', true);
        require_once BASE_PATH . '/core/controllers/components/UpgradeComponent.php';
        $upgradeComponent = new UpgradeComponent();
        $upgradeComponent->dir = BASE_PATH . '/core/database/'.$type;
        $upgradeComponent->init = true;
        $sqlFile = $upgradeComponent->getNewestVersion(true);
        $sqlFile = BASE_PATH . '/core/database/'.$type.'/'.$sqlFile.'.sql';

        if(!isset($sqlFile) || !file_exists($sqlFile))
          {
          throw new Zend_Exception('Unable to find sql file');
          }

        $dbtype = 'PDO_' . strtoupper($type);
        $version = str_replace('.sql', '', basename($sqlFile));

        $configDatabase['production']['database.type'] = 'pdo';
        $configDatabase['production']['database.adapter'] = $dbtype;
        $configDatabase['production']['database.params.host'] = $form->getValue('host');
        $configDatabase['production']['database.params.port'] = $form->getValue('port');
        $configDatabase['production']['database.params.unix_socket'] = $form->getValue('unix_socket');
        $configDatabase['production']['database.params.dbname'] = $form->getValue('dbname');
        $configDatabase['production']['database.params.username'] = $form->getValue('username');
        $configDatabase['production']['database.params.password'] = $form->getValue('password');
        $configDatabase['production']['version'] = $version;

        $configDatabase['development']['database.type'] = 'pdo';
        $configDatabase['development']['database.adapter'] = $dbtype;
        $configDatabase['development']['database.params.host'] = $form->getValue('host');
        $configDatabase['development']['database.params.port'] = $form->getValue('port');
        $configDatabase['development']['database.params.unix_socket'] = $form->getValue('unix_socket');
        $configDatabase['development']['database.params.dbname'] = $form->getValue('dbname');
        $configDatabase['development']['database.params.username'] = $form->getValue('username');
        $configDatabase['development']['database.params.password'] = $form->getValue('password');
        $configDatabase['development']['version'] = $version;

        $driverOptions = array();
        $params = array(
          'dbname' => $form->getValue('dbname'),
          'username' => $form->getValue('username'),
          'password' => $form->getValue('password'),
          'driver_options' => $driverOptions);
        $unixsocket = $form->getValue('unix_socket');
        if($unixsocket)
          {
          $params['unix_socket'] = $unixsocket;
          }
        else
          {
          $params['host'] = $form->getValue('host');
          $params['port'] = $form->getValue('port');
          }

        $db = Zend_Db::factory($dbtype, $params);
        $this->Component->Utility->run_sql_from_file($db, $sqlFile);

        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('dbAdapter', $db);

        $this->Component->Utility->createInitFile(LOCAL_CONFIGS_PATH . '/database.local.ini', $configDatabase);

        // Must generate and store our password salt before we create our first user
        $configApplication = parse_ini_file(CORE_CONFIGS_PATH . '/application.ini', true);
        $configApplication['global']['password.prefix'] = UtilityComponent::generateRandomString(32);

        // Verify whether the user wants to use gravatars or not
        $configApplication['global']['gravatar'] = $form->getValue('gravatar');

        // Save the new config
        $this->Component->Utility->createInitFile(LOCAL_CONFIGS_PATH . '/application.local.ini', $configApplication);
        $configGlobal = new Zend_Config_Ini(LOCAL_CONFIGS_PATH . '/application.local.ini', 'global', true);
        Zend_Registry::set('configGlobal', $configGlobal);

        require_once BASE_PATH . '/core/controllers/components/UpgradeComponent.php';
        $upgradeComponent = new UpgradeComponent();
        $db = Zend_Registry::get('dbAdapter');

        $upgradeComponent->initUpgrade('core', $db, $dbtype);
        $upgradeComponent->upgrade(str_replace('.sql', '', basename($sqlFile)));

        session_start();
        $userModel = MidasLoader::loadModel('User');
        $this->userSession->Dao = $userModel->createUser($form->getValue('email'), $form->getValue('userpassword1'),
                                $form->getValue('firstname'), $form->getValue('lastname'), 1);

        // create default assetstore
        $assetstoreDao = new AssetstoreDao();
        $assetstoreDao->setName('Local');
        $assetstoreDao->setPath($this->getDataDirectory('assetstore'));
        $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
        $this->Assetstore = new AssetstoreModel(); //reset Database adapter
        $this->Assetstore->save($assetstoreDao);
        $this->_redirect('/install/step3');
        }
      }
    }

  /**
   * @method step3Action()
   */
  function step3Action()
    {
    $this->requireAdminPrivileges();
    if(!file_exists(LOCAL_CONFIGS_PATH . '/database.local.ini'))
      {
      $this->_redirect('/install/index');
      }
    $this->view->header = 'Step 3: Midas Server Configuration';
    $userDao = $this->userSession->Dao;
    if(!isset($userDao) || !$userDao->isAdmin())
      {
      unlink(LOCAL_CONFIGS_PATH . '/database.local.ini');
      $this->_redirect('/install/index');
      }
    $applicationConfig = parse_ini_file(LOCAL_CONFIGS_PATH . '/application.local.ini', true);

    $form = $this->Form->Install->createConfigForm();
    $formArray = $this->getFormAsArray($form);
    $formArray['name']->setValue($applicationConfig['global']['application.name']);
    $formArray['keywords']->setValue($applicationConfig['global']['application.keywords']);
    $formArray['environment']->setValue($applicationConfig['global']['environment']);
    $formArray['lang']->setValue($applicationConfig['global']['application.lang']);
    $formArray['description']->setValue($applicationConfig['global']['application.description']);
    $formArray['smartoptimizer']->setValue($applicationConfig['global']['smartoptimizer']);
    $formArray['timezone']->setValue($applicationConfig['global']['default.timezone']);

    $assetstrores = $this->Assetstore->getAll();

    $this->view->form = $formArray;
    $this->view->databaseType = Zend_Registry::get('configDatabase')->database->adapter;

    if($this->_request->isPost() && $form->isValid($this->getRequest()->getPost()))
      {
      $allModules = $this->Component->Utility->getAllModules();
      foreach($allModules as $key => $module)
        {
        $configLocal = LOCAL_CONFIGS_PATH.'/'.$key.'.local.ini';
        if(file_exists($configLocal))
          {
          unlink($configLocal);
          }
        }

      $applicationConfig['global']['application.name'] = $form->getValue('name');
      $applicationConfig['global']['application.description'] = $form->getValue('description');
      $applicationConfig['global']['application.keywords'] = $form->getValue('keywords');
      $applicationConfig['global']['application.lang'] = $form->getValue('lang');
      $applicationConfig['global']['environment'] = $form->getValue('environment');
      $applicationConfig['global']['defaultassetstore.id'] = $assetstrores[0]->getKey();
      $applicationConfig['global']['smartoptimizer'] = $form->getValue('smartoptimizer');
      $applicationConfig['global']['default.timezone'] = $form->getValue('timezone');

      $this->Component->Utility->createInitFile(LOCAL_CONFIGS_PATH . '/application.local.ini', $applicationConfig);
      $this->_redirect('/admin#tabs-modules');
      }
    }

  /** AJAX function which tests connectivity to a database */
  public function testconnectionAction()
    {
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    try
      {
      $driverOptions = array();
      $params = array(
        'dbname' => $this->_getParam('dbname'),
        'username' => $this->_getParam('username'),
        'password' => $this->_getParam('password'),
        'driver_options' => $driverOptions);
      $unixsocket = $this->_getParam('unix_socket');
      if($unixsocket)
        {
        $params['unix_socket'] = $this->_getParam('unix_socket');
        }
      else
        {
        $params['host'] = $this->_getParam('host');
        $params['port'] = $this->_getParam('port');
        }
      $db = Zend_Db::factory('PDO_' . strtoupper($this->_getParam('type')), $params);
      $tables = $db->listTables();
      if(count($tables) > 0)
        {
        $return = array(false, 'The database is not empty');
        }
      else
        {
        $return = array(true, 'The database is reachable');
        }
      $db->closeConnection();
      }
    catch(Zend_Exception $exception)
      {
      $return = array(false, 'Could not connect to the database');
      }
    echo JsonComponent::encode($return);
    }
  }
