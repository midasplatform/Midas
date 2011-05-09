<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
define('APPLICATION_ENV', 'testing');
define('LIBRARY_PATH', realpath(dirname(__FILE__) . '/../library'));
define('TESTS_PATH', realpath(dirname(__FILE__)));
define('BASE_PATH', realpath(dirname(__FILE__)) . "/../");


$includePaths = array(LIBRARY_PATH, get_include_path());
set_include_path(implode(PATH_SEPARATOR, $includePaths));

require_once dirname(__FILE__)."/../library/Zend/Loader/Autoloader.php";
#Zend_Loader::registerAutoload();
$loader = Zend_Loader_Autoloader::getInstance();
$loader->setFallbackAutoloader(true);
$loader->suppressNotFoundWarnings(false);

require_once(BASE_PATH . "/core/include.php");
define('START_TIME',microtime(true));

Zend_Session::$_unitTestEnabled = true;
Zend_Session::start();

require_once 'core/ControllerTestCase.php';
require_once 'core/DatabaseTestCase.php';


Zend_Registry::set('logger', null);


$configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global');
Zend_Registry::set('configGlobal', $configGlobal);

$config = new Zend_Config_Ini(APPLICATION_CONFIG, 'testing');
Zend_Registry::set('config', $config);
// InitDatabase
$configDatabase = new Zend_Config_Ini(DATABASE_CONFIG, 'testing');
if ($configDatabase->database->type == 'pdo')
  {
  $db = Zend_Db::factory($configDatabase->database->adapter, array(
    'host' => $configDatabase->database->params->host,
    'username' => $configDatabase->database->params->username,
    'password' => $configDatabase->database->params->password,
    'dbname' => $configDatabase->database->params->dbname,
    'port' =>$configDatabase->database->params->port,
  )
  );
  if ($configDatabase->database->profiler == '1')
    {
    $db->getProfiler()->setEnabled(true);
    }
  Zend_Db_Table::setDefaultAdapter($db);
  Zend_Registry::set('dbAdapter', $db);
  }
elseif ($configDatabase->database->type == 'cassandra')
  {
  set_include_path('.'
  . PATH_SEPARATOR . './core/models/cassandra/'
  . PATH_SEPARATOR . get_include_path());
  }
else
  {
  throw new Zend_Exception("Database type Error. Please check the environment config file.");
  }

Zend_Registry::set('configDatabase', $configDatabase);

require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
$upgradeComponent=new UpgradeComponent();
$db = Zend_Registry::get('dbAdapter');
$dbtype = Zend_Registry::get('configDatabase')->database->adapter;

$upgradeComponent->initUpgrade('core', $db, $dbtype);
$version = Zend_Registry::get('configDatabase')->version;
if(!isset($version))
  {
  if(Zend_Registry::get('configDatabase')->database->adapter=='PDO_MYSQL')
    {
    $type='mysql';
    }
  else
    {
    $type='pgsql';
    }
  $MyDirectory = opendir(BASE_PATH."/core/database/{$type}");
  while($Entry = @readdir($MyDirectory))
    {
    if(strpos($Entry, ".sql")!=false)
      {
      $sqlFile=BASE_PATH."/core/database/{$type}/".$Entry;
      }
    }

  
  $version=str_replace('.sql', '', basename($sqlFile));
  }
$upgradeComponent->upgrade($version,true);

$logger = Zend_Log::factory(array(
  array(
    'writerName' => 'Stream',
    'writerParams' => array(
      'stream' => BASE_PATH.'/tests/log/testing.log',
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

Zend_Registry::set('logger', $logger);

if(file_exists(BASE_PATH.'/tests/configs/lock.pgsql.ini'))
  {
  unlink(BASE_PATH.'/tests/configs/pgsql.ini');
  unlink(BASE_PATH.'/tests/configs/lock.pgsql.ini');
  rename(  BASE_PATH.'/core/configs/database.local.ini',BASE_PATH.'/tests/configs/pgsql.ini');
  rename(  BASE_PATH.'/core/configs/database.local.ini.test',BASE_PATH.'/core/configs/database.local.ini');
  }
if(file_exists(BASE_PATH.'/tests/configs/lock.mysql.ini'))
  {
  unlink(BASE_PATH.'/tests/configs/mysql.ini');
  unlink(BASE_PATH.'/tests/configs/lock.mysql.ini');
  rename(  BASE_PATH.'/core/configs/database.local.ini',BASE_PATH.'/tests/configs/mysql.ini');
  rename(  BASE_PATH.'/core/configs/database.local.ini.test',BASE_PATH.'/core/configs/database.local.ini');
  }

