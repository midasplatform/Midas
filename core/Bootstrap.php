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
 * Bootstrap. Provides common functionality including dependency checking
 * algorithms and the ability to load bootstrap resources on demand.
 *
 * @package Core
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /** Send the HTML DOCTYPE to the view. */
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
    }

    /**
     * Load the configuration files into the Zend registry.
     *
     * @return Zend_Config_Ini configuration file
     * @throws Zend_Exception
     */
    protected function _initConfig()
    {
        // init language
        $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);

        if (isset($_COOKIE[MIDAS_LANGUAGE_COOKIE_NAME])) {
            $configGlobal->application->lang = $_COOKIE[MIDAS_LANGUAGE_COOKIE_NAME];
        }

        if (isset($_GET['lang'])) {
            $language = $_GET['lang'];
            if ($language !== 'en' && $language !== 'fr') {
                $language = 'en';
            }
            $configGlobal->application->lang = $language;
            $date = new DateTime();
            $interval = new DateInterval('P1M');
            setcookie(
                MIDAS_LANGUAGE_COOKIE_NAME,
                $language,
                $date->add($interval)->getTimestamp(),
                '/',
                !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'],
                (int) $configGlobal->get('cookie_secure', 0) === 1,
                true
            );
        }

        Zend_Registry::set('configGlobal', $configGlobal);

        $configCore = new Zend_Config_Ini(CORE_CONFIG, 'global', true);
        Zend_Registry::set('configCore', $configCore);

        // check if internationalization enabled
        if (isset($configCore->internationalization) && $configCore->internationalization == "0") {
            $configGlobal->application->lang = "en";
        }

        $config = new Zend_Config_Ini(APPLICATION_CONFIG, $configGlobal->environment, true);
        Zend_Registry::set('config', $config);
        date_default_timezone_set($configGlobal->default->timezone);

        // InitDatabase
        $configDatabase = new Zend_Config_Ini(DATABASE_CONFIG, $configGlobal->environment, true);
        if (empty($configDatabase->database->params->driver_options)) {
            $driverOptions = array();
        } else {
            $driverOptions = $configDatabase->database->params->driver_options->toArray();
        }

        if ($configDatabase->database->adapter == 'PDO_SQLITE') {
            $params = array(
                'dbname' => $configDatabase->database->params->dbname,
                'driver_options' => $driverOptions,
            );
        } else {
            if ($configDatabase->database->adapter == 'PDO_MYSQL') {
                $driverOptions[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
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
        }

        $db = Zend_Db::factory($configDatabase->database->adapter, $params);
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('dbAdapter', $db);
        Zend_Registry::set('configDatabase', $configDatabase);

        // Init log
        if ($configGlobal->environment == 'production') {
            Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
            $priority = Zend_Log::WARN;
        } else {
            Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);
            $priority = Zend_Log::DEBUG;
        }

        if (is_writable(LOGS_PATH)) {
            $stream = LOGS_PATH.'/'.$configGlobal->environment.'.log';
            $logger = Zend_Log::factory(
                array(
                    array(
                        'writerName' => 'Stream',
                        'writerParams' => array('stream' => $stream),
                        'formatterName' => 'Simple',
                        'filterName' => 'Priority',
                        'filterParams' => array('priority' => $priority),
                    ),
                )
            );
        } else {
            $logger = Zend_Log::factory(
                array(
                    array(
                        'writerName' => 'Syslog',
                        'formatterName' => 'Simple',
                        'filterName' => 'Priority',
                        'filterParams' => array('priority' => $priority),
                    ),
                )
            );
        }
        if (file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')) {
            $columnMapping = array('priority' => 'priority', 'message' => 'message', 'module' => 'module');
            $writer = new Zend_Log_Writer_Db($db, 'errorlog', $columnMapping);
            if ($configGlobal->environment == 'production') {
                $priority = Zend_Log::INFO;
            } else {
                $priority = Zend_Log::DEBUG;
            }
            $filter = new Zend_Log_Filter_Priority($priority);
            $writer->addFilter($filter);
            $logger->addWriter($writer);
        }
        $logger->setEventItem('module', 'core');
        $logger->registerErrorHandler();
        Zend_Registry::set('logger', $logger);

        // Init error handler
        require_once BASE_PATH.'/core/controllers/components/NotifyErrorComponent.php';
        $notifyErrorComponent = new NotifyErrorComponent();
        ini_set('display_errors', 0);
        register_shutdown_function(array($notifyErrorComponent, 'fatalError'), $logger);
        set_error_handler(array($notifyErrorComponent, 'warningError'), E_NOTICE | E_WARNING);

        return $config;
    }

    /** Display the debug toolbar, if enabled. */
    protected function _initZFDebug()
    {
        $this->bootstrap('config');
        $zfDebugPath = BASE_PATH.'/vendor/jokkedk/zfdebug/library';

        if (Zend_Registry::get('configGlobal')->debug_toolbar === '1' && file_exists($zfDebugPath)) {
            set_include_path(get_include_path().PATH_SEPARATOR.$zfDebugPath);

            $options = array(
                'plugins' => array('Variables',
                    'Database' => array('adapter' => Zend_Registry::get('dbAdapter')),
                    'Exception',
                    'File' => array('basePath' => BASE_PATH),
                    'Html',
                )
            );

            $debug = new ZFDebug_Controller_Plugin_Debug($options);

            $this->bootstrap('frontController');
            $frontController = $this->getResource('frontController');
            $frontController->registerPlugin($debug);
        }
    }

    /** Register the module directories. */
    protected function _initFrontModules()
    {
        $this->bootstrap('frontController');
        $front = $this->getResource('frontController');
        $front->addModuleDirectory(BASE_PATH.'/modules');
        if (file_exists(BASE_PATH.'/privateModules')) {
            $front->addModuleDirectory(BASE_PATH.'/privateModules');
        }
    }

    /** Initialize the SASS compiler. */
    protected function _initSass()
    {
        $this->bootstrap('Config');
        $config = Zend_Registry::get('configGlobal');
        $logger = Zend_Registry::get('logger');
        if ($config->environment == 'development') {
            $directory = new RecursiveDirectoryIterator(BASE_PATH);
            $iterator = new RecursiveIteratorIterator(
                $directory,
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            $regex = new RegexIterator(
                $iterator,
                '#(?:core|(?:modules|privateModules)/.*)/public/scss/(?!mixins).*\.scss$#',
                RegexIterator::GET_MATCH
            );
            $scssPaths = array();
            foreach ($regex as $scssPath) {
                $scssPaths = array_merge($scssPaths, $scssPath);
            }
            $scssc = new Leafo\ScssPhp\Compiler();
            $scssc->setImportPaths(
                array(BASE_PATH.'/core/public/scss/mixins', BASE_PATH.'/core/public/scss/mixins/bourbon')
            );
            $scssc->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
            foreach ($scssPaths as $scssPath) {
                $cssPath = preg_replace(
                    '#((?:core|(?:modules|privateModules)/.*)/public)/scss/(.*)\.scss$#',
                    '\1/css/\2.css',
                    $scssPath
                );
                if (!file_exists($cssPath) || filemtime($cssPath) < filemtime($scssPath)
                ) {
                    $cssDirectoryName = pathinfo($cssPath, PATHINFO_DIRNAME);
                    if (!file_exists($cssDirectoryName)) {
                        $level = error_reporting(0);
                        mkdir($cssDirectoryName, 0755, true);
                        error_reporting($level);
                    }
                    if (is_dir($cssDirectoryName) && is_writable($cssDirectoryName)
                    ) {
                        $scss = file_get_contents($scssPath);
                        $css = $scssc->compile($scss).PHP_EOL;
                        file_put_contents($cssPath, $css);
                    } else {
                        $logger->debug('Could not compile SASS located at '.$scssPath);
                    }
                }
            }
        }
    }

    /**
     * Initialize the router.
     *
     * @return Zend_Controller_Router_Interface
     */
    protected function _initRouter()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();

        // Init Modules
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers');

        $modules = new Zend_Config_Ini(APPLICATION_CONFIG, 'module');
        // routes modules
        $listeModule = array();
        $apiModules = array();
        foreach ($modules as $key => $module) {
            if ($module == 1 && file_exists(BASE_PATH.'/modules/'.$key) && file_exists(
                    BASE_PATH."/modules/".$key."/AppController.php"
                )
            ) {
                $listeModule[] = $key;
                // get web API controller directories and web API module names for enabled modules
                if (file_exists(BASE_PATH."/modules/".$key."/controllers/api")) {
                    $frontController->addControllerDirectory(
                        BASE_PATH."/modules/".$key."/controllers/api",
                        "api".$key
                    );
                    $apiModules[] = $key;
                }
            } elseif ($module == 1 && file_exists(BASE_PATH.'/privateModules/'.$key) && file_exists(
                    BASE_PATH."/privateModules/".$key."/AppController.php"
                )
            ) {
                $listeModule[] = $key;
                // get web API controller directories and web API module names for enabled modules
                if (file_exists(BASE_PATH."/privateModules/".$key."/controllers/api")) {
                    $frontController->addControllerDirectory(
                        BASE_PATH."/privateModules/".$key."/controllers/api",
                        "api".$key
                    );
                    $apiModules[] = $key;
                }
            }
        }

        // get web API controller directory for core APIs
        require_once BASE_PATH."/core/ApiController.php";
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers/api', 'rest');
        // add RESTful route for web APIs
        $restRoute = new Zend_Rest_Route($frontController, array(), array('rest'));
        $router->addRoute('api-core', $restRoute);
        // loading modules elements
        foreach ($listeModule as $m) {
            $route = $m;
            $nameModule = $m;
            $router->addRoute(
                $nameModule."-1",
                new Zend_Controller_Router_Route(
                    "".$route."/:controller/:action/*", array('module' => $nameModule)
                )
            );
            $router->addRoute(
                $nameModule."-2",
                new Zend_Controller_Router_Route(
                    "".$route."/:controller/",
                    array('module' => $nameModule, 'action' => 'index')
                )
            );
            $router->addRoute(
                $nameModule."-3",
                new Zend_Controller_Router_Route(
                    "".$route."/",
                    array('module' => $nameModule, 'controller' => 'index', 'action' => 'index')
                )
            );

            if (file_exists(BASE_PATH."/modules/".$route."/AppController.php")) {
                require_once BASE_PATH."/modules/".$route."/AppController.php";
            }
            if (file_exists(BASE_PATH."/modules/".$route."/models/AppDao.php")) {
                require_once BASE_PATH."/modules/".$route."/models/AppDao.php";
            }
            if (file_exists(BASE_PATH."/modules/".$route."/models/AppModel.php")) {
                require_once BASE_PATH."/modules/".$route."/models/AppModel.php";
            }
            if (file_exists(BASE_PATH."/modules/".$route."/constant/module.php")) {
                require_once BASE_PATH."/modules/".$route."/constant/module.php";
            }

            if (file_exists(BASE_PATH."/privateModules/".$route."/AppController.php")) {
                require_once BASE_PATH."/privateModules/".$route."/AppController.php";
            }
            if (file_exists(BASE_PATH."/privateModules/".$route."/models/AppDao.php")) {
                require_once BASE_PATH."/privateModules/".$route."/models/AppDao.php";
            }
            if (file_exists(BASE_PATH."/privateModules/".$route."/models/AppModel.php")) {
                require_once BASE_PATH."/privateModules/".$route."/models/AppModel.php";
            }
            if (file_exists(BASE_PATH."/privateModules/".$route."/constant/module.php")) {
                require_once BASE_PATH."/privateModules/".$route."/constant/module.php";
            }

            $dir = BASE_PATH."/modules/".$route."/models/base";
            if (!is_dir($dir)) {
                $dir = BASE_PATH."/privateModules/".$route."/models/base";
            }

            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (filetype($dir."/".$object) != "dir") {
                            require_once $dir."/".$object;
                        }
                    }
                }
            }
        }
        Zend_Registry::set('modulesEnable', $listeModule);
        Zend_Registry::set('modulesHaveApi', $apiModules);

        return $router;
    }

    /** Register the plugins and helpers for the REST controllers. */
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

    /** Configure the session. */
    protected function _initSession()
    {
        $this->bootstrap('Config');
        $config = Zend_Registry::get('configGlobal');
        $options = array(
            'cookie_httponly' => true,
            'cookie_secure' => (int) $config->get('cookie_secure', 0) === 1,
            'gc_maxlifetime' => 600,
        );
        Zend_Session::setOptions($options);
    }
}
