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
 * Database upgrade base class.
 *
 * @package Core\Database
 */
class MIDASUpgrade
{
    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var string */
    protected $dbtype;

    /**
     * Constructor.
     *
     * @param Zend_Db_Adapter_Abstract $db database adapter
     * @param string $module module name
     * @param string $dbType database type
     */
    public function __construct($db, $module, $dbType)
    {
        $this->db = $db;
        $this->moduleName = $module;
        $this->loadElements();

        if ($module != 'core') {
            $this->loadModuleElements();
        }

        $this->dbtype = $dbType;
    }

    /** Pre database upgrade. */
    public function preUpgrade()
    {
    }

    /** Upgrade a MySQL database. */
    public function mysql()
    {
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
    }

    /** Upgrade a SQLite database. */
    public function sqlite()
    {
    }

    /** Post database upgrade. */
    public function postUpgrade()
    {
    }

    /**
     * Load module components, DAOs, forms, and models.
     *
     * @throws Zend_Exception
     */
    public function loadModuleElements()
    {
        if (isset($this->_moduleModels)) {
            MidasLoader::loadModels($this->_moduleModels, $this->moduleName);
            $modelsArray = Zend_Registry::get('models');

            foreach ($this->_moduleModels as $value) {
                if (isset($modelsArray[$this->moduleName.$value])) {
                    $tmp = ucfirst($this->moduleName).'_'.$value;
                    $this->$tmp = $modelsArray[$this->moduleName.$value];
                }
            }
        }

        if (isset($this->_moduleDaos)) {
            foreach ($this->_moduleDaos as $dao) {
                if (file_exists(BASE_PATH."/modules/".$this->moduleName."/models/dao/".$dao."Dao.php")) {
                    include_once BASE_PATH."/modules/".$this->moduleName."/models/dao/".$dao."Dao.php";
                } elseif (file_exists(
                    BASE_PATH."/privateModules/".$this->moduleName."/models/dao/".$dao."Dao.php"
                )) {
                    include_once BASE_PATH."/privateModules/".$this->moduleName."/models/dao/".$dao."Dao.php";
                } else {
                    throw new Zend_Exception("Unable to find dao file ".$dao);
                }
            }
        }

        if (isset($this->_moduleComponents)) {
            foreach ($this->_moduleComponents as $component) {
                $nameComponent = ucfirst($this->moduleName).'_'.$component."Component";

                if (file_exists(
                    BASE_PATH."/modules/".$this->moduleName."/controllers/components/".$component."Component.php"
                )) {
                    include_once BASE_PATH."/modules/".$this->moduleName."/controllers/components/".$component."Component.php";
                } elseif (file_exists(
                    BASE_PATH."/privateModules/".$this->moduleName."/controllers/components/".$component."Component.php"
                )) {
                    include_once BASE_PATH."/privateModules/".$this->moduleName."/controllers/components/".$component."Component.php";
                } else {
                    throw new Zend_Exception("Unable to find component file ".$component);
                }

                if (!isset($this->ModuleComponent)) {
                    $this->ModuleComponent = new stdClass();
                }

                $this->ModuleComponent->$component = new $nameComponent();
            }
        }

        if (isset($this->_moduleForms)) {
            foreach ($this->_moduleForms as $forms) {
                $nameForm = ucfirst($this->moduleName).'_'.$forms."Form";

                if (file_exists(
                    BASE_PATH."/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php"
                )) {
                    include_once BASE_PATH."/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php";
                } elseif (file_exists(
                    BASE_PATH."/privateModules/".$this->moduleName."/controllers/forms/".$forms."Form.php"
                )) {
                    include_once BASE_PATH."/privateModules/".$this->moduleName."/controllers/forms/".$forms."Form.php";
                } else {
                    throw new Zend_Exception("Unable to find form file ".$forms);
                }

                if (!isset($this->ModuleForm)) {
                    $this->ModuleForm = new stdClass();
                }

                $this->ModuleForm->$forms = new $nameForm();
            }
        }
    }

    /** Load core components, DAOs, forms, and models. */
    public function loadElements()
    {
        Zend_Registry::set('models', array());

        if (isset($this->_models)) {
            MidasLoader::loadModels($this->_models);
        }

        $modelsArray = Zend_Registry::get('models');

        foreach ($modelsArray as $key => $tmp) {
            $this->$key = $tmp;
        }

        if (isset($this->_daos)) {
            foreach ($this->_daos as $dao) {
                Zend_Loader::loadClass($dao."Dao", BASE_PATH.'/core/models/dao');
            }
        }

        Zend_Registry::set('components', array());

        if (isset($this->_components)) {
            foreach ($this->_components as $component) {
                $nameComponent = $component."Component";
                Zend_Loader::loadClass($nameComponent, BASE_PATH.'/core/controllers/components');

                if (!isset($this->Component)) {
                    $this->Component = new stdClass();
                }

                $this->Component->$component = new $nameComponent();
            }
        }

        Zend_Registry::set('forms', array());

        if (isset($this->_forms)) {
            foreach ($this->_forms as $forms) {
                $nameForm = $forms."Form";

                Zend_Loader::loadClass($nameForm, BASE_PATH.'/core/controllers/forms');

                if (!isset($this->Form)) {
                    $this->Form = new stdClass();
                }

                $this->Form->$forms = new $nameForm();
            }
        }
    }

    /**
     * Add a given field to a given table.
     *
     * @param string $table name of the database table
     * @param string $field name of the field
     * @param string $mySqlType type of the field in a MySQL database
     * @param string $pgSqlType type of the field in a PostgreSQL database
     * @param string $default default value of the field
     * @throws Zend_Exception
     */
    public function addTableField($table, $field, $mySqlType, $pgSqlType, $default)
    {
        $sql = '';
        if ($default !== false) {
            $sql = " DEFAULT '".$default."'";
        }

        if ($this->dbtype === 'PDO_MYSQL') {
            $this->db->query("ALTER TABLE ".$table." ADD ".$field." ".$mySqlType.$sql.";");
        } elseif ($this->dbtype === 'PDO_PGSQL') {
            $this->db->query("ALTER TABLE \"".$table."\" ADD \"".$field."\" ".$pgSqlType.$sql.";");
        } else {
            throw new Zend_Exception('Database does not support adding table fields');
        }
    }

    /**
     * Remove a given field from a given table.
     *
     * @param string $table name of the database table
     * @param string $field name of the field
     * @throws Zend_Exception
     */
    public function removeTableField($table, $field)
    {
        if ($this->dbtype === 'PDO_MYSQL') {
            $this->db->query("ALTER TABLE ".$table." DROP ".$field.";");
        } elseif ($this->dbtype === 'PDO_PGSQL') {
            $this->db->query("ALTER TABLE \"".$table."\" DROP COLUMN \"".$field."\";");
        } else {
            throw new Zend_Exception('Database does not support removing table fields');
        }
    }

    /**
     * Rename a given field of a given table.
     *
     * @param string $table name of the database table
     * @param string $field current name of the field
     * @param string $newField new name of the field
     * @param string $mySqlType type of the field in a MySQL database
     * @param string $pgSqlType type of the field in a PostgreSQL database
     * @param string $default default value of the field
     * @throws Zend_Exception
     */
    public function renameTableField($table, $field, $newField, $mySqlType, $pgSqlType, $default)
    {
        if ($this->dbtype === 'PDO_MYSQL') {
            if ($default !== false) {
                $this->db->query(
                    "ALTER TABLE ".$table." CHANGE ".$field." ".$newField." ".$mySqlType." DEFAULT '".$default."';"
                );
            } else {
                $this->db->query("ALTER TABLE ".$table." CHANGE ".$field." ".$newField." ".$mySqlType.";");
            }
        } elseif ($this->dbtype === 'PDO_PGSQL') {
            $this->db->query("ALTER TABLE \"".$table."\" RENAME \"".$field."\" TO \"".$newField."\";");
            $this->db->query("ALTER TABLE \"".$table."\" ALTER COLUMN \"".$newField."\" TYPE ".$pgSqlType.";");
            if ($default !== false) {
                $this->db->query(
                    "ALTER TABLE \"".$table."\" ALTER COLUMN \"".$newField."\" SET DEFAULT ".$default.";"
                );
            }
        } else {
            throw new Zend_Exception('Database does not support renaming table fields');
        }
    }

    /**
     * Check whether there is an index on a given field of a given table.
     *
     * @param string $table name of the database table
     * @param string $field name of the field
     * @return bool true if the index exists, false otherwise
     */
    public function checkIndexExists($table, $field)
    {
        if ($this->dbtype === 'PDO_MYSQL') {
            $rowset = $this->db->fetchAll("SHOW INDEX FROM ".$table.";");
            foreach ($rowset as $index_array) {
                if ($index_array['Column_name'] === $field) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add an index on a given field to a given table.
     *
     * @param string $table name of the database table
     * @param string $field name of the indexed field
     * @throws Zend_Exception
     */
    public function addTableIndex($table, $field)
    {
        if (!$this->checkIndexExists($table, $field)) {
            if ($this->dbtype === 'PDO_MYSQL') {
                $this->db->query("ALTER TABLE ".$table." ADD INDEX ( ".$field." );");
            } elseif ($this->dbtype === 'PDO_PGSQL') {
                @$this->db->query(
                    "CREATE INDEX ".$table."_".$field."_idx ON \"".$table."\" (\"".$field."\");"
                );
            } else {
                throw new Zend_Exception('Database does not support adding table indexes');
            }
        }
    }

    /**
     * Remove an index on a given field of a given table.
     *
     * @param string $table name of the database table
     * @param string $field name of the indexed field
     * @throws Zend_Exception
     */
    public function removeTableIndex($table, $field)
    {
        if ($this->checkIndexExists($table, $field)) {
            if ($this->dbtype === 'PDO_MYSQL') {
                $this->db->query("ALTER TABLE ".$table." DROP INDEX ".$field.";");
            } elseif ($this->dbtype === 'PDO_PGSQL') {
                $this->db->query("DROP INDEX ".$table."_".$field."_idx;");
            } else {
                throw new Zend_Exception('Database does not support renaming table indexes');
            }
        }
    }

    /**
     * Add a primary key on a given field to a given table.
     *
     * @param string $table name of the database table
     * @param string $field name of the primary key field
     * @throws Zend_Exception
     */
    public function addTablePrimaryKey($table, $field)
    {
        if ($this->dbtype === 'PDO_MYSQL') {
            $this->db->query("ALTER TABLE ".$table." ADD PRIMARY KEY ( ".$field." );");
        } elseif ($this->dbtype === 'PDO_PGSQL') {
            $this->db->query("ALTER TABLE \"".$table."\" ADD PRIMARY KEY (\"".$field."\");");
        } else {
            throw new Zend_Exception('Database does not support adding table primary keys');
        }
    }

    /**
     * Remove the primary key from a given table.
     *
     * @param string $table name of the database table
     * @throws Zend_Exception
     */
    public function removeTablePrimaryKey($table)
    {
        if ($this->dbtype === 'PDO_MYSQL') {
            $this->db->query("ALTER TABLE ".$table." DROP PRIMARY KEY;");
        } elseif ($this->dbtype === 'PDO_PGSQL') {
            $this->db->query("ALTER TABLE \"".$table."\" DROP CONSTRAINT \"value_pkey\";");
            $this->db->query("ALTER TABLE \"".$table."\" DROP CONSTRAINT \"".$table."_pkey\";");
        } else {
            throw new Zend_Exception('Database does not support removing table primary keys');
        }
    }
}
