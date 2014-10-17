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
require_once dirname(__FILE__).'/TestsConfig.php';
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** main models test element */
abstract class DatabaseTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
{
    protected $application;

    /**
     * Start xdebug code coverage.
     * Only has an effect if MIDAS_TEST_COVERAGE is defined to true
     */
    public function startCodeCoverage()
    {
        if (MIDAS_TEST_COVERAGE && extension_loaded('xdebug')) {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        }
    }

    /**
     * Stop xdebug code coverage and write the results.
     * Only has an effect if MIDAS_TEST_COVERAGE is defined to true
     */
    public function stopCodeCoverage()
    {
        if (MIDAS_TEST_COVERAGE && extension_loaded('xdebug')) {
            $data = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();

            $file = CMAKE_BINARY_DIR.'/xdebugCoverage/'.md5($_SERVER['SCRIPT_FILENAME']);
            file_put_contents($file.'.'.md5(uniqid(mt_rand(), true)).'.'.get_class($this), serialize($data));
        }
    }

    /**
     * get the Midas temporary directory
     *
     * @return string
     */
    protected function getTempDirectory()
    {
        return UtilityComponent::getTempDirectory();
    }

    /** init tests */
    public function setUp()
    {
        $this->bootstrap = array($this, 'appBootstrap');
        $this->loadElements();
        if (isset($this->enabledModules) && !empty($this->enabledModules)) {
            foreach ($this->enabledModules as $route) {
                if (file_exists(BASE_PATH.'/modules/'.$route.'/AppController.php')) {
                    require_once BASE_PATH.'/modules/'.$route.'/AppController.php';
                }
                if (file_exists(BASE_PATH.'/modules/'.$route.'/models/AppDao.php')) {
                    require_once BASE_PATH.'/modules/'.$route.'/models/AppDao.php';
                }
                if (file_exists(BASE_PATH.'/modules/'.$route.'/models/AppModel.php')) {
                    require_once BASE_PATH.'/modules/'.$route.'/models/AppModel.php';
                }
            }
        }

        parent::setUp();
        $this->startCodeCoverage();
    }

    /** end tests */
    public function tearDown()
    {
        parent::tearDown();
        $this->stopCodeCoverage();
    }

    /** init Midas */
    public function appBootstrap()
    {
        $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
        $this->application->bootstrap();
    }

    /** setup database using xml files */
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
            $db->query("SELECT setval('feed_feed_id_seq', (SELECT MAX(feed_id) FROM feed)+1);");
            $db->query("SELECT setval('user_user_id_seq', (SELECT MAX(user_id) FROM \"user\")+1);");
            $db->query("SELECT setval('item_item_id_seq', (SELECT MAX(item_id) FROM item)+1);");
            $db->query(
                "SELECT setval('itemrevision_itemrevision_id_seq', (SELECT MAX(itemrevision_id) FROM itemrevision)+1);"
            );
            $db->query("SELECT setval('folder_folder_id_seq', (SELECT MAX(folder_id) FROM folder)+1);");
            $db->query("SELECT setval('bitstream_bitstream_id_seq', (SELECT MAX(bitstream_id) FROM bitstream)+1);");
        }
    }

    /** create mock database connection */
    protected function getConnection()
    {
        if (!isset($this->_connectionMock) || $this->_connectionMock == null) {
            $configDatabase = Zend_Registry::get('configDatabase');
            if (empty($configDatabase->database->params->driver_options)) {
                $driverOptions = array();
            } else {
                $driverOptions = $configDatabase->database->params->driver_options->toArray();
            }
            $params = array(
                'dbname' => $configDatabase->database->params->dbname,
                'username' => $configDatabase->database->params->username,
                'password' => $configDatabase->database->params->password,
                'driver_options' => $driverOptions,
            );
            if (empty($configDatabase->database->params->unix_socket)) {
                $params['host'] = $configDatabase->database->params->host;
                $params['port'] = $configDatabase->database->params->port;
            } else {
                $params['unix_socket'] = $configDatabase->database->params->unix_socket;
            }
            $db = Zend_Db::factory($configDatabase->database->adapter, $params);
            $this->_connectionMock = $this->createZendDbConnection($db, $configDatabase->database->params->dbname);
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
        }

        return $this->_connectionMock;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet($name = 'default', $module = null)
    {
        $path = BASE_PATH.'/core/tests/databaseDataset/'.$name.'.xml';
        if (isset($module) && !empty($module)) {
            $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$name.'.xml';
        }

        return $this->createFlatXmlDataSet($path);
    }

    /** loadData
     *
     * @param modelName of model to load
     * @param file that the test data is defined in
     * @param modelModule the module of the model, or '' if in core
     * @param fileModule the module the test data file is in, or '' if in core
     */
    protected function loadData($modelName, $file = null, $modelModule = '', $fileModule = '')
    {
        $model = MidasLoader::loadModel($modelName, $modelModule);
        if ($file == null) {
            $file = strtolower($modelName);
        }
        $data = $this->getDataSet($file, $fileModule);
        $dataUsers = $data->getTable($model->getName());
        $key = array();
        for ($i = 0; $i < $dataUsers->getRowCount(); $i++) {
            $key[] = $dataUsers->getValue($i, $model->getKey());
        }

        return $model->load($key);
    }

    /**
     *  Loads model and components
     */
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
            foreach ($this->_components as $component) {
                $nameComponent = $component.'Component';
                Zend_Loader::loadClass($nameComponent, BASE_PATH.'/core/controllers/components');
                @$this->Component->$component = new $nameComponent();
            }
        }

        Zend_Registry::set('forms', array());
        if (isset($this->_forms)) {
            foreach ($this->_forms as $forms) {
                $nameForm = $forms.'Form';

                Zend_Loader::loadClass($nameForm, BASE_PATH.'/core/controllers/forms');
                @$this->Form->$forms = new $nameForm();
            }
        }
    }

    /** completion eclipse */
    /**
     * Assetstore Model
     *
     * @var AssetstoreModel
     */
    public $Assetstrore;
    /**
     * Bitstream Model
     *
     * @var BitstreamModel
     */
    public $Bitstream;
    /**
     * Community Model
     *
     * @var CommunityModel
     */
    public $Community;
    /**
     * Feed Model
     *
     * @var FeedModel
     */
    public $Feed;
    /**
     * Feedpolicygroup Model
     *
     * @var FeedpolicygroupModel
     */
    public $Feedpolicygroup;
    /**
     * Feedpolicyuser Model
     *
     * @var FeedpolicyuserModel
     */
    public $Feedpolicyuser;
    /**
     * Folder Model
     *
     * @var FolderModel
     */
    public $Folder;
    /**
     * Folderpolicygroup Model
     *
     * @var FolderpolicygroupModel
     */
    public $Folderpolicygroup;
    /**
     * Folderpolicyuser Model
     *
     * @var FolderpolicyuserModel
     */
    public $Folderpolicyuser;
    /**
     * Group Model
     *
     * @var GroupModel
     */
    public $Group;
    /**
     * Item Model
     *
     * @var ItemModel
     */
    public $Item;
    /**
     * Itempolicygroup Model
     *
     * @var ItempolicygroupModel
     */
    public $Itempolicygroup;
    /**
     * Itempolicyuser Model
     *
     * @var ItempolicyuserModel
     */
    public $Itempolicyuser;
    /**
     * ItemRevision Model
     *
     * @var ItemRevisionModel
     */
    public $ItemRevision;
    /**
     * User Model
     *
     * @var UserModel
     */
    public $User;
    /** end completion eclipse */
}
