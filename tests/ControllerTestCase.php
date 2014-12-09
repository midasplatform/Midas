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

require_once dirname(__FILE__).'/TestsBootstrap.php';
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/**
 * @property ActivedownloadModel $Activedownload
 * @property AssetstoreModel $Assetstore
 * @property BitstreamModel $Bitstream
 * @property CommunityModel $Community
 * @property CommunityInvitationModel $CommunityInvitation
 * @property object $Component
 * @property ErrorlogModel $Errorlog
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
     * @return PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet
     */
    protected function getDataSet($name = 'default', $module = null)
    {
        $path = BASE_PATH.'/core/tests/databaseDataset/'.$name.'.xml';
        if (isset($module) && !empty($module)) {
            $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$name.'.xml';
        }

        return new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
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
        for ($i = 0; $i < $dataUsers->getRowCount(); $i++) {
            $key[] = $dataUsers->getValue($i, $model->getKey());
        }

        return $model->load($key);
    }

    /** Initialize modules. */
    private function _initModule()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        // Init Modules
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers');
        if (isset($this->enabledModules) || (isset($_POST['enabledModules']) || isset($_GET['enabledModules']))) {
            if (isset($this->enabledModules)) {
                $paramsTestingModules = $this->enabledModules;
            } elseif (isset($_POST['enabledModules'])) {
                $paramsTestingModules = explode(';', $_POST['enabledModules']);
            } else {
                $paramsTestingModules = explode(';', $_GET['enabledModules']);
            }
            $modules = array();
            foreach ($paramsTestingModules as $p) {
                $modules[$p] = 1;
                if (file_exists(BASE_PATH.'/modules/'.$p.'/constant/module.php')) {
                    require_once BASE_PATH.'/modules/'.$p.'/constant/module.php';
                }
                if (file_exists(BASE_PATH.'/privateModules/'.$p.'/constant/module.php')) {
                    require_once BASE_PATH.'/privateModules/'.$p.'/constant/module.php';
                }
            }
        } else {
            $modules = array();
        }

        // routes modules
        $listeModule = array();
        $apiModules = array();
        foreach ($modules as $key => $module) {
            if ($module == 1 && file_exists(BASE_PATH.'/modules/'.$key)) {
                $listeModule[] = $key;
                // get web API controller directories and web API module names for enabled modules
                if (file_exists(BASE_PATH.'/modules/'.$key.'/controllers/api')) {
                    $frontController->addControllerDirectory(
                        BASE_PATH.'/modules/'.$key.'/controllers/api',
                        'api'.$key
                    );
                    $apiModules[] = $key;
                }
            }
        }

        /** @var UtilityComponent $utilityComponent */
        $utilityComponent = MidasLoader::loadComponent('Utility');

        require_once BASE_PATH.'/core/ApiController.php';
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers/api', 'rest');
        // add RESTful route for web APIs
        $restRoute = new Zend_Rest_Route($frontController, array(), array('rest'));
        // add regular route for apikey configuration page
        $router->addRoute(
            'rest-apikey',
            new Zend_Controller_Router_Route('/apikey/:action/', array('module' => 'rest', 'controller' => 'apikey'))
        );
        $router->addRoute('api-core', $restRoute);
        foreach ($listeModule as $m) {
            $route = $m;
            $nameModule = $m;
            $router->addRoute(
                $nameModule.'-1',
                new Zend_Controller_Router_Route(
                    ''.$route.'/:controller/:action/*', array('module' => $nameModule)
                )
            );
            $router->addRoute(
                $nameModule.'-2',
                new Zend_Controller_Router_Route(
                    ''.$route.'/:controller/',
                    array('module' => $nameModule, 'action' => 'index')
                )
            );
            $router->addRoute(
                $nameModule.'-3',
                new Zend_Controller_Router_Route(
                    ''.$route.'/',
                    array('module' => $nameModule, 'controller' => 'index', 'action' => 'index')
                )
            );
            $frontController->addControllerDirectory(BASE_PATH.'/modules/'.$route.'/controllers', $nameModule);
            if (file_exists(BASE_PATH.'/modules/'.$route.'/AppController.php')) {
                require_once BASE_PATH.'/modules/'.$route.'/AppController.php';
            }
            if (file_exists(BASE_PATH.'/modules/'.$route.'/models/AppDao.php')) {
                require_once BASE_PATH.'/modules/'.$route.'/models/AppDao.php';
            }
            if (file_exists(BASE_PATH.'/modules/'.$route.'/models/AppModel.php')) {
                require_once BASE_PATH.'/modules/'.$route.'/models/AppModel.php';
            }
            $utilityComponent->installModule($m);
        }
        Zend_Registry::set('modulesEnable', $listeModule);
        Zend_Registry::set('modulesHaveApi', $apiModules);
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
                $databaseFixture = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
                $databaseTester->setupDatabase($databaseFixture);
            }
        } else {
            $path = BASE_PATH.'/core/tests/databaseDataset/'.$files.'.xml';
            if (isset($module)) {
                $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$files.'.xml';
            }
            $databaseFixture = new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
            $databaseTester->setupDatabase($databaseFixture);
        }

        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('assetstore_assetstore_id_seq', (SELECT MAX(assetstore_id) FROM assetstore)+1);");
            $db->query("SELECT setval('bitstream_bitstream_id_seq', (SELECT MAX(bitstream_id) FROM bitstream)+1);");
            $db->query("SELECT setval('feed_feed_id_seq', (SELECT MAX(feed_id) FROM feed)+1);");
            $db->query("SELECT setval('user_user_id_seq', (SELECT MAX(user_id) FROM \"user\")+1);");
            $db->query("SELECT setval('folder_folder_id_seq', (SELECT MAX(folder_id) FROM folder)+1);");
            $db->query("SELECT setval('item_item_id_seq', (SELECT MAX(item_id) FROM item)+1);");
            $db->query("SELECT setval('itemrevision_itemrevision_id_seq', (SELECT MAX(itemrevision_id) FROM itemrevision)+1);");
            $db->query("SELECT setval('folder_folder_id_seq', (SELECT MAX(folder_id) FROM folder)+1);");
            $db->query("SELECT setval('bitstream_bitstream_id_seq', (SELECT MAX(bitstream_id) FROM bitstream)+1);");
            $db->query("SELECT setval('license_license_id_seq', (SELECT MAX(license_id) FROM license)+1);");
            $db->query("SELECT setval('metadata_metadata_id_seq', (SELECT MAX(metadata_id) FROM metadata)+1);");
            $db->query("SELECT setval('setting_setting_id_seq', (SELECT MAX(setting_id) FROM setting)+1);");
            $db->query("SELECT setval('user_user_id_seq', (SELECT MAX(user_id) FROM \"user\")+1);");
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
