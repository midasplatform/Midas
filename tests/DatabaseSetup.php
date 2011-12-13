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



function getSqlDbTypes($testConfigDir)
  {
  // setup testing for whichever db config testing files exist
  $d = dir($testConfigDir);
  $dbTypes = array();
  while(false !== ($entry = $d->read()))
    {
    if($entry === 'mysql.ini')
      {
      $dbTypes[] = 'mysql';
      }
    else if($entry === 'pgsql.ini')
      {
      $dbTypes[] = 'pgsql';
      }
    }
  $d->close();
  return $dbTypes;
  }


function loadDbAdapter($testConfigDir, $dbType)
  {

  // create the lockfile for this dbType
  $dbConfigFile = $testConfigDir . '/' . $dbType . '.ini';
  $lockFile = $testConfigDir . '/lock.' . $dbType . '.ini';
  copy($dbConfigFile, $lockFile);

  // load the lockfile as the test dbConfig
  if(file_exists($lockFile))
    {
    $configDatabase = new Zend_Config_Ini($lockFile, 'testing');
    }
  else
    {
    throw new Zend_Exception("Error, cannot load lockfile: ".$lockFile);
    }

  // currently only support pdo
  if ($configDatabase->database->type == 'pdo')
    {
    $db = Zend_Db::factory($configDatabase->database->adapter, array(
      'host' => $configDatabase->database->params->host,
      'username' => $configDatabase->database->params->username,
      'password' => $configDatabase->database->params->password,
      'dbname' => $configDatabase->database->params->dbname,
      'port' =>$configDatabase->database->params->port,));
    if($configDatabase->database->profiler == '1')
      {
      $db->getProfiler()->setEnabled(true);
      }
    Zend_Db_Table::setDefaultAdapter($db);
    Zend_Registry::set('dbAdapter', $db);
    Zend_Registry::set('configDatabase', $configDatabase);
    return $db;
    }
  else
    {
    throw new Zend_Exception("Database type Error. Expecting pdo but have ".$configDatabase->database->type);
    }
  }


function dropTables($db, $dbType)
  {
  $tables = $db->listTables();
  foreach($tables as $table)
    {
    if($dbType === 'mysql')
      {
      $sql = "drop table `".$table."` cascade";
      }
    else if($dbType === 'pgsql')
      {
      $sql = 'drop table "'.$table.'" cascade';
      }
    $db->query($sql);
    }
  }


function installCore($db, $dbType, $utilityComponent)
  {
  require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
  $upgradeComponent=new UpgradeComponent();
  $upgradeComponent->dir = BASE_PATH."/core/database/".$dbType;
  $upgradeComponent->init = true;

  $newestVersion = $upgradeComponent->getNewestVersion(true);

  $sqlFile = BASE_PATH."/core/database/{$dbType}/" . $newestVersion . ".sql";
  if(!isset($sqlFile) || !file_exists($sqlFile))
    {
    throw new Zend_Exception("Unable to find sql file: ".$sqlFile);
    }

  $databaseConfig = $db->getConfig();
  switch($dbType)
    {
    case 'mysql':
      $utilityComponent->run_mysql_from_file($sqlFile, $databaseConfig['host'], $databaseConfig['username'], $databaseConfig['password'], $databaseConfig['dbname'], $databaseConfig['port']);
      $upgradeDbType = "PDO_MYSQL";
      break;
    case 'pgsql':
      $utilityComponent->run_pgsql_from_file($sqlFile, $databaseConfig['host'], $databaseConfig['username'], $databaseConfig['password'], $databaseConfig['dbname'], $databaseConfig['port']);
      $upgradeDbType = "PDO_PGSQL";
      break;
    default:
      throw new Zend_Exception("Unknown db type: ".$dbType);
      break;
    }

  $upgradeComponent->initUpgrade('core', $db, $upgradeDbType);
  $upgradeComponent->upgrade(str_replace('.sql', '', basename($sqlFile)), true /* true for testing */);
  }


// want to create a default assetstore that is separate from application's
function createDefaultAssetstore()
  {
  Zend_Registry::set('models', array());
  $modelLoader = new MIDAS_ModelLoader();
  $modelLoader->loadModel('Assetstore');

  // path munging
  require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
  $testAssetstoreBase = UtilityComponent::getTempDirectory().'/test/';
  $testAssetstoreBase = str_replace('tests/../', '', $testAssetstoreBase);
  $testAssetstoreBase = str_replace('//', '/', $testAssetstoreBase);

  // create assetstore directory
  if(!is_dir($testAssetstoreBase))
    {
    mkdir($testAssetstoreBase);
    }
  $testAssetstore = $testAssetstoreBase . '/assetstore';
  if(!is_dir($testAssetstore))
    {
    mkdir($testAssetstore);
    }

  //create default assetstore in db
  require_once BASE_PATH.'/core/models/dao/AssetstoreDao.php';
  $assetstoreDao = new AssetstoreDao();
  $assetstoreDao->setName('Default');
  $assetstoreDao->setPath($testAssetstore);
  $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
  $assetstore = new AssetstoreModel();
  $assetstore->save($assetstoreDao);
  }

// install and perform upgrades on modules
//
// What to do about module config files, these should be copied into
// core/configs when a module is installed, but we can't do that
// as there are already module files there, and we may not want
// all the module files
// we could copy the existing ones somewhere, then copy them back at the end
// but i don't like that idea
// for now do nothing
function installModules($utilityComponent)
  {
  $modules = $utilityComponent->getAllModules();
  foreach($modules as $moduleName => $module)
    {
    $utilityComponent->installModule($moduleName);
    }
  }

// cleanup the lock file
function releaseLock($dbType)
  {
  if(file_exists(BASE_PATH.'/tests/configs/lock.'.$dbType.'.ini'))
    {
    rename(  BASE_PATH.'/tests/configs/lock.'.$dbType.'.ini',BASE_PATH.'/tests/configs/'.$dbType.'.ini');
    }
  }


// general setup

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
$loader = Zend_Loader_Autoloader::getInstance();
$loader->setFallbackAutoloader(true);
$loader->suppressNotFoundWarnings(false);

require_once(BASE_PATH . "/core/include.php");
define('START_TIME',microtime(true));

Zend_Session::$_unitTestEnabled = true;
Zend_Session::start();

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





// get the config properties

$configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
$configGlobal->environment = 'testing';
Zend_Registry::set('configGlobal', $configGlobal);

$config = new Zend_Config_Ini(APPLICATION_CONFIG, 'testing');
Zend_Registry::set('config', $config);








// get DB type
// for now only supporting pgsql and mysql
// get the DB type from the existing config files

$testConfigDir = BASE_PATH.'/tests/configs/';
$dbTypes = getSqlDbTypes($testConfigDir);

foreach($dbTypes as $dbType)
  {
  try
    {
    $dbAdapter = loadDbAdapter($testConfigDir, $dbType);
    dropTables($dbAdapter, $dbType);
    require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
    $utilityComponent = new UtilityComponent();

    installCore($dbAdapter, $dbType, $utilityComponent);
    createDefaultAssetstore();
    installModules($utilityComponent);

    releaseLock($dbType);
    }
  catch(Zend_Exception $ze)
    {
    echo $ze->getMessage();
    exit(1);
    }
  }

exit(0);