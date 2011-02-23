<?php
require_once dirname(__FILE__).'/../bootstrap.php';
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
  {
  protected $application;

  public function setUp()
    {
    $this->bootstrap = array($this, 'appBootstrap');
    $this->loadElements();
    parent::setUp();
    }

 public function dispatchUrI($uri){
   $this->dispatch($uri);
   if($this->request->getControllerName()=="error")
     {
     $error = $this->request->getParam('error_handler');
     Zend_Loader::loadClass("NotifyErrorComponent", BASE_PATH . '/application/controllers/components');
     $errorComponent = new NotifyErrorComponent();
     $mailer = new Zend_Mail();
     $session = new Zend_Session_Namespace('Auth_User');
     $db = Zend_Registry::get('dbAdapter');
     $profiler = $db->getProfiler();
     $environment = 'testing';
     $errorComponent->initNotifier(
          $environment,
          $error,
          $mailer,
          $session,
          $profiler,
          $_SERVER
      );

     $this->fail($errorComponent->getFullErrorMessage());
     }
   $this->assertController("browse");
   $this->assertAction("index");
   }


 public function tearDown()
    {
    $this->resetRequest();
    $this->resetResponse();
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
        Zend_Loader::loadClass($dao . "Dao", BASE_PATH . '/application/models/dao');
        }
      }

    Zend_Registry::set('components', array());
    if(isset($this->_components))
      {
      foreach ($this->_components as $component)
        {
        $nameComponent = $component . "Component";
        Zend_Loader::loadClass($nameComponent, BASE_PATH . '/application/controllers/components');
        @$this->Component->$component = new $nameComponent();
        }
      }

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach ($this->_forms as $forms)
        {
        $nameForm = $forms . "Form";

        Zend_Loader::loadClass($nameForm, BASE_PATH . '/application/controllers/forms');
        @$this->Form->$forms = new $nameForm();
        }
      }
    }


  }