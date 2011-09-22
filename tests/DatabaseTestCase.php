<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
require_once dirname(__FILE__).'/bootstrap.php';
/** main models test element*/
abstract class DatabaseTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
  {
  protected $application;

  /** init tests*/
  public function setUp()
    {
    $this->ModelLoader = new MIDAS_ModelLoader();
    $this->bootstrap = array($this, 'appBootstrap');
    $this->loadElements();
    if(isset($this->enabledModules) && !empty($this->enabledModules))
      {
      foreach($this->enabledModules as $route)
        {
        if(file_exists(BASE_PATH . "/modules/".$route."/AppController.php"))
          {
          require_once BASE_PATH . "/modules/".$route."/AppController.php";
          }
        if(file_exists(BASE_PATH . "/modules/".$route."/models/AppDao.php"))
          {
          require_once BASE_PATH . "/modules/".$route."/models/AppDao.php";
          }
        if(file_exists(BASE_PATH . "/modules/".$route."/models/AppModel.php"))
          {
          require_once BASE_PATH . "/modules/".$route."/models/AppModel.php";
          }
        }
      }

    parent::setUp();
    }

  /** end tests*/
  public function tearDown()
    {
    parent::tearDown();
    }

  /** init midas*/
  public function appBootstrap()
    {
    $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
    $this->application->bootstrap();
    }

  /** setup databse using xml files*/
  public function setupDatabase($files, $module = null)
    {
    $db = Zend_Registry::get('dbAdapter');
    $configDatabase = Zend_Registry::get('configDatabase' );
    $connection = new Zend_Test_PHPUnit_Db_Connection($db, $configDatabase->database->params->dbname);
    $databaseTester = new Zend_Test_PHPUnit_Db_SimpleTester($connection);
    if(is_array($files))
      {
      foreach($files as $f)
        {
        $path = BASE_PATH.'/core/tests/databaseDataset/'.$f.".xml";
        if(isset($module))
          {
          $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$f.".xml";
          }
        $databaseFixture =  new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
        $databaseTester->setupDatabase($databaseFixture);
        }
      }
    else
      {
      $path = BASE_PATH.'/core/tests/databaseDataset/'.$files.".xml";
      if(isset($module))
        {
        $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$files.".xml";
        }
      $databaseFixture =  new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
      $databaseTester->setupDatabase($databaseFixture);
      }

    if($configDatabase->database->adapter == 'PDO_PGSQL')
      {
      $db->query("SELECT setval('feed_feed_id_seq', (SELECT MAX(feed_id) FROM feed)+1);");
      $db->query("SELECT setval('user_user_id_seq', (SELECT MAX(user_id) FROM \"user\")+1);");
      $db->query("SELECT setval('item_item_id_seq', (SELECT MAX(item_id) FROM item)+1);");
      $db->query("SELECT setval('itemrevision_itemrevision_id_seq', (SELECT MAX(itemrevision_id) FROM itemrevision)+1);");
      $db->query("SELECT setval('folder_folder_id_seq', (SELECT MAX(folder_id) FROM folder)+1);");
      }
    }

  /** create mock database connection*/
  protected function getConnection()
    {
    if(!isset($this->_connectionMock) || $this->_connectionMock == null)
      {
      $configDatabase = Zend_Registry::get('configDatabase');
      if($configDatabase->database->type == 'pdo')
        {
        $db = Zend_Db::factory($configDatabase->database->adapter, array(
          'host' => $configDatabase->database->params->host,
          'username' => $configDatabase->database->params->username,
          'password' => $configDatabase->database->params->password,
          'dbname' => $configDatabase->database->params->dbname,
         )
        );
        $this->_connectionMock = $this->createZendDbConnection(
          $db, $configDatabase->database->params->dbname
        );
        Zend_Db_Table_Abstract::setDefaultAdapter($db);
        }
      }
    return $this->_connectionMock;
    }

  /**
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
  protected function getDataSet($name = 'default', $module = null)
    {
    $path = BASE_PATH.'/core/tests/databaseDataset/'.$name.".xml";
    if(isset($module) && !empty($module))
      {
      $path = BASE_PATH.'/modules/'.$module.'/tests/databaseDataset/'.$name.".xml";
      }
    return $this->createFlatXmlDataSet($path);
    }

  /** loadData
   * @param modelName of model to load
   * @param file that the test data is defined in
   * @param modelModule the module of the model, or '' if in core
   * @param fileModule the module the test data file is in, or '' if in core
   */
  protected function loadData($modelName, $file = null, $modelModule = '', $fileModule = '')
    {
    $model = $this->ModelLoader->loadModel($modelName, $modelModule);
    if($file == null)
      {
      $file = strtolower($modelName);
      }
    $data = $this->getDataSet($file, $fileModule);
    $dataUsers = $data->getTable($model->getName());
    $rows = $dataUsers->getRowCount();
    $key = array();
    for($i = 0; $i < $dataUsers->getRowCount();$i++)
      {
      $key[] = $dataUsers->getValue($i, $model->getKey());
      }
    return $model->load($key);
    }

  /**
  * @method public  loadElements()
  *  Loads model and components
  */
  public function loadElements()
    {
    Zend_Registry::set('models', array());
    if(isset($this->_models))
      {
      $this->ModelLoader = new MIDAS_ModelLoader();
      $this->ModelLoader->loadModels($this->_models);
      $modelsArray = Zend_Registry::get('models');
      foreach($modelsArray as $key => $tmp)
        {
        $this->$key = $tmp;
        }
      }
    if(isset($this->_daos))
      {
      foreach($this->_daos as $dao)
        {
        Zend_Loader::loadClass($dao . "Dao", BASE_PATH . '/core/models/dao');
        }
      }

    Zend_Registry::set('components', array());
    if(isset($this->_components))
      {
      foreach($this->_components as $component)
        {
        $nameComponent = $component . "Component";
        Zend_Loader::loadClass($nameComponent, BASE_PATH . '/core/controllers/components');
        @$this->Component->$component = new $nameComponent();
        }
      }

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach($this->_forms as $forms)
        {
        $nameForm = $forms . "Form";

        Zend_Loader::loadClass($nameForm, BASE_PATH . '/core/controllers/forms');
        @$this->Form->$forms = new $nameForm();
        }
      }
    }

  /**completion eclipse*/
  /**
   * Assetstrore Model
   * @var AssetstoreModel
   */
  var $Assetstrore;
  /**
   * Bitstream Model
   * @var BitstreamModel
   */
  var $Bitstream;
  /**
   * Community Model
   * @var CommunityModel
   */
  var $Community;
  /**
   * Feed Model
   * @var FeedModel
   */
  var $Feed;
  /**
   * Feedpolicygroup Model
   * @var FeedpolicygroupModel
   */
  var $Feedpolicygroup;
  /**
   * Feedpolicyuser Model
   * @var FeedpolicyuserModel
   */
  var $Feedpolicyuser;
  /**
   * Folder Model
   * @var FolderModel
   */
  var $Folder;
  /**
   * Folderpolicygroup Model
   * @var FolderpolicygroupModel
   */
  var $Folderpolicygroup;
  /**
   * Folderpolicyuser Model
   * @var FolderpolicyuserModel
   */
  var $Folderpolicyuser;
  /**
   * Group Model
   * @var GroupModel
   */
  var $Group;
  /**
   * Item Model
   * @var ItemModel
   */
  var $Item;
  /**
   * Itempolicygroup Model
   * @var ItempolicygroupModel
   */
  var $Itempolicygroup;
  /**
   * Itempolicyuser Model
   * @var ItempolicyuserModel
   */
  var $Itempolicyuser;
  /**
   * ItemRevision Model
   * @var ItemRevisionModel
   */
  var $ItemRevision;
  /**
   * User Model
   * @var UserModel
   */
  var $User;
  /**end completion eclipse */

  }