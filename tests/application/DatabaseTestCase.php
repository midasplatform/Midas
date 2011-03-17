<?php
require_once dirname(__FILE__).'/../bootstrap.php';
abstract class DatabaseTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
  {
  protected $application;

  public function setUp()
    {
    $this->bootstrap = array($this, 'appBootstrap');
    $this->loadElements();
    parent::setUp();
    }

 public function tearDown()
    {
    parent::tearDown();
    }
  public function appBootstrap()
    {
    $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
    $this->application->bootstrap();
    }

  public function setupDatabase($files)
    {
    $db = Zend_Registry::get('dbAdapter');
    $configDatabase= Zend_Registry::get('configDatabase' );
    $connection = new Zend_Test_PHPUnit_Db_Connection($db,$configDatabase->database->params->dbname);
    $databaseTester = new Zend_Test_PHPUnit_Db_SimpleTester($connection);
    if(is_array($files))
      {
      foreach($files as $f)
        {
        $databaseFixture =  new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet( dirname(__FILE__) . '/../databaseDataset/'.$f.'.xml');
        $databaseTester->setupDatabase($databaseFixture);
        }
      }
    else
      {
      $databaseFixture =  new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet( dirname(__FILE__) . '/../databaseDataset/'.$files.'.xml');
      $databaseTester->setupDatabase($databaseFixture);
      }
    }


   protected function getConnection()
    {
    if(!isset($this->_connectionMock)||$this->_connectionMock == null)
      {
      $configDatabase = new Zend_Config_Ini(DATABASE_CONFIG, 'testing');
      if ($configDatabase->database->type == 'pdo')
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
    protected function getDataSet($name='default')
    {
    return $this->createFlatXmlDataSet(
            dirname(__FILE__) . '/../databaseDataset/'.$name.".xml"
        );
    }

    /** loadData */
    protected function loadData($modelName,$file=null)
      {
      $model=$this->ModelLoader->loadModel($modelName);
      if($file==null)
        {
        $file=strtolower($modelName);
        }

      $data=$this->getDataSet($file);
      $dataUsers=$data->getTable($model->_name);
      $rows=$dataUsers->getRowCount();
      $key=array();
      for($i=0; $i<$dataUsers->getRowCount();$i++)
        {
        $key[]=$dataUsers->getValue($i, $model->_key);
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
      foreach ($modelsArray as $key => $tmp)
        {
        $this->$key = $tmp;
        }
      }
    if(isset($this->_daos))
      {
      foreach ($this->_daos as $dao)
        {
        Zend_Loader::loadClass($dao . "Dao", BASE_PATH . '/core/models/dao');
        }
      }

    Zend_Registry::set('components', array());
    if(isset($this->_components))
      {
      foreach ($this->_components as $component)
        {
        $nameComponent = $component . "Component";
        Zend_Loader::loadClass($nameComponent, BASE_PATH . '/core/controllers/components');
        @$this->Component->$component = new $nameComponent();
        }
      }

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach ($this->_forms as $forms)
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
   * ItemKeyword Model
   * @var ItemKeywordModel
   */
  var $ItemKeyword;
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