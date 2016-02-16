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

require_once dirname(__FILE__).'/TestsBootstrap.php';
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/**
 * @property ActivedownloadModel $Activedownload
 * @property AssetstoreModel $Assetstore
 * @property BitstreamModel $Bitstream
 * @property CommunityModel $Community
 * @property CommunityInvitationModel $CommunityInvitation
 * @property object $Component
 * @property FeedModel $Feed
 * @property FeedpolicygroupModel $Feedpolicygroup
 * @property FeedpolicyuserModel $Feedpolicyuser
 * @property FolderModel $Folder
 * @property FolderpolicygroupModel $Folderpolicygroup
 * @property FolderpolicyuserModel $Folderpolicyuser
 * @property object $Form
 * @property GroupModel $Group
 * @property ItemModel $Item
 * @property ItempolicygroupModel $Itempolicygroup
 * @property ItempolicyuserModel $Itempolicyuser
 * @property ItemRevisionModel $ItemRevision
 * @property LicenseModel $License
 * @property MetadataModel $Metadata
 * @property ModuleModel $Module
 * @property NewUserInvitationModel $NewUserInvitation
 * @property PendingUserModel $PendingUser
 * @property ProgressModel $Progress
 * @property SettingModel $Setting
 * @property TokenModel $Token
 * @property UserModel $User
 * @property UserapiModel $Userapi;
 */
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    protected $application;

    /** @var array */
    protected $params = array();

    /**
     * Get the temporary directory.
     *
     * @return string
     */
    protected function getTempDirectory()
    {
        return UtilityComponent::getTempDirectory();
    }

    /** Setup. */
    public function setUp()
    {
        $this->bootstrap = array($this, 'appBootstrap');
        $this->loadElements();

        parent::setUp();
    }

    /** Tear down. */
    public function tearDown()
    {
        Zend_Controller_Front::getInstance()->resetInstance();
        $this->resetAll();
        parent::tearDown();
    }

    /**
     * Get the response body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->response->outputBody();
    }

    /**
     * Get the data set.
     *
     * @param string $name
     * @param null|string $module
     * @return PHPUnit_Extensions_Database_DataSet_AbstractDataSet
     * @throws Zend_Exception
     */
    protected function getDataSet($name = 'default', $module = null)
    {
        $path = BASE_PATH.'/core/tests/databaseDataset/'.$name.'.xml';
        if (isset($module) && !empty($module)) {
            $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$name.'.xml';
        }
        $xmlDataSet = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
        $replacementDataSet = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($xmlDataSet);
        $configCore = new Zend_Config_Ini(CORE_CONFIG, 'global', true);
        Zend_Registry::set('configCore', $configCore);
        $coreVersion = UtilityComponent::getLatestModuleVersion('core');
        $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $coreVersion, $matches);
        if ($result !== 1) {
            throw new Zend_Exception('Invalid core version string.');
        }
        $replacementDataSet->addFullReplacement('##CORE_MAJOR_VERSION##', $matches[1]);
        $replacementDataSet->addFullReplacement('##CORE_MINOR_VERSION##', $matches[2]);
        $replacementDataSet->addFullReplacement('##CORE_PATCH_VERSION##', $matches[3]);

        return $replacementDataSet;
    }

    /**
     * Load data.
     *
     * @param string $modelName
     * @param null|string $file
     * @param string $module
     * @return false|array|mixed
     */
    protected function loadData($modelName, $file = null, $module = '')
    {
        $model = MidasLoader::loadModel($modelName, $module);
        if ($file == null) {
            $file = strtolower($modelName);
        }

        $data = $this->getDataSet($file, $module);
        $dataUsers = $data->getTable($model->getName());
        $key = array();
        for ($i = 0; $i < $dataUsers->getRowCount(); ++$i) {
            $key[] = $dataUsers->getValue($i, $model->getKey());
        }

        return $model->load($key);
    }

    /** Initialize modules. */
    private function _initModule()
    {
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers');

        require_once BASE_PATH.'/core/ApiController.php';
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers/api', 'rest');

        $router = $frontController->getRouter();
        $router->addRoute('api-core', new Zend_Rest_Route($frontController, array(), array('rest')));

        $enabledModules = array();

        if (isset($this->enabledModules)) {
            $enabledModules = $this->enabledModules;
        } elseif (isset($_POST['enabledModules'])) {
            $enabledModules = explode(';', $_POST['enabledModules']);
        } elseif (isset($_GET['enabledModules'])) {
            $enabledModules = explode(';', $_GET['enabledModules']);
        }

        /** @var UtilityComponent $utilityComponent */
        $utilityComponent = MidasLoader::loadComponent('Utility');

        /** @var ModuleModel $moduleModel */
        $moduleModel = MidasLoader::loadModel('Module');

        $enabledApiModules = array();

        /** @var string $enabledModule */
        foreach ($enabledModules as $enabledModule) {
            $frontController->addControllerDirectory(BASE_PATH.'/modules/'.$enabledModule.'/controllers', $enabledModule);

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/constant/module.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/constant/module.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/AppController.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/AppController.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/models/AppDao.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/models/AppDao.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/models/AppModel.php')) {
                require_once BASE_PATH.'/modules/'.$enabledModule.'/models/AppModel.php';
            }

            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/controllers/api')) {
                $frontController->addControllerDirectory(BASE_PATH.'/modules/'.$enabledModule.'/controllers/api', 'api'.$enabledModule);
                $enabledApiModules[] = $enabledModule;
            }

            $router->addRoute($enabledModule.'-1', new Zend_Controller_Router_Route($enabledModule.'/:controller/:action/*', array('module' => $enabledModule)));
            $router->addRoute($enabledModule.'-2', new Zend_Controller_Router_Route($enabledModule.'/:controller/', array('module' => $enabledModule, 'action' => 'index')));
            $router->addRoute($enabledModule.'-3', new Zend_Controller_Router_Route($enabledModule.'/', array('module' => $enabledModule, 'controller' => 'index', 'action' => 'index')));

            $utilityComponent->installModule($enabledModule);
            $moduleDao = $moduleModel->getByName($enabledModule);
            $moduleDao->setEnabled(1);
            $moduleModel->save($moduleDao);
        }

        Zend_Registry::set('modulesEnable', $enabledModules);
        Zend_Registry::set('modulesHaveApi', $enabledApiModules);
    }

    /** Register plugins and helpers for the REST controller. */
    protected function _initREST()
    {
        $frontController = Zend_Controller_Front::getInstance();

        // register the RestHandler plugin
        $frontController->registerPlugin(new REST_Controller_Plugin_RestHandler($frontController));

        // add REST contextSwitch helper
        $contextSwitch = new REST_Controller_Action_Helper_ContextSwitch();
        Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

        // add restContexts helper
        $restContexts = new REST_Controller_Action_Helper_RestContexts();
        Zend_Controller_Action_HelperBroker::addHelper($restContexts);
    }

    /**
     * Fetch the given page.
     *
     * @param string $url URL of the page
     * @param null|UserDao $userDao user with which to log in
     * @param bool $withException if true, an exception is expected
     * @param bool $assertNot404 if true, a status code that is not 404 is expected
     * @deprecated
     */
    public function dispatchUrI($url, $userDao = null, $withException = false, $assertNot404 = true)
    {
        $this->dispatchUrl($url, $userDao, $withException, $assertNot404);
    }

    /**
     * Fetch the given page.
     *
     * @param string $url URL of the page
     * @param null|UserDao $userDao user with which to log in
     * @param bool $withException if true, an exception is expected
     * @param bool $assertNot404 if true, a status code that is not 404 is expected
     */
    public function dispatchUrl($url, $userDao = null, $withException = false, $assertNot404 = true)
    {
        if ($userDao != null) {
            $this->params['testingUserId'] = $userDao->getKey();
        }

        if (isset($this->enabledModules) && !empty($this->enabledModules)) {
            $this->params['enabledModules'] = implode(';', $this->enabledModules);
        } else {
            unset($this->params['enabledModules']);
        }

        if ($this->request->isPost()) {
            $this->request->setPost($this->params);
        } else {
            $this->request->setQuery($this->params);
        }
        $this->dispatch($url);
        if ($assertNot404) {
            $this->assertNotResponseCode('404');
        }
        if ($this->request->getControllerName() == 'error') {
            if ($withException) {
                return;
            }
            $error = $this->request->getParam('error_handler');
            Zend_Loader::loadClass('NotifyErrorComponent', BASE_PATH.'/core/controllers/components');
            $errorComponent = new NotifyErrorComponent();
            $session = new Zend_Session_Namespace('Auth_User');
            $environment = 'testing';
            $errorComponent->initNotifier($environment, $error, $session, $_SERVER);

            $this->fail($errorComponent->getFullErrorMessage());
        }

        if ($withException) {
            $this->fail('The dispatch should throw an exception');
        }
    }

    /** Reset dispatch parameters. */
    public function resetAll()
    {
        $this->reset();
        $this->params = array();
        $this->frontController->setControllerDirectory(BASE_PATH.'/core/controllers', 'default');
        $this->_initModule();
        $this->_initREST();
    }

    /** Bootstrap the application. */
    public function appBootstrap()
    {
        $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
        $this->frontController->setControllerDirectory(BASE_PATH.'/core/controllers', 'default');
        $this->_initModule();
        $this->_initREST();
        $this->application->bootstrap();
    }

    /**
     * Setup database using XML files.
     *
     * @param string|array $files
     * @param null|string $module
     * @throws Zend_Exception
     */
    public function setupDatabase($files, $module = null)
    {
        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        $connection = new Zend_Test_PHPUnit_Db_Connection($db, $configDatabase->database->params->dbname);
        $databaseTester = new Zend_Test_PHPUnit_Db_SimpleTester($connection);
        if (is_array($files)) {
            foreach ($files as $f) {
                $path = BASE_PATH.'/core/tests/databaseDataset/'.$f.'.xml';
                if (isset($module)) {
                    $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$f.'.xml';
                }
                $xmlDataSet = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
                $replacementDataSet = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($xmlDataSet);
                $coreVersion = UtilityComponent::getLatestModuleVersion('core');
                $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $coreVersion, $matches);
                if ($result !== 1) {
                    throw new Zend_Exception('Invalid core version string.');
                }
                $replacementDataSet->addFullReplacement('##CORE_MAJOR_VERSION##', $matches[1]);
                $replacementDataSet->addFullReplacement('##CORE_MINOR_VERSION##', $matches[2]);
                $replacementDataSet->addFullReplacement('##CORE_PATCH_VERSION##', $matches[3]);
                $databaseTester->setupDatabase($replacementDataSet);
            }
        } else {
            $path = BASE_PATH.'/core/tests/databaseDataset/'.$files.'.xml';
            if (isset($module)) {
                $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$files.'.xml';
            }
            $xmlDataSet = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
            $replacementDataSet = new PHPUnit_Extensions_Database_DataSet_ReplacementDataSet($xmlDataSet);
            $coreVersion = UtilityComponent::getLatestModuleVersion('core');
            $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $coreVersion, $matches);
            if ($result !== 1) {
                throw new Zend_Exception('Invalid core version string.');
            }
            $replacementDataSet->addFullReplacement('##CORE_MAJOR_VERSION##', $matches[1]);
            $replacementDataSet->addFullReplacement('##CORE_MINOR_VERSION##', $matches[2]);
            $replacementDataSet->addFullReplacement('##CORE_PATCH_VERSION##', $matches[3]);
            $databaseTester->setupDatabase($replacementDataSet);
        }

        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('activedownload_activedownload_id_seq', (SELECT MAX(activedownload_id) FROM activedownload)+1);");
            $db->query("SELECT setval('assetstore_assetstore_id_seq', (SELECT MAX(assetstore_id) FROM assetstore)+1);");
            $db->query("SELECT setval('bitstream_bitstream_id_seq', (SELECT MAX(bitstream_id) FROM bitstream)+1);");
            $db->query("SELECT setval('community_community_id_seq', (SELECT MAX(community_id) FROM community)+1);");
            $db->query("SELECT setval('communityinvitation_communityinvitation_id_seq', (SELECT MAX(communityinvitation_id) FROM communityinvitation)+1);");
            $db->query("SELECT setval('feed_feed_id_seq', (SELECT MAX(feed_id) FROM feed)+1);");
            $db->query("SELECT setval('folder_folder_id_seq', (SELECT MAX(folder_id) FROM folder)+1);");
            $db->query("SELECT setval('group_group_id_seq', (SELECT MAX(group_id) FROM \"group\")+1);");
            $db->query("SELECT setval('item_item_id_seq', (SELECT MAX(item_id) FROM item)+1);");
            $db->query("SELECT setval('itemrevision_itemrevision_id_seq', (SELECT MAX(itemrevision_id) FROM itemrevision)+1);");
            $db->query("SELECT setval('license_license_id_seq', (SELECT MAX(license_id) FROM license)+1);");
            $db->query("SELECT setval('metadata_metadata_id_seq', (SELECT MAX(metadata_id) FROM metadata)+1);");
            $db->query("SELECT setval('metadatavalue_metadatavalue_id_seq', (SELECT MAX(metadatavalue_id) FROM metadatavalue)+1);");
            $db->query("SELECT setval('module_module_id_seq', (SELECT MAX(module_id) FROM module)+1);");
            $db->query("SELECT setval('newuserinvitation_newuserinvitation_id_seq', (SELECT MAX(newuserinvitation_id) FROM newuserinvitation)+1);");
            $db->query("SELECT setval('progress_progress_id_seq', (SELECT MAX(progress_id) FROM progress)+1);");
            $db->query("SELECT setval('setting_setting_id_seq', (SELECT MAX(setting_id) FROM setting)+1);");
            $db->query("SELECT setval('token_token_id_seq', (SELECT MAX(token_id) FROM token)+1);");
            $db->query("SELECT setval('user_user_id_seq', (SELECT MAX(user_id) FROM \"user\")+1);");
            $db->query("SELECT setval('userapi_userapi_id_seq', (SELECT MAX(userapi_id) FROM userapi)+1);");
        }
    }

    /** Load model and components. */
    public function loadElements()
    {
        Zend_Registry::set('models', array());
        if (isset($this->_models)) {
            MidasLoader::loadModels($this->_models);
            $modelsArray = Zend_Registry::get('models');
            foreach ($modelsArray as $key => $tmp) {
                $this->$key = $tmp;
            }
        }
        if (isset($this->_daos)) {
            foreach ($this->_daos as $dao) {
                Zend_Loader::loadClass($dao.'Dao', BASE_PATH.'/core/models/dao');
            }
        }

        Zend_Registry::set('components', array());
        if (isset($this->_components)) {
            if (!isset($this->Component)) {
                $this->Component = new stdClass();
            }
            foreach ($this->_components as $component) {
                $nameComponent = $component.'Component';
                Zend_Loader::loadClass($nameComponent, BASE_PATH.'/core/controllers/components');
                @$this->Component->$component = new $nameComponent();
            }
        }

        Zend_Registry::set('forms', array());
        if (isset($this->_forms)) {
            if (!isset($this->Form)) {
                $this->Form = new stdClass();
            }
            foreach ($this->_forms as $forms) {
                $nameForm = $forms.'Form';

                Zend_Loader::loadClass($nameForm, BASE_PATH.'/core/controllers/forms');
                @$this->Form->$forms = new $nameForm();
            }
        }
    }
}
