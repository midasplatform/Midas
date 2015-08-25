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

/** Upgrade Midas Server */
class UpgradeComponent extends AppComponent
{
    /** @var string */
    public $dir;

    /** @var string */
    protected $module;

    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var string */
    protected $dbtype;

    /** @var string */
    protected $dbtypeShort;

    /** @var bool */
    public $init = false;

    /**
     * Initialize the upgrade component.
     *
     * @param string $module
     * @param Zend_Db_Adapter_Abstract $db
     * @param string $dbtype
     * @throws Zend_Exception
     */
    public function initUpgrade($module, $db, $dbtype)
    {
        if ($module === 'core') {
            $this->dir = BASE_PATH.'/core/database/upgrade';
        } elseif (file_exists(BASE_PATH.'/privateModules/'.$module.'/database/upgrade')) {
            $this->dir = BASE_PATH.'/privateModules/'.$module.'/database/upgrade';
        } else {
            $this->dir = BASE_PATH.'/modules/'.$module.'/database/upgrade';
        }

        $this->db = $db;
        $this->module = $module;
        $this->dbtype = $dbtype;
        switch ($dbtype) {
            case 'PDO_MYSQL':
                $this->dbtypeShort = 'mysql';
                break;
            case 'PDO_PGSQL':
                $this->dbtypeShort = 'pgsql';
                break;
            case 'PDO_SQLITE':
                $this->dbtypeShort = 'sqlite';
                break;
            default:
                throw new Zend_Exception('Unknown database type');
                break;
        }
        $this->init = true;
    }

    /**
     * Get newest version.
     *
     * @param bool $text
     * @return int|string
     * @throws Zend_Exception
     */
    public function getNewestVersion($text = false)
    {
        if ($this->init === false) {
            throw new Zend_Exception('Upgrade component is not initialized.');
        }

        $files = $this->getMigrationFiles();
        if (empty($files)) {
            return 0;
        }
        $version = '';
        foreach ($files as $key => $f) {
            $version = $key;
            if ($text) {
                $version = $f['versionText'];
            }
        }

        return $version;
    }

    /**
     * Get all migration files.
     *
     * @return array
     * @throws Zend_Exception
     */
    public function getMigrationFiles()
    {
        if ($this->init === false) {
            throw new Zend_Exception('Upgrade component is not initialized.');
        }

        $files = array();
        if (file_exists($this->dir)) {
            $d = dir($this->dir);
            while (false !== ($entry = $d->read())) {
                if (preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)\.php$/i', $entry, $matches)) {
                    $versionText = basename(str_replace('.php', '', $entry));
                    $versionNumber = $this->transformVersionToNumeric($versionText);
                    $files[$versionNumber] = array(
                        'filename' => $entry,
                        'version' => $versionNumber,
                        'versionText' => $versionText,
                    );
                }
                if (preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)\.sql$/i', $entry, $matches)) {
                    $versionText = basename(str_replace('.sql', '', $entry));
                    $versionNumber = $this->transformVersionToNumeric($versionText);
                    $files[$versionNumber] = array(
                        'filename' => $entry,
                        'version' => $versionNumber,
                        'versionText' => $versionText,
                    );
                }
            }
            $d->close();
        }
        ksort($files);

        return $files;
    }

    /**
     * Transform version to numeric.
     *
     * @param string $text
     * @return int
     * @throws Zend_Exception
     */
    public function transformVersionToNumeric($text)
    {
        $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $text, $matches);
        if ($result !== 1) {
            throw new Zend_Exception('Core or module version string is invalid.');
        }

        return (int) $matches[1] * 1000000 + (int) $matches[2] * 1000 + (int) $matches[3];
    }

    /**
     * Upgrade.
     *
     * @param false|null|int|string $currentVersion
     * @param bool $testing
     * @return bool
     * @throws Zend_Exception
     */
    public function upgrade($currentVersion = false, $testing = false)
    {
        if ($this->init === false) {
            throw new Zend_Exception('Upgrade component is not initialized.');
        }

        if (is_null($currentVersion) || $currentVersion === false) {
            $currentVersion = UtilityComponent::getCurrentModuleVersion($this->module);
        }
        if ($currentVersion === false) {
            throw new Zend_Exception('Core or module version is undefined.');
        }
        if (!is_numeric($currentVersion)) {
            $currentVersion = $this->transformVersionToNumeric($currentVersion);
        }

        $version = $this->getNewestVersion($text = false);

        if ($currentVersion >= $version || $version === 0) {
            return false;
        }

        $migrations = $this->getMigrationFiles();
        $versionText = false;

        /** @var array $migration */
        foreach ($migrations as $migration) {
            if ($migration['version'] > $currentVersion) {
                $this->_processFile($migration);
                $versionText = $migration['versionText'];
                Zend_Registry::get('logger')->info($versionText);
            }
        }

        if ($versionText !== false) {
            if (isset(Zend_Registry::get('configDatabase')->version) === false) {
                /** @var ModuleModel $moduleModel */
                $moduleModel = MidasLoader::loadModel('Module');
                $moduleDao = $moduleModel->getByName($this->module);
                if ($moduleDao === false) {
                    /** @var ModuleDao $moduleDao */
                    $moduleDao = MidasLoader::newDao('ModuleDao');
                    $moduleDao->setName($this->module);

                    if ($this->module === 'core') {
                        $moduleConfig = Zend_Registry::get('configCore');
                    } else {
                        /** @var UtilityComponent $utilityComponent */
                        $utilityComponent = MidasLoader::loadComponent('Utility');
                        $moduleConfigs = $utilityComponent->getAllModules();
                        /** @var Zend_Config_Ini $moduleConfig */
                        $moduleConfig = $moduleConfigs[$this->module];
                    }

                    /** @var UuidComponent $uuidComponent */
                    $uuidComponent = MidasLoader::loadComponent('Uuid');
                    $uuid = $moduleConfig->get('uuid', $uuidComponent->generate());
                    $uuid = str_replace('-', '', $uuid);
                    $moduleDao->setUuid($uuid);
                }
                $moduleDao->setCurrentVersion($versionText);
                $moduleModel->save($moduleDao);
            } else {
                if ($this->module === 'core') {
                    $configPath = DATABASE_CONFIG;
                    $config = new Zend_Config_Ini($configPath, null, true);
                    $config->development->version = $versionText;
                    $config->production->version = $versionText;
                } else {
                    $configPath = LOCAL_CONFIGS_PATH.'/'.$this->module.'.local.ini';
                    $config = new Zend_Config_Ini($configPath, null, true);
                    $config->global->version = $versionText;
                }

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename($configPath);
                $writer->write();
            }
        }

        return true;
    }

    /**
     * Get class name.
     *
     * @param string $filename
     * @return string
     * @throws Zend_Exception
     */
    public function getClassName($filename)
    {
        $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+).php$/i', basename($filename), $matches);
        if ($result !== 1) {
            throw new Zend_Exception('Core or module version string is invalid.');
        }

        $className = '';
        if ($this->module != 'core') {
            $className = ucfirst($this->module).'_';
        }
        $className .= 'Upgrade_';

        return $className.$matches[1].'_'.$matches[2].'_'.$matches[3];
    }

    /**
     * Execute the upgrade.
     *
     * @param array $migration
     * @throws Zend_Exception
     */
    protected function _processFile($migration)
    {
        require_once BASE_PATH.'/core/models/MIDASUpgrade.php';
        $fileName = $migration['filename'];
        $className = $this->getClassName($fileName);

        require_once $this->dir.'/'.$fileName;
        if (!class_exists($className, false)) {
            throw new Zend_Exception("Could not find class '".$className."' in file '".$fileName."'");
        }

        /** @var MIDASUpgrade $upgradeClass */
        $upgradeClass = new $className($this->db, $this->module, $this->dbtype);
        $upgradeClass->preUpgrade();
        $dbTypeShort = $this->dbtypeShort;
        $upgradeClass->$dbTypeShort();
        $upgradeClass->postUpgrade();
    }
}
