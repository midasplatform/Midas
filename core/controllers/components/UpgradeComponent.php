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

/** Upgrade MIDAS */
class UpgradeComponent extends AppComponent
{
    public $dir;
    protected $module;
    protected $db;
    protected $dbtype;
    protected $dbtypeShort;
    public $init = false;

    /** init upgrade Component */
    public function initUpgrade($module, $db, $dbtype)
    {
        if ($module == 'core') {
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
            case "PDO_MYSQL":
                $this->dbtypeShort = 'mysql';
                break;
            case "PDO_PGSQL":
                $this->dbtypeShort = 'pgsql';
                break;
            default:
                throw new Zend_Exception("Unknown database type");
                break;
        }
        $this->init = true;
    }

    /** get Newest version */
    public function getNewestVersion($text = false)
    {
        if (!$this->init) {
            throw new Zend_Exception("Please init the component first");
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

    /** get all migration files */
    public function getMigrationFiles()
    {
        if (!$this->init) {
            throw new Zend_Exception("Please init the component first");
        }
        $files = array();
        if (file_exists($this->dir)) {
            $d = dir($this->dir);
            while (false !== ($entry = $d->read())) {
                if (preg_match('/^([0-9]+)(.)([0-9]+)(.)([0-9]+)\.php/i', $entry, $matches)) {
                    $versionText = basename(str_replace(".php", "", $entry));
                    $versionNumber = $this->transformVersionToNumeric($versionText);
                    $files[$versionNumber] = array(
                        'filename' => $entry,
                        'version' => $versionNumber,
                        'versionText' => $versionText,
                    );
                }
                if (preg_match('/^([0-9]+)(.)([0-9]+)(.)([0-9]+)\.sql/i', $entry, $matches)) {
                    $versionText = basename(str_replace(".sql", "", $entry));
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

    /** transformVersionToNumeric */
    public function transformVersionToNumeric($text)
    {
        $array = explode('.', $text);
        if (count($array) != 3) {
            throw new Zend_Exception("The version format shoud be 1.2.5. You set:".$text);
        }

        return (int) $array[0] * 1000000 + (int) $array[1] * 1000 + (int) $array[2];
    }

    /** upgrade */
    public function upgrade($currentVersion = null, $testing = false)
    {
        if ($currentVersion == null) {
            $currentVersion = Zend_Registry::get('configDatabase')->version;
        }
        if ($currentVersion == null) {
            throw new Zend_Exception('Version undefined');
        }
        if (!is_numeric($currentVersion)) {
            $currentVersion = $this->transformVersionToNumeric($currentVersion);
        }

        $version = $this->getNewestVersion($text = false);

        if ($currentVersion >= $version || $version == 0) {
            return false;
        }

        $migrations = $this->getMigrationFiles();

        foreach ($migrations as $migration) {
            if ($migration['version'] > $currentVersion) {
                $this->_processFile($migration);
            }
        }

        if ($this->module == 'core') {
            if (isset($migration)) {
                $path = LOCAL_CONFIGS_PATH.'/database.local.ini';
                if ($testing || Zend_Registry::get('configGlobal')->environment == 'testing'
                ) {
                    $path = getenv('MIDAS_DATABASE_INI');
                    if (!file_exists($path)) {
                        if (file_exists(BASE_PATH.'/tests/configs/lock.mysql.ini')) {
                            $path = BASE_PATH.'/tests/configs/lock.mysql.ini';
                        }
                        if (file_exists(BASE_PATH.'/tests/configs/lock.pgsql.ini')) {
                            $path = BASE_PATH.'/tests/configs/lock.pgsql.ini';
                        }
                    }
                }
                $options = array('allowModifications' => true);
                if ($testing || Zend_Registry::get('configGlobal')->environment == 'testing'
                ) {
                    $path = getenv('MIDAS_DATABASE_INI');
                    if (!file_exists($path)) {
                        if (file_exists(BASE_PATH.'/tests/configs/lock.mysql.ini')) {
                            $path = BASE_PATH.'/tests/configs/lock.mysql.ini';
                        }
                        if (file_exists(BASE_PATH.'/tests/configs/lock.pgsql.ini')) {
                            $path = BASE_PATH.'/tests/configs/lock.pgsql.ini';
                        }
                    }
                    $config = new Zend_Config_Ini($path, null, $options);
                    $config->testing->version = $migration['versionText'];
                } else {
                    $config = new Zend_Config_Ini($path, null, $options);
                    $config->development->version = $migration['versionText'];
                    $config->production->version = $migration['versionText'];
                }

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename($path);
                $writer->write();
            }
        } else {
            $path = LOCAL_CONFIGS_PATH."/".$this->module.".local.ini";
            if (file_exists($path)) {
                $options = array('allowModifications' => true);
                $config = new Zend_Config_Ini($path, null, $options);
                $config->global->version = $migration['versionText'];

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename($path);
                $writer->write();
            }
        }

        return true;
    }

    /** get Class Name */
    public function getClassName($filename)
    {
        $array = explode('.', str_replace('.php', '', basename($filename)));
        if (count($array) != 3) {
            throw new Zend_Exception(
                "The version format shoud be 1.2.5. You set:".str_replace('.php', '', basename($filename))
            );
        }

        $classname = '';
        if ($this->module != 'core') {
            $classname = ucfirst($this->module).'_';
        }
        $classname .= "Upgrade_";

        return $classname.$array[0].'_'.$array[1].'_'.$array[2];
    }

    /** execute de upgrade */
    protected function _processFile($migration)
    {
        require_once BASE_PATH.'/core/models/MIDASUpgrade.php';
        $filename = $migration['filename'];
        $classname = $this->getClassName($filename);
        require_once $this->dir.'/'.$filename;
        if (!class_exists($classname, false)) {
            throw new Zend_Exception("Could not find class '".$classname."' in file '".$filename."'");
        }

        $class = new $classname($this->db, $this->module, $this->dbtype);
        $class->preUpgrade();
        $dbtypeShort = $this->dbtypeShort;
        $class->$dbtypeShort();
        $class->postUpgrade();
    }
}
