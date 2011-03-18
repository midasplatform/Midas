<?php

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
    }

  /**
   * \fn protected _initConfig()
   * \brief set the configuration  and save it in the registry
   */
  protected function _initConfig()
    {
    Zend_Loader::loadClass( "UserDao", BASE_PATH . '/core/models/dao');
    Zend_Loader::loadClass( "ItemDao", BASE_PATH . '/core/models/dao');
    if (isset($_POST['sid']))    
      { 
      Zend_Session::setId($_POST['sid']);       
      }
    Zend_Session::start();
    $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG,'global',true);
    if(isset($_COOKIE['lang']))
      {
      $configGlobal->application->lang=$_COOKIE['lang'];
      } 
    
      
    if(isset($_GET['lang']))
      {
      if($_GET['lang']!='en'&&$_GET['lang']!='fr')
        {
        $_GET['lang']='en';
        }
      $configGlobal->application->lang=$_GET['lang'];
      setcookie("lang", $_GET['lang'], time()+60*60*24*30*20,'/'); //30 days *20
      }
   
    Zend_Registry::set('configGlobal', $configGlobal);

    $configCore = new Zend_Config_Ini(CORE_CONFIG,'global');
    Zend_Registry::set('configCore', $configCore);

    $config = new Zend_Config_Ini(APPLICATION_CONFIG, $configGlobal->environment);
    Zend_Registry::set('config', $config);    
    date_default_timezone_set('Europe/Paris');
    
    
    // InitDatabase
    $configDatabase = new Zend_Config_Ini(DATABASE_CONFIG, $configGlobal->environment);
    if ($configDatabase->database->type == 'pdo')
      {      
      $pdoParams = array(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        );
      $params= array(
        'host' => $configDatabase->database->params->host,
        'username' => $configDatabase->database->params->username,
        'password' => $configDatabase->database->params->password,
        'dbname' => $configDatabase->database->params->dbname,
        'driver_options' => $pdoParams
      );
      if($configGlobal->environment=="production")
        {
        Zend_Loader::loadClass( "ProductionDbProfiler", BASE_PATH . '/library/MIDAS/models/profiler');
        $params['profiler']=new ProductionDbProfiler();
        }
      $db = Zend_Db::factory($configDatabase->database->adapter, $params);
      $db->getProfiler()->setEnabled(true);
      Zend_Db_Table::setDefaultAdapter($db);
      Zend_Registry::set('dbAdapter', $db);
      }
    elseif ($configDatabase->database->type == 'cassandra')
      {
      Zend_Loader::loadClass( "connection", BASE_PATH . '/library/phpcassa');
      Zend_Loader::loadClass( "columnfamily", BASE_PATH . '/library/phpcassa');
          
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
      if ($config->error->php == 1)
        {
         new Zend_Log_Formatter_Simple();
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 'on');
        Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);
        $logger = Zend_Log::factory(array(
          array(
            'writerName' => 'Stream',
            'writerParams' => array(
              'stream' => './log/dev.log',
            ),
            'filterName' => 'Priority',
            'filterParams' => array(
              'priority' => Zend_Log::INFO,
            ),
          ),
          array(
            'writerName' => 'Firebug',
            'filterName' => 'Priority',
            'filterParams' => array(
              'priority' => Zend_Log::INFO,
            ),
          ),
        ));
        }
      else
        {
        error_reporting(0);
        ini_set('display_errors', 'off');
        Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
        $logger = Zend_Log::factory(array(
          array(
            'writerName' => 'Stream',
            'writerParams' => array(
              'stream' => './log/prod.log',
            ),
            'filterName' => 'Priority',
            'filterParams' => array(
              'priority' => Zend_Log::WARN,
            ),
          ),
        ));
        }
      }
    else
      {
      $redacteur = new Zend_Log_Writer_Stream('php://output');
      $logger = new Zend_Log($redacteur);
      }
    Zend_Registry::set('logger', $logger);
    return $config;
    }
    
    
    // Dans application/Bootstrap
  protected function _initRouter()
    {
    $router = Zend_Controller_Front::getInstance()->getRouter();

    $route1 = new Zend_Controller_Router_Route(
        Zend_Registry::get('configGlobal')->webdav->path.'/:action/*',
        array(
            'controller' => 'webdav',
            'action'     => ':action'
        )
    );
    $router->addRoute('webdav', $route1);    
    
    //Init Modules
    
    $frontController = Zend_Controller_Front::getInstance();    

    $frontController->addControllerDirectory(BASE_PATH . '/core/controllers');

    // routes modules
    $listeModule = array(
        array('task')
    );

    foreach ($listeModule as $m) { 
        $route = implode('/', $m);
        $nameModule = implode('_', $m); 
        $router->addRoute("$nameModule-1", 
            new Zend_Controller_Router_Route("$route/:controller/:action/*", 
                array(
                    'module' => $nameModule,
                )
            )
        );
        $router->addRoute("$nameModule-2", 
            new Zend_Controller_Router_Route("$route/:controller/", 
                array(
                    'module' => $nameModule,
                    'action' => 'index',
                )
            )
        );
        $router->addRoute("$nameModule-3", 
            new Zend_Controller_Router_Route("$route/", 
                array(
                    'module' => $nameModule,
                    'controller' => 'index',
                    'action' => 'index',
                )
            )
        );
        $frontController->addControllerDirectory(BASE_PATH . "/modules/$route/constrollers", $nameModule);
    }
    
    return $router;
    }
  }

