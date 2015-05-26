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
 * Get database types.
 *
 * @param string $testConfigDir
 * @return array
 */
function getSqlDbTypes($testConfigDir)
{
    // setup testing for whichever db config testing files exist
    $d = dir($testConfigDir);
    $dbTypes = array();
    while (false !== ($entry = $d->read())) {
        if ($entry === 'mysql.ini') {
            $dbTypes[] = 'mysql';
        } elseif ($entry === 'pgsql.ini') {
            $dbTypes[] = 'pgsql';
        } elseif ($entry === 'sqlite.ini') {
            $dbTypes[] = 'sqlite';
        }
    }
    $d->close();

    return $dbTypes;
}

/**
 * Load database adapter.
 *
 * @param string $testConfigDir
 * @param string $dbType
 * @return Zend_Db_Adapter_Abstract
 * @throws Zend_Exception
 */
function loadDbAdapter($testConfigDir, $dbType)
{
    // create the lockfile for this dbType
    $dbConfigFile = $testConfigDir.'/'.$dbType.'.ini';
    $lockFile = $testConfigDir.'/lock.'.$dbType.'.ini';
    copy($dbConfigFile, $lockFile);

    // load the lockfile as the test dbConfig
    if (file_exists($lockFile)) {
        $configDatabase = new Zend_Config_Ini($lockFile, 'testing');
    } else {
        throw new Zend_Exception('Error, cannot load lockfile: '.$lockFile);
    }

    if (empty($configDatabase->database->params->driver_options)) {
        $driverOptions = array();
    } else {
        $driverOptions = $configDatabase->database->params->driver_options->toArray();
    }
    $params = array(
        'dbname' => $configDatabase->database->params->dbname,
        'driver_options' => $driverOptions,
    );
    if ($dbType != 'sqlite') {
        $params['username'] = $configDatabase->database->params->username;
        $params['password'] = $configDatabase->database->params->password;
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

    return $db;
}

/**
 * Drop database tables.
 *
 * @param Zend_Db_Adapter_Abstract $db
 * @param string $dbType
 * @throws Zend_Exception
 */
function dropTables($db, $dbType)
{
    $db->beginTransaction();
    try {
        $tables = $db->listTables();
        foreach ($tables as $table) {
            if ($dbType === 'mysql') {
                $db->query('DROP TABLE IF EXISTS `'.$table.'` CASCADE;');
            } elseif ($dbType === 'pgsql') {
                $db->query('DROP TABLE IF EXISTS "'.$table.'" CASCADE;');
            } elseif ($dbType === 'sqlite' && $table != 'sqlite_sequence') {
                $db->query('DROP TABLE IF EXISTS "'.$table.'";');
            } else {
                continue;
            }
        }
        $db->commit();
    } catch (Zend_Exception $exception) {
        $db->rollBack();
        throw $exception;
    }
}

/**
 * Install and upgrade core.
 *
 * @param Zend_Db_Adapter_Abstract $db
 * @param string $dbType
 * @param UtilityComponent $utilityComponent
 * @throws Zend_Exception
 */
function installCore($db, $dbType, $utilityComponent)
{
    require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
    $upgradeComponent = new UpgradeComponent();
    $upgradeComponent->dir = BASE_PATH.'/core/database/'.$dbType;
    $upgradeComponent->init = true;

    $newestVersion = $upgradeComponent->getNewestVersion(true);

    $sqlFile = BASE_PATH.'/core/database/'.$dbType.'/'.$newestVersion.'.sql';
    if (!isset($sqlFile) || !file_exists($sqlFile)) {
        throw new Zend_Exception('Unable to find SQL file: '.$sqlFile);
    }

    switch ($dbType) {
        case 'mysql':
            $utilityComponent->run_sql_from_file($db, $sqlFile);
            $upgradeDbType = 'PDO_MYSQL';
            break;
        case 'pgsql':
            $utilityComponent->run_sql_from_file($db, $sqlFile);
            $upgradeDbType = 'PDO_PGSQL';
            break;
        case 'sqlite':
            $utilityComponent->run_sql_from_file($db, $sqlFile);
            $upgradeDbType = 'PDO_SQLITE';
            break;
        default:
            throw new Zend_Exception('Unknown database type: '.$dbType);
            break;
    }

    $options = array('allowModifications' => true);
    $databaseConfig = new Zend_Config_Ini(BASE_PATH.'/tests/configs/lock.'.$dbType.'.ini', null, $options);
    $databaseConfig->testing->version = $newestVersion;
    $writer = new Zend_Config_Writer_Ini();
    $writer->setConfig($databaseConfig);
    $writer->setFilename(BASE_PATH.'/tests/configs/lock.'.$dbType.'.ini');
    $writer->write();

    $upgradeComponent->initUpgrade('core', $db, $upgradeDbType);
    $upgradeComponent->upgrade(str_replace('.sql', '', basename($sqlFile)), true /* true for testing */);
}

/** Create default asset store. */
function createDefaultAssetstore()
{
    Zend_Registry::set('models', array());

    /** @var AssetstoreModel $assetStoreModel */
    $assetStoreModel = MidasLoader::loadModel('Assetstore');

    // path munging
    require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
    $testAssetstoreBase = UtilityComponent::getTempDirectory().'/test/';
    $testAssetstoreBase = str_replace('tests/../', '', $testAssetstoreBase);
    $testAssetstoreBase = str_replace('//', '/', $testAssetstoreBase);

    // create assetstore directory
    if (!is_dir($testAssetstoreBase)) {
        mkdir($testAssetstoreBase);
    }
    $testAssetstore = $testAssetstoreBase.'/assetstore';
    if (!is_dir($testAssetstore)) {
        mkdir($testAssetstore);
    }

    // create default assetstore in db
    require_once BASE_PATH.'/core/models/dao/AssetstoreDao.php';
    $assetstoreDao = new AssetstoreDao();
    $assetstoreDao->setName('Default');
    $assetstoreDao->setPath($testAssetstore);
    $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
    $assetStoreModel->save($assetstoreDao);
}

/**
 * Install and upgrade modules.
 *
 * @param UtilityComponent $utilityComponent
 */
function installModules($utilityComponent)
{
    // What to do about module config files, these should be copied into
    // core/configs when a module is installed, but we can't do that
    // as there are already module files there, and we may not want
    // all the module files
    // we could copy the existing ones somewhere, then copy them back at the end
    // but I don't like that idea
    // for now do nothing
    $modules = $utilityComponent->getAllModules();
    foreach ($modules as $moduleName => $module) {
        $utilityComponent->installModule($moduleName);
    }
}

/**
 * Release lock file.
 *
 * @param string $dbType
 */
function releaseLock($dbType)
{
    if (file_exists(BASE_PATH.'/tests/configs/lock.'.$dbType.'.ini')) {
        rename(BASE_PATH.'/tests/configs/lock.'.$dbType.'.ini', BASE_PATH.'/tests/configs/'.$dbType.'.ini');
    }
}

// general setup
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('BASE_PATH', realpath(dirname(__FILE__).'/../'));
define('APPLICATION_ENV', 'testing');
define('APPLICATION_PATH', BASE_PATH.'/core');
define('LIBRARY_PATH', BASE_PATH.'/library');
define('TESTS_PATH', BASE_PATH.'/tests');

require_once BASE_PATH.'/vendor/autoload.php';
require_once BASE_PATH.'/core/include.php';

Zend_Session::$_unitTestEnabled = true;
Zend_Session::start();

$logger = Zend_Log::factory(
    array(
        array(
            'writerName' => 'Stream',
            'writerParams' => array('stream' => LOGS_PATH.'/testing.log'),
            'filterName' => 'Priority',
            'filterParams' => array('priority' => Zend_Log::DEBUG),
        ),
    )
);

Zend_Registry::set('logger', $logger);

// get the config properties
$configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
$configGlobal->environment = 'testing';
Zend_Registry::set('configGlobal', $configGlobal);

$config = new Zend_Config_Ini(APPLICATION_CONFIG, 'testing');
Zend_Registry::set('config', $config);

// get database type
// for now only supporting MySQL, PostgreSQL, and SQLite
// get the database type from the existing config files
$testConfigDir = BASE_PATH.'/tests/configs/';
$dbTypes = getSqlDbTypes($testConfigDir);

foreach ($dbTypes as $dbType) {
    try {
        echo 'Dropping and installing tables for database type: '.$dbType.PHP_EOL;
        $dbAdapter = loadDbAdapter($testConfigDir, $dbType);
        dropTables($dbAdapter, $dbType);
        require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
        $utilityComponent = new UtilityComponent();

        installCore($dbAdapter, $dbType, $utilityComponent);
        createDefaultAssetstore();
        installModules($utilityComponent);

        releaseLock($dbType);
    } catch (Zend_Exception $ze) {
        echo $ze->getMessage();
        exit(1);
    }
}

exit(0);
