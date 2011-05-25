<?php
require_once dirname(__FILE__).'/../bootstrap.php';
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
  {
  protected $application;
  
  protected $params = array();

  public function setUp()
    {
    $this->bootstrap = array($this, 'appBootstrap');
    $this->loadElements();
    parent::setUp();
    }
  
      /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
  protected function getDataSet($name='default')
    {
    return new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(
            dirname(__FILE__) . '/../databaseDataset/'.$name.".xml");
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
    $dataUsers=$data->getTable($model->getName());
    $rows=$dataUsers->getRowCount();
    $key=array();
    for($i=0; $i<$dataUsers->getRowCount();$i++)
      {
      $key[]=$dataUsers->getValue($i, $model->getKey());
      }
    return $model->load($key);
    }

 private function initModule()
   {
    $router = Zend_Controller_Front::getInstance()->getRouter();
    
    //Init Modules    
    $frontController = Zend_Controller_Front::getInstance();  
    $frontController->addControllerDirectory(BASE_PATH . '/core/controllers');
    $modules = new Zend_Config_Ini(APPLICATION_CONFIG, 'module');
    // routes modules
    $listeModule = array();
    foreach($modules as $key => $module)
      {      
      if($module == 1 &&  file_exists(BASE_PATH.'/modules/'.$key))
        {
        $listeModule[] = $key;
        }
      }
    foreach($listeModule as $m)
      { 
      $route = $m;
      $nameModule = $m; 
      $router->addRoute($nameModule."-1", 
          new Zend_Controller_Router_Route("".$route."/:controller/:action/*", 
              array(
                  'module' => $nameModule)));
      $router->addRoute($nameModule."-2", 
          new Zend_Controller_Router_Route("".$route."/:controller/", 
              array(
                  'module' => $nameModule,
                  'action' => 'index')));
      $router->addRoute($nameModule."-3", 
          new Zend_Controller_Router_Route("".$route."/", 
              array(
                  'module' => $nameModule,
                  'controller' => 'index',
                  'action' => 'index')));
      $frontController->addControllerDirectory(BASE_PATH . "/modules/".$route."/controllers", $nameModule);
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
    Zend_Registry::set('modulesEnable', $listeModule);
   }
    
 public function dispatchUrI($uri, $userDao = null, $withException = false){
   if($userDao != null)
     {
     $this->params['testingUserId'] = $userDao->getKey();
     }
     
   $this->request->setQuery($this->params);
   $this->dispatch($uri);
   if($this->request->getControllerName()=="error")
     {
     if($withException)
       {
       return;
       }
     $error = $this->request->getParam('error_handler');
     Zend_Loader::loadClass("NotifyErrorComponent", BASE_PATH . '/core/controllers/components');
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
     
   if($withException)
     {
     $this->fail('The dispatch should throw an exception');
     }
   }

  public function resetAll()
    {
    $this->reset();
    $this->params = array();
    $this->frontController->setControllerDirectory(BASE_PATH . '/core/controllers', 'default');
    }
   
 public function tearDown()
    {
    Zend_Controller_Front::getInstance()->resetInstance();
    $this->resetAll();
    parent::tearDown();
    }
  public function appBootstrap()
    {
    $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
    $this->frontController->setControllerDirectory(BASE_PATH . '/core/controllers', 'default');
    $this->initModule();
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


  }