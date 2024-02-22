<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /** Send the HTML DOCTYPE to the view. */
    protected function _initDoctype()
    {
        $this->bootstrap('view');

        /** @var Zend_View $view */
        $view = $this->getResource('View');
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
        $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
        Zend_Registry::set('configGlobal', $configGlobal);

        $configCore = new Zend_Config_Ini(CORE_CONFIG, 'global', true);
        Zend_Registry::set('configCore', $configCore);

        $config = new Zend_Config_Ini(APPLICATION_CONFIG, $configGlobal->get('environment', 'production'), true);
        Zend_Registry::set('config', $config);

        return $config;
    }

    /**
     * Initialize the database.
     *
     * @return false|Zend_Db_Adapter_Abstract
     * @throws Zend_Exception
     */
    protected function _initDatabase()
    {
        $this->bootstrap('Config');
        $config = new Zend_Config_Ini(DATABASE_CONFIG, Zend_Registry::get('configGlobal')->get('environment', 'production'), true);
        Zend_Registry::set('configDatabase', $config);

        if (empty($config->database->params->driver_options)) {
            $driverOptions = array();
        } else {
            $driverOptions = $config->database->params->driver_options->toArray();
        }

        if ($config->database->adapter === 'PDO_SQLITE') {
            $params = array(
                'dbname' => $config->database->params->dbname,
                'driver_options' => $driverOptions,
            );
        } else {
            if ($config->database->adapter === 'PDO_MYSQL') {
                $driverOptions[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
            }

            $params = array(
                'dbname' => $config->database->params->dbname,
                'username' => $config->database->params->username,
                'password' => $config->database->params->password,
                'driver_options' => $driverOptions,
                'profiler' => array_key_exists('profiler', $_GET),
            );

            if (empty($config->database->params->unix_socket)) {
                $params['host'] = $config->database->params->host;
                $params['port'] = $config->database->params->port;
            } else {
                $params['unix_socket'] = $config->database->params->unix_socket;
            }
        }

        $database = Zend_Db::factory($config->database->adapter, $params);
        Zend_Db_Table::setDefaultAdapter($database);
        Zend_Registry::set('dbAdapter', $database);
        Zend_Registry::set('models', array());

        if (file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')) {
            return $database;
        }

        return false;
    }

    /**
     * Initialize the cache.
     *
     * @return false|Zend_Cache_Core
     */
    protected function _initCache()
    {
        $this->bootstrap('Config', 'Database');
        $cache = false;

        if (Zend_Registry::get('configGlobal')->get('environment', 'production') === 'production') {
            $frontendOptions = array(
                'automatic_serialization' => true,
                'lifetime' => 86400,
                'cache_id_prefix' => 'midas_',
            );

            if (extension_loaded('memcached') || session_save_path() === 'Memcache') {
                $cache = Zend_Cache::factory('Core', 'Libmemcached', $frontendOptions, array());
            } elseif (extension_loaded('memcache')) {
                $cache = Zend_Cache::factory('Core', 'Memcached', $frontendOptions, array());
            } else {
                $cacheDir = UtilityComponent::getCacheDirectory().'/db';

                if (is_dir($cacheDir) && is_writable($cacheDir)) {
                    $backendOptions = array('cache_dir' => $cacheDir);
                    $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
                }
            }

            if ($cache !== false) {
                Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
            }
        }

        Zend_Registry::set('cache', $cache);

        return $cache;
    }

    /** Initialize the error handler. */
    protected function _initErrorHandle()
    {
        $this->bootstrap(array('Config', 'Logger'));

        /** @var Zend_Log $logger */
        $logger = $this->getResource('Logger');

        Zend_Registry::set('components', array());
        $notifyErrorComponent = MidasLoader::loadComponent('NotifyError');

        register_shutdown_function(array($notifyErrorComponent, 'fatalError'), $logger);
        set_error_handler(array($notifyErrorComponent, 'warningError'), E_NOTICE | E_WARNING);
    }

    /**
     * Initialize internationalization.
     *
     * @throws Zend_Exception
     */
    protected function _initInternationalization()
    {
        $this->bootstrap(array('Cache', 'Config', 'Database', 'FrontController'));

        /** @var false|Zend_Db_Adapter_Abstract $database */
        $database = $this->getResource('Database');

        if ((int) Zend_Registry::get('configGlobal')->get('internationalization', 0) === 1) {
            $language = 'en';

            if (isset($_COOKIE[MIDAS_LANGUAGE_COOKIE_NAME])) {
                $language = $_COOKIE[MIDAS_LANGUAGE_COOKIE_NAME];
            }

            if (isset($_GET['lang'])) {
                $language = $_GET['lang'];
                if ($language !== 'en' && $language !== 'fr') {
                    $language = 'en';
                }

                /** @var Zend_Controller_Front $frontController */
                $frontController = $this->getResource('FrontController');

                /** @var Zend_Controller_Request_Http $request */
                $request = $frontController->getRequest();

                $date = new DateTime();
                $interval = new DateInterval('P1M');
                $expires = $date->add($interval);

                UtilityComponent::setCookie($request, MIDAS_LANGUAGE_COOKIE_NAME, $language, $expires);
            }

            if ($database !== false) {
                /** @var SettingModel $settingModel */
                $settingModel = MidasLoader::loadModel('Setting');
                $settingModel->setConfig('language', $language);
            }
        }

        if ($database !== false) {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');
            $timeZone = $settingModel->getValueByNameWithDefault('time_zone', 'UTC');
        } else {
            $timeZone = 'UTC';
        }

        date_default_timezone_set($timeZone);
    }

    /**
     * Initialize the logger.
     *
     * @return Zend_Log
     * @throws Zend_Log_Exception
     */
    protected function _initLogger()
    {
        $this->bootstrap('Config');

        if (Zend_Registry::get('configGlobal')->get('environment', 'production') === 'production') {
            Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(true);
            $priority = Zend_Log::WARN;
        } else {
            Zend_Loader_Autoloader::getInstance()->suppressNotFoundWarnings(false);
            $priority = Zend_Log::DEBUG;
        }

        if (is_writable(LOGS_PATH)) {
            $stream = LOGS_PATH.'/'.Zend_Registry::get('configGlobal')->get('environment', 'production').'.log';
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
        $logger->setEventItem('module', 'core');
        $logger->registerErrorHandler();
        Zend_Registry::set('logger', $logger);

        return $logger;
    }

    /**
     * Display the debug toolbar, if enabled.
     *
     * @throws Zend_Exception
     */
    protected function _initZFDebug()
    {
        $this->bootstrap(array('Config', 'FrontController'));
        $zfDebugPath = BASE_PATH.'/vendor/jokkedk/zfdebug/library';

        if ((int) Zend_Registry::get('configGlobal')->get('debug_toolbar', 0) === 1 && file_exists($zfDebugPath)) {
            set_include_path(get_include_path().PATH_SEPARATOR.$zfDebugPath);

            $options = array(
                'plugins' => array('Variables',
                    'Database' => array('adapter' => Zend_Registry::get('dbAdapter')),
                    'Exception',
                    'File' => array('basePath' => BASE_PATH),
                    'Html',
                ),
            );

            $debug = new ZFDebug_Controller_Plugin_Debug($options);

            /** @var Zend_Controller_Front $frontController */
            $frontController = $this->getResource('FrontController');
            $frontController->registerPlugin($debug);
        }
    }

    /** Register the module directories. */
    protected function _initFrontModules()
    {
        $this->bootstrap('FrontController');

        /** @var Zend_Controller_Front $frontController */
        $frontController = $this->getResource('FrontController');
        $frontController->addModuleDirectory(BASE_PATH.'/modules');

        if (file_exists(BASE_PATH.'/privateModules')) {
            $frontController->addModuleDirectory(BASE_PATH.'/privateModules');
        }
    }

    /**
     * Initialize the SASS compiler.
     *
     * @throws Zend_Exception
     */
    protected function _initSass()
    {
        $this->bootstrap(array('Config', 'Logger'));
        $config = Zend_Registry::get('configGlobal');

        /** @var Zend_Log $logger */
        $logger = $this->getResource('Logger');
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
     * @throws Zend_Exception
     */
    protected function _initRouter()
    {
        $this->bootstrap(array('Cache', 'Config', 'Database', 'FrontController'));

        /** @var Zend_Controller_Front $frontController */
        $frontController = $this->getResource('FrontController');
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers');

        require_once BASE_PATH.'/core/ApiController.php';
        $frontController->addControllerDirectory(BASE_PATH.'/core/controllers/api', 'rest');

        $router = $frontController->getRouter();
        $router->addRoute('api-core', new Zend_Rest_Route($frontController, array(), array('rest')));

        $enabledModules = array();

        if (isset(Zend_Registry::get('configDatabase')->version) === false) {
            Zend_Registry::set('models', array());

            try {
                /** @var ModuleModel $moduleModel */
                $moduleModel = MidasLoader::loadModel('Module');
                $moduleDaos = $moduleModel->getEnabled();
            } catch (Zend_Db_Exception $exception) {
                $moduleDaos = array();
            }

            /** @var ModuleDao $moduleDao */
            foreach ($moduleDaos as $moduleDao) {
                $enabledModules[] = $moduleDao->getName();
            }
        } else {
            $modules = new Zend_Config_Ini(APPLICATION_CONFIG, 'module');
            $enabledModules = array_keys($modules->toArray(), 1);
        }

        $enabledApiModules = array();

        /** @var string $enabledModule */
        foreach ($enabledModules as $enabledModule) {
            if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/AppController.php')) {
                $moduleRoot = BASE_PATH.'/modules/'.$enabledModule;
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$enabledModule.'/AppController.php')) {
                $moduleRoot = BASE_PATH.'/privateModules/'.$enabledModule;
            } else {
                throw new Zend_Exception('Module '.$enabledModule.'" does not exist.');
            }

            $frontController->addControllerDirectory($moduleRoot.'/controllers', $enabledModule);

            if (file_exists($moduleRoot.'/constant/module.php')) {
                require_once $moduleRoot.'/constant/module.php';
            }

            if (file_exists($moduleRoot.'/AppController.php')) {
                require_once $moduleRoot.'/AppController.php';
            }

            if (file_exists($moduleRoot.'/models/AppDao.php')) {
                require_once $moduleRoot.'/models/AppDao.php';
            }

            if (file_exists($moduleRoot.'/models/AppModel.php')) {
                require_once $moduleRoot.'/models/AppModel.php';
            }

            if (file_exists($moduleRoot.'/controllers/api')) {
                $frontController->addControllerDirectory($moduleRoot.'/controllers/api', 'api'.$enabledModule);
                $enabledApiModules[] = $enabledModule;
            }

            $router->addRoute($enabledModule.'-1', new Zend_Controller_Router_Route($enabledModule.'/:controller/:action/*', array('module' => $enabledModule)));
            $router->addRoute($enabledModule.'-2', new Zend_Controller_Router_Route($enabledModule.'/:controller/', array('module' => $enabledModule, 'action' => 'index')));
            $router->addRoute($enabledModule.'-3', new Zend_Controller_Router_Route($enabledModule.'/', array('module' => $enabledModule, 'controller' => 'index', 'action' => 'index')));

            $baseModels = $moduleRoot.'/models/base';

            if (is_dir($baseModels)) {
                $fileNames = array_diff(scandir($baseModels), array('..', '.'));

                /** @var string $fileName */
                foreach ($fileNames as $fileName) {
                    if (filetype($baseModels.'/'.$fileName) != 'dir') {
                        require_once $baseModels.'/'.$fileName;
                    }
                }
            }
        }

        Zend_Registry::set('modulesEnable', $enabledModules);
        Zend_Registry::set('modulesHaveApi', $enabledApiModules);

        return $router;
    }

    /** Register the plugins and helpers for the REST controllers. */
    protected function _initREST()
    {
        $this->bootstrap('FrontController');

        /** @var Zend_Controller_Front $frontController */
        $frontController = $this->getResource('FrontController');

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
            'cookie_secure' => (int) $config->get('cookie_secure', 1) === 1,
            'gc_maxlifetime' => 600,
        );
        Zend_Session::setOptions($options);
    }
}
