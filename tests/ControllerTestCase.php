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

require_once dirname(__FILE__).'/bootstrap.php';
require_once dirname(__FILE__).'/configuredVars.php';
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** main controller test element*/
abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
  {
  protected $application;

  protected $params = array();

  /**
   * Start xdebug code coverage.
   * Only has an effect if MIDAS_TEST_COVERAGE is defined to true
   */
  public function startCodeCoverage()
    {
    if(MIDAS_TEST_COVERAGE && extension_loaded('xdebug'))
      {
      xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
      }
    }

  /**
   * Stop xdebug code coverage and write the results.
   * Only has an effect if MIDAS_TEST_COVERAGE is defined to true
   */
  public function stopCodeCoverage()
    {
    if(MIDAS_TEST_COVERAGE && extension_loaded('xdebug'))
      {
      $data = xdebug_get_code_coverage();
      xdebug_stop_code_coverage();

      $file = CMAKE_BINARY_DIR.'/xdebugCoverage/'.md5($_SERVER['SCRIPT_FILENAME']);
      file_put_contents($file.'.'.md5(uniqid(rand(), true)).'.'.get_class($this), serialize($data));
      }
    }

  /**
   * @method protected getTempDirectory()
   * get the midas temporary directory
   * @return string
   */
  protected function getTempDirectory()
    {
    return UtilityComponent::getTempDirectory();
    }

  /** set up tests*/
  public function setUp()
    {
    $this->bootstrap = array($this, 'appBootstrap');
    $this->loadElements();
    $this->ModelLoader = new MIDAS_ModelLoader();
    parent::setUp();
    $this->startCodeCoverage();
    }

  /** end test */
  public function tearDown()
    {
    Zend_Controller_Front::getInstance()->resetInstance();
    $this->resetAll();
    parent::tearDown();
    $this->stopCodeCoverage();
    }

  /** get response body*/
  public function getBody()
    {
    return $this->response->outputBody();
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
    return new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($path);
    }

  /** loadData */
  protected function loadData($modelName, $file = null, $module = '')
    {
    $model = $this->ModelLoader->loadModel($modelName, $module);
    if($file == null)
      {
      $file = strtolower($modelName);
      }

    $data = $this->getDataSet($file, $module);
    $dataUsers = $data->getTable($model->getName());
    $rows = $dataUsers->getRowCount();
    $key = array();
    for($i = 0; $i < $dataUsers->getRowCount();$i++)
      {
      $key[] = $dataUsers->getValue($i, $model->getKey());
      }
    return $model->load($key);
    }

  /** ini modules*/
  private function _initModule()
    {
    $router = Zend_Controller_Front::getInstance()->getRouter();
    //Init Modules
    $frontController = Zend_Controller_Front::getInstance();
    $frontController->addControllerDirectory(BASE_PATH . '/core/controllers');
    if(isset($this->enabledModules) || (isset($_POST['enabledModules']) || isset($_GET['enabledModules'])))
      {
      if(isset($this->enabledModules))
        {
        $paramsTestingModules = $this->enabledModules;
        }
      else if(isset($_POST['enabledModules']))
        {
        $paramsTestingModules = explode(';', $_POST['enabledModules']);
        }
      else
        {
        $paramsTestingModules = explode(';', $_GET['enabledModules']);
        }
      $modules = array();
      foreach($paramsTestingModules as $p)
        {
        $modules[$p] = 1;
        if(file_exists(BASE_PATH . "/modules/".$p."/constant/module.php"))
          {
          require_once BASE_PATH . "/modules/".$p."/constant/module.php";
          }
        if(file_exists(BASE_PATH . "/privateModules/".$p."/constant/module.php"))
          {
          require_once BASE_PATH . "/privateModules/".$p."/constant/module.php";
          }
        }
      }
    else
      {
      $modules = array();
      }

    // routes modules
    $listeModule = array();
    foreach($modules as $key => $module)
      {
      if($module == 1 &&  file_exists(BASE_PATH.'/modules/'.$key))
        {
        $listeModule[] = $key;
        }
      }

    require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
    $utilityComponent = new UtilityComponent();
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

  /**
   *
    The method dispatchUrl  fetchs the page.
    Parameters:
    - $uri: page you want to render
    - $userDao : user you want to log in with
    - $withException : You may want to test if you will get an exception
   */
  public function dispatchUrI($uri, $userDao = null, $withException = false)
    {
    if($userDao != null)
      {
      $this->params['testingUserId'] = $userDao->getKey();
      }

    if(isset($this->enabledModules) && !empty($this->enabledModules))
      {
      $this->params['enabledModules'] = join(";", $this->enabledModules);
      }
    else
      {
      unset($this->params['enabledModules']);
      }

    if($this->request->isPost())
      {
      $this->request->setPost($this->params);
      }
    else
      {
      $this->request->setQuery($this->params);
      }
    $this->dispatch($uri);
    $this->assertNotResponseCode('404');
    if($this->request->getControllerName() == "error")
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

  /** reset dispatch parameters*/
  public function resetAll()
    {
    $this->reset();
    $this->params = array();
    $this->frontController->setControllerDirectory(BASE_PATH . '/core/controllers', 'default');
    $this->_initModule();
    }

  /** init midas*/
  public function appBootstrap()
    {
    $this->application = new Zend_Application(APPLICATION_ENV, CORE_CONFIG);
    $this->frontController->setControllerDirectory(BASE_PATH . '/core/controllers', 'default');
    $this->_initModule();
    $this->application->bootstrap();
    }

  /** setup database using xml files*/
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
      $db->query("SELECT setval('bitstream_bitstream_id_seq', (SELECT MAX(bitstream_id) FROM bitstream)+1);");
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
  }//end class
