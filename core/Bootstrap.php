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
 * \class Bootstrap
 * \brief Provides common functionality for most bootstrapping needs, including dependency checking algorithms and the ability to load bootstrap resources on demand. *
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
  {
  /**
   * \fn protected _initDoctype()
   * \brief Send the HTML Doc Type to the view
   */
  protected function _initDoctype()
    {
    $this->bootstrap('view');
    $view = $this->getResource('view');
    $view->doctype('XHTML1_STRICT');
    }//end _initDoctype


  /**
   * \fn protected _initConfig()
   * \brief set the configuration  and save it in the registry
   */
  protected function _initConfig()
    {

    // init language
    $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
    if(isset($_COOKIE['lang']))
      {
      $configGlobal->application->lang = $_COOKIE['lang'];
      }

    if(isset($_GET['lang']))
      {
      if($_GET['lang'] != 'en' && $_GET['lang'] != 'fr')
        {
        $_GET['lang'] = 'en';
        }
      $configGlobal->application->lang = $_GET['lang'];
      setcookie("lang", $_GET['lang'], time() + 60 * 60 * 24 * 30 * 20, '/'); //30 days *20
      }

    Zend_Registry::set('configGlobal', $configGlobal);

    $configCore = new Zend_Config_Ini(CORE_CONFIG, 'global', true);
    Zend_Registry::set('configCore', $configCore);

    // check if internationalization enabled
    if(isset($configCore->internationalization) && $configCore->internationalization == "0")
      {
      $configGlobal->application->lang = "en";
      }

    $config = new Zend_Config_Ini(APPLICATION_CONFIG, $configGlobal->environment, true);
    Zend_Registry::set('config', $config);
    date_default_timezone_set($configGlobal->default->timezone);

    // InitDatabase
    $configDatabase = new Zend_Config_Ini(DATABASE_CONFIG, $configGlobal->environment, true);
    if($configDatabase->database->type == 'pdo')
      {
      // Does the server PDO support MySQL?
      if(!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'))
        {
        throw new Zend_Exception('It appears PDO is not setup to support MySQL. '.
                                 'Please check your PDO configuration.');
        }
      $pdoParams = array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true);
      $params = array(
        'host' => $configDatabase->database->params->host,
        'username' => $configDatabase->database->params->username,
        'password' => $configDatabase->database->params->password,
        'dbname' => $configDatabase->database->params->dbname,
        'port' => $configDatabase->database->params->port,
        'driver_options' => $pdoParams);
      if($configGlobal->environment == "production")
        {
        Zend_Loader::loadClass("ProductionDbProfiler", BASE_PATH . '/core/models/profiler');
        $params['profiler'] = new ProductionDbProfiler();
        }
      $db = Zend_Db::factory($configDatabase->database->adapter, $params);
      $db->getProfiler()->setEnabled(true);
      Zend_Db_Table::setDefaultAdapter($db);
      Zend_Registry::set('dbAdapter', $db);
      }
    elseif($configDatabase->database->type == 'mongo')
      {
      // The mongo driver should be a php extension
      $db = new Mongo($configDatabase->database->params->host.":".
                      $configDatabase->database->params->port);
      $dbname = $configDatabase->database->params->dbname;
      $database = $db->$dbname;
      Zend_Registry::set('dbAdapter', $database);
      }
    elseif($configDatabase->database->type == 'cassandra')
      {
      Zend_Loader::loadClass("connection", BASE_PATH . '/library/phpcassa');
      Zend_Loader::loadClass("columnfamily", BASE_PATH . '/library/phpcassa');

      $db = new Connection('midas', array(array('host' => $configDatabase->database->params->host,
                                                'port' => $configDatabase->database->params->port)));
      Zend_Registry::set('dbAdapter', $db);
      }
    else
      {
      throw new Zend_Exception("Database type Error. Please check the environment config file.");
      }

    Zend_Registry::set('configDatabase', $configDatabase);

    // Init Log
    if(is_writable(BASE_PATH."/log"))
      {
      $columnMapping = array('priority' => 'priority',
        'message' => 'message',
        'datetime' => 'timestamp',
        'module'   => 'module');
      $writerDb = new Zend_Log_Writer_Db($db, 'errorlog', $columnMapping);
      if($configGlobal->environment == 'production')
        {
        $formatter = new Zend_Log_Formatter_Simple();
        Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);
        $logger = Zend_Log::factory(array(
          array(
            'writerName' => 'Stream',
            'writerParams' => array(
              'stream' => './log/prod.log'),
            'filterName' => 'Priority',
            'filterParams' => array(
              'priority' => Zend_Log::INFO))));
        }
      else
        {
        Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
        $logger = Zend_Log::factory(array(
          array(
            'writerName' => 'Stream',
            'writerParams' => array(
              'stream' => './log/dev.log'),
            'filterName' => 'Priority',
            'filterParams' => array(
              'priority' => Zend_Log::WARN))));
        }
      if($configDatabase->database->adapter == 'PDO_MYSQL'
         && $configDatabase->database->params->password != 'set_your_password')
        {
        $logger->addWriter($writerDb);
        $logger->setEventItem('datetime', date('Y-m-d H:i:s'));
        $logger->setEventItem('module', 'unknown');
        }
      }
    else
      {
      $redacteur = new Zend_Log_Writer_Stream('php://output');
      $logger = new Zend_Log($redacteur);
      }
    Zend_Registry::set('logger', $logger);

    // Catch fatal errors
    require_once BASE_PATH.'/core/controllers/components/NotifyErrorComponent.php';
    $notifyErrorCompoenent = new NotifyErrorComponent();
    ini_set('display_errors', 0);
    register_shutdown_function(array($notifyErrorCompoenent, 'fatalEror'), $logger, new Zend_Mail());
    set_error_handler(array($notifyErrorCompoenent, 'warningError'), E_NOTICE|E_WARNING);

    return $config;
    }

  /** set up front */
  protected function _initFrontModules()
    {
    $this->bootstrap('frontController');
    $front = $this->getResource('frontController');
    $front->addModuleDirectory(BASE_PATH.'/modules');
    $front->addModuleDirectory(BASE_PATH.'/privateModules');
    }

  /** init routes*/
  protected function _initRouter()
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
      if($module == 1 &&  file_exists(BASE_PATH.'/modules/'.$key) && file_exists(BASE_PATH . "/modules/".$key."/AppController.php"))
        {
        $listeModule[] = $key;
        }
      elseif($module == 1 &&  file_exists(BASE_PATH.'/privateModules/'.$key) && file_exists(BASE_PATH . "/privateModules/".$key."/AppController.php"))
        {
        $listeModule[] = $key;
        }
      }

    // loading modules elements
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
      if(file_exists(BASE_PATH . "/modules/".$route."/constant/module.php"))
        {
        require_once BASE_PATH . "/modules/".$route."/constant/module.php";
        }

      if(file_exists(BASE_PATH . "/privateModules/".$route."/AppController.php"))
        {
        require_once BASE_PATH . "/privateModules/".$route."/AppController.php";
        }
      if(file_exists(BASE_PATH . "/privateModules/".$route."/models/AppDao.php"))
        {
        require_once BASE_PATH . "/privateModules/".$route."/models/AppDao.php";
        }
      if(file_exists(BASE_PATH . "/privateModules/".$route."/models/AppModel.php"))
        {
        require_once BASE_PATH . "/privateModules/".$route."/models/AppModel.php";
        }
      if(file_exists(BASE_PATH . "/privateModules/".$route."/constant/module.php"))
        {
        require_once BASE_PATH . "/privateModules/".$route."/constant/module.php";
        }

      $dir = BASE_PATH . "/modules/".$route."/models/base";
      if(!is_dir($dir))
        {
        $dir = BASE_PATH . "/privateModules/".$route."/models/base";
        }

      if(is_dir($dir))
        {
        $objects = scandir($dir);
        foreach($objects as $object)
          {
          if($object != "." && $object != "..")
            {
            if(filetype($dir."/".$object) != "dir")
              {
              require_once $dir."/".$object;
              }
            }
          }
        }
      }
    Zend_Registry::set('modulesEnable', $listeModule);
    return $router;
    }
  }

