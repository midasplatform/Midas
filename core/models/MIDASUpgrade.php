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
 *  MIDASUpgrade
 */
class MIDASUpgrade
{
  protected $db;
  protected $dbtype;

  /**
   * @method public  __construct()
   *  Construct model
   */
  public function __construct($db, $module, $dbType)
    {
    $this->db = $db;
    $this->moduleName = $module;
    $this->loadElements();
    if($module != 'core')
      {
      $this->loadModuleElements();
      }
    $this->dbtype = $dbType;
    } // end __construct()

  /** preUpgrade called before the upgrade*/
  public function preUpgrade()
    {

    }

  /** calls if mysql enable*/
  public function mysql()
    {

    }

  /** called is pgsql enabled*/
  public function pgsql()
    {

    }

  /** called after the upgrade*/
  public function postUpgrade()
    {

    }


  /**
   * @method public  loadElements()
   *  Loads model and components
   */
  public function loadModuleElements()
    {
    $this->ModelLoader = new MIDAS_ModelLoader();
    if(isset($this->_moduleModels))
      {
      $this->ModelLoader->loadModels($this->_moduleModels, $this->moduleName);
      $modelsArray = Zend_Registry::get('models');
      foreach($this->_moduleModels as  $value)
        {
        if(isset($modelsArray[$this->moduleName.$value]))
          {
          $tmp = ucfirst($this->moduleName).'_'.$value;
          $this->$tmp = $modelsArray[$this->moduleName.$value];
          }
        }
      }

    if(isset($this->_moduleDaos))
      {
      foreach($this->_moduleDaos as $dao)
        {
        if(file_exists(BASE_PATH . "/modules/".$this->moduleName."/models/dao/".$dao."Dao.php"))
          {
          include_once (BASE_PATH . "/modules/".$this->moduleName."/models/dao/".$dao."Dao.php");
          }
        elseif(file_exists(BASE_PATH . "/privateModules/".$this->moduleName."/models/dao/".$dao."Dao.php"))
          {
          include_once (BASE_PATH . "/privateModules/".$this->moduleName."/models/dao/".$dao."Dao.php");
          }
        else
          {
          throw new Zend_Exception("Unable to find dao file ".$dao);
          }
        }
      }

    if(isset($this->_moduleComponents))
      {
      foreach($this->_moduleComponents as $component)
        {
        $nameComponent = ucfirst($this->moduleName).'_'.$component . "Component";
        if(file_exists(BASE_PATH . "/modules/".$this->moduleName."/controllers/components/".$component."Component.php"))
          {
          include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/components/".$component."Component.php");
          }
        elseif(file_exists(BASE_PATH . "/privateModules/".$this->moduleName."/controllers/components/".$component."Component.php"))
          {
          include_once (BASE_PATH . "/privateModules/".$this->moduleName."/controllers/components/".$component."Component.php");
          }
        else
          {
          throw new Zend_Exception("Unable to find component file ".$component);
          }

        if(!isset($this->ModuleComponent))
          {
          $this->ModuleComponent =  new stdClass();
          }
        $this->ModuleComponent->$component = new $nameComponent();
        }
      }

    if(isset($this->_moduleForms))
      {
      foreach($this->_moduleForms as $forms)
        {
        $nameForm = ucfirst($this->moduleName).'_'.$forms . "Form";
        if(file_exists(BASE_PATH . "/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php"))
          {
          include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php");
          }
        elseif(file_exists(BASE_PATH . "/privateModules/".$this->moduleName."/controllers/forms/".$forms."Form.php"))
          {
          include_once (BASE_PATH . "/privateModules/".$this->moduleName."/controllers/forms/".$forms."Form.php");
          }
        else
          {
          throw new Zend_Exception("Unable to find form file ".$forms);
          }

        if(!isset($this->ModuleForm))
          {
          $this->ModuleForm =  new stdClass();
          }
        $this->ModuleForm->$forms = new $nameForm();
        }
      }
    }

  /**
   * @method public  loadElements()
   *  Loads model and components
   */
  public function loadElements()
    {
    Zend_Registry::set('models', array());
    $this->ModelLoader = new MIDAS_ModelLoader();
    if(isset($this->_models))
      {
      $this->ModelLoader->loadModels($this->_models);
      }
    $modelsArray = Zend_Registry::get('models');
    foreach($modelsArray as $key => $tmp)
      {
      $this->$key = $tmp;
      }

    if(isset($this->_daos))
      {
      foreach($this->_daos as $dao)
        {
        Zend_Loader::loadClass($dao . "Dao", BASE_PATH . '/core/models/dao');
        }
      }

    Zend_Registry::set('components', array());

    if(isset($this->_components))
      {
      foreach($this->_components as $component)
        {
        $nameComponent = $component . "Component";
        Zend_Loader::loadClass($nameComponent, BASE_PATH . '/core/controllers/components');
        if(!isset($this->Component))
          {
          $this->Component =  new stdClass();
          }
        $this->Component->$component = new $nameComponent();
        }
      }

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach($this->_forms as $forms)
        {
        $nameForm = $forms . "Form";

        Zend_Loader::loadClass($nameForm, BASE_PATH . '/core/controllers/forms');
        if(!isset($this->Form))
          {
          $this->Form =  new stdClass();
          }
        $this->Form->$forms = new $nameForm();
        }
      }
    }//end loadElements


  /**
   * @method public AddTableField()
   *  Add a field to a table
   */
  function addTableField($table, $field, $mySQLType, $pgSqlType, $default)
    {
    $sql = '';
    if($default !== false)
      {
      $sql = " DEFAULT '".$default."'";
      }

    if($this->dbtype == "PDO_PGSQL")
      {
      $this->db->query("ALTER TABLE \"".$table."\" ADD \"".$field."\" ".$pgSqlType.$sql);
      }
    else
      {
      $this->db->query("ALTER TABLE ".$table." ADD ".$field." ".$mySQLType.$sql);
      }
    }

  /**
   * @method public RemoveTableField()
   *  Remove a field from a table
   */
  function removeTableField($table, $field)
    {
    if($this->dbtype == "PDO_PGSQL")
      {
      $this->db->query("ALTER TABLE \"".$table."\" DROP COLUMN \"".$field."\"");
      }
    else
      {
      $this->db->query("ALTER TABLE ".$table." DROP ".$field);
      }
    }

  /**
   * @method public RenameTableField()
   *  Rename a field from a table
   */
  function renameTableField($table, $field, $newfield, $mySQLType, $pgSqlType, $default)
    {
    if($this->dbtype == "PDO_PGSQL")
      {
      $this->db->query("ALTER TABLE \"".$table."\" RENAME \"".$field."\" TO \"".$newfield."\"");
      $this->db->query("ALTER TABLE \"".$table."\" ALTER COLUMN \"".$newfield."\" TYPE ".$pgSqlType);
      if($default !== false)
        {
        $this->db->query("ALTER TABLE \"".$table."\" ALTER COLUMN \"".$newfield."\" SET DEFAULT ".$default);
        }
      }
    else
      {
      if($default !== false)
        {
        $this->db->query("ALTER TABLE ".$table." CHANGE ".$field." ".$newfield." ".$mySQLType." DEFAULT '".$default."'");
        }
      else
        {
        $this->db->query("ALTER TABLE ".$table." CHANGE ".$field." ".$newfield." ".$mySQLType);
        }
      }
    }

  /**
   * @method public CheckIndexExists()
   *  Check if the index exists.
   *  Only works for MySQL
   */
  function checkIndexExists($table, $field)
    {
    if($this->dbtype == "PDO_MYSQL")
      {
      $rowset = $this->db->fetchAll("SHOW INDEX FROM ".$tablename);
      foreach($rowset as $index_array)
        {
        if($index_array['Column_name'] == $columnname)
          {
          return true;
          }
        }
      }
    return false;
    }  // end CheckIndexExists()

  /**
   * @method public AddTableIndex()
   *  Add an index to a table
   */
  function addTableIndex($table, $field)
    {
    if(!$this->checkIndexExists($table, $field))
      {
      if($this->dbtype == "PDO_PGSQL")
        {
        @$this->db->query("CREATE INDEX ".$table."_".$field."_idx ON \"".$table."\" (\"".$field."\")");
        }
      else
        {
        $this->db->query("ALTER TABLE ".$table." ADD INDEX ( ".$field." )");
        }
      }
    }

  /**
   * @method public RemoveTableIndex()
   *  Remove an index from a table
   */
  function removeTableIndex($table, $field)
    {
    if($this->checkIndexExists($table, $field))
      {
      if($this->dbtype == "PDO_PGSQL")
        {
        $this->db->query("DROP INDEX ".$table."_".$field."_idx");
        }
      else
        {
        $this->db->query("ALTER TABLE ".$table." DROP INDEX ".$field);
        }
      }
    }

  /**
   * @method public AddTablePrimaryKey()
   *  Add a primary key to a table
   */
  function addTablePrimaryKey($table, $field)
    {
    if($this->dbtype == "PDO_PGSQL")
      {
      $this->db->query("ALTER TABLE \"".$table."\" ADD PRIMARY KEY (\"".$field."\")");
      }
    else
      {
      $this->db->query("ALTER TABLE ".$table." ADD PRIMARY KEY ( ".$field." )");
      }
    }

  /**
   * @method public RemoveTablePrimaryKey()
   *  Remove a primary key from a table
   */
  function removeTablePrimaryKey($table)
    {
    if($this->dbtype == "PDO_PGSQL")
      {
      $this->db->query("ALTER TABLE \"".$table."\" DROP CONSTRAINT \"value_pkey\"");
      $this->db->query("ALTER TABLE \"".$table."\" DROP CONSTRAINT \"".$table."_pkey\"");
      }
    else
      {
      $this->db->query("ALTER TABLE ".$table." DROP PRIMARY KEY");
      }
    }

} //end class MIDASUpgrade
?>
