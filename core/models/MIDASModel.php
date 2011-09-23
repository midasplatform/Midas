<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** global midas model library*/
class MIDASModel
{
  protected $database;
  protected $_name = ''; // I don't like this (should be protected)
  protected $_key = '';
  protected $_mainData = array();
  protected $_components = array();

  /**
   * @method public __construct()
   * Constructor
   */
  public function __construct()
    {
    // We should do the switch here
    $configDatabase = Zend_Registry::get('configDatabase');
    switch($configDatabase->database->type)
      {
      case 'pdo': $this->database = new MIDASDatabasePdo(); break;
      case 'cassandra': $this->database = new MIDASDatabaseCassandra(); break;
      case 'mongo': $this->database = new MIDASDatabaseMongo(); break;
      default: break;
      }
    } // end __construct()

  /** Initializing */
  public function initialize()
    {
    $this->loadElements(); // load the components for the models
    $this->database->initialize($this->_name, $this->_key, $this->_mainData);
    }

  /** Save a Dao */
  public function save($dao)
    {
    $instance = $this->_name."Dao";
    if(isset($this->_daoName) && isset($this->moduleName))
      {
      $instance = ucfirst($this->moduleName).'_'.$this->_daoName;
      }
    if(!$dao instanceof $instance)
      {
      throw new Zend_Exception("Should be an object (".$instance.").");
      }

    $dataarray = array();
    foreach($this->_mainData as $key => $var)
      {
      if(isset($dao->$key))
        {
        $dataarray[$key] = $dao->$key;
        }
      }

    $insertedid = $this->database->save($dataarray);

    if($insertedid !== false)
      {
      if(isset($this->_key) && !empty($this->_key))
        {
        $key = $this->_key;
        $dao->$key = $insertedid;
        }
      $dao->saved = true;
      }
    } // end save()

  /** Delete a Dao */
  public function delete($dao)
    {
    $this->database->delete($dao);
    }

  /**
   * @method public  loadElements()
   *  Loads model and components
   */
  public function loadElements()
    {
    Zend_Registry::set('components', array());
    if(isset($this->_components))
      {
      foreach($this->_components as $component)
        {
        $nameComponent = $component . "Component";

        Zend_Loader::loadClass($nameComponent, BASE_PATH . '/core/controllers/components');
        @$this->Component->$component = new $nameComponent();
        }
      }
    }

  /** Return the key */
  public function getKey()
    {
    return $this->_key;
    }

  /** Return the name */
  public function getName()
    {
    return $this->_name;
    }

  /** Return the maindata */
  public function getMainData()
    {
    return $this->_mainData;
    }

  /**
   * @method public function getLogger()
   * Get Logger
   * @return Zend_Log
   */
  public function getLogger()
    {
    return Zend_Registry::get('logger');
    }

  /**
   * @method public  initDao()
   *  init a dao
   * @param $name name of the dao
   * @param $data array of values
   */
  public function initDao($name, $data, $module = null)
    {
    // If no data found we return false
    if(!$data)
      {
      return false;
      }

    if($module == null)
      {
      $name = $name . 'Dao';
      Zend_Loader::loadClass($name, BASE_PATH.'/core/models/dao');
      }
    else
      {
      require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name. 'Dao.php';
      $name = ucfirst($module).'_'.$name. 'Dao';
      }
    if(class_exists($name))
      {
      $obj = new $name();
      $model = $obj->getModel();
      foreach($model->_mainData as $name => $option)
        {
        if(isset($data[$name]))
          {
          $obj->$name = $data[$name];
          }
        }
      $obj->saved = true;
      return $obj;
      }
    else
      {
      throw new Zend_Exception('Unable to load dao ' . $name);
      }
    } //end initDao


  /**
   * @method public  __call($method, $params)
   *  Catch ifthe method doesn't exists and create a method dynamically
   * @param $method method name
   * @param $params array of param
   * @return return the result of the function dynamically created
   */
  public function __call($method, $params)
    {
    if(substr($method, 0, 5) == 'getBy')
      {
      throw new Zend_Exception(__CLASS__ . " " . $this->_name . ": ".$method." has been deprecated. Please fix.");
      }
    elseif(substr($method, 0, 6) == 'findBy')
      {
      if(isset($this->_mainData[strtolower(substr($method, 6))]))
        {
        return $this->findBy(strtolower(substr($method, 6)), $params[0]);
        }
      else
        {
        throw new Zend_Exception("Dao:  " . __CLASS__ . " " . $this->_name . ": method ".$method." doesn't exist (" . strtolower(substr($method, 6)) . " is not defined.");
        }
      }
    else
      {
      throw new Zend_Exception($this->_name."Model : method ".$method." doesn't exist.");
      }
    }// end method __call


  /**
   * @method public  findBy($var, $value)
   *  find all DAO by $var = $value
   * @param $var name of the attribute we search
   * @param $value
   * @return daos
   */
  public function findBy($var, $value)
    {
    if(!isset($this->_mainData[$var]))
      {
      throw new Zend_Exception("Model PDO " . $this->_name . ": var ".$var." is not defined here.");
      }
    else
      {
      $module = '';
      if(isset($this->moduleName) )
        {
        $module = $this->moduleName;
        }
      $rowset = $this->database->findBy($var, $value);
      $return = array();

      // if there are any rows, set the daoName
      if(isset($rowset) && sizeof($rowset) > 0)
        {
        if(isset($this->_daoName))
          {
          $daoName = substr($this->_daoName, 0, strlen($this->_daoName)-3);
          }
        else
          {
          // can't just convert the name to dao name, in case it is in a module
          if(isset($this->moduleName))
            {
            // we want to split the string, expecting 2 parts, module_model
            // just use the model name for the dao
            $parts = explode('_', $this->_name);
            $daoName = ucfirst($parts[1]);
            }
          else
            {
            // if no module, just upper case the model name
            $daoName = ucfirst($this->_name);
            }
          }
        }
      foreach($rowset as $row)
        {
        $tmpDao = $this->initDao($daoName, $row, $module);
        $return[] = $tmpDao;
        unset($tmpDao);
        }
      return $return;
      }
    } //end method findBy




  /** load Dao class*/
  public function loadDaoClass($name, $module = 'core')
    {
    if($module == 'core')
      {
      Zend_Loader::loadClass($name, BASE_PATH . '/core/models/dao');
      if(!class_exists($name))
        {
        throw new Zend_Exception('Unable to load dao class ' . $name);
        }
      }
    else
      {
      require_once BASE_PATH . "/modules/".$module."/models/dao/".$name.".php";
      if(!class_exists(ucfirst($module).'_'.$name))
        {
        throw new Zend_Exception('Unable to load dao class ' . ucfirst($module).'_'.$name);
        }
      }

    }


  /**
   * @fn public  load()
   * @brief Load a dao   *
   * @return return dao
   */
  public function load($key = null)
    {
    if(isset($this->_daoName))
      {
      $name = $this->_daoName;
      }
    else
      {
      $name = ucfirst($this->_name) . 'Dao';
      }

    if(isset($this->moduleName))
      {
      $this->loadDaoClass(ucfirst(substr($name, strpos($name, '_') + 1)), $this->moduleName);
      }
    else
      {
      Zend_Loader::loadClass($name, BASE_PATH . '/core/models/dao');
      }

    if(class_exists($name))
      {
      if(!isset($this->_key) && $key != null)
        {
        throw new Zend_Exception("MIDASDatabasePDO " . $this->_name . ": key is not defined here. (you should write your own load method)");
        }
      if(is_array($key))
        {
        if(empty($key))
          {
          return array();
          }
        $cond = '';

        if(empty($key))
          {
          return array();
          }
        $rowset = $this->database->getAllByKey($key);
        $return = array();
        foreach($rowset as $row)
          {
          $tmpDao = $this->initDao(ucfirst($this->_name), $row);
          $return[] = $tmpDao;
          unset($tmpDao);
          }
        return $return;
        }
      else
        {
        $obj = new $name();
        if($key !== null && method_exists($obj, 'initValues'))
          {
          if(!$obj->initValues($key))
            {
            unset($obj);
            return false;
            }
          $obj->saved = true;
          }
        return $obj;
        }
      }
    else
      {
      throw new Zend_Exception('Unable to load dao ' . $name);
      }
    } //end load


  /**
   * @method public getValue()
   * Generic get function. You can define custom function.
   * @param $var name of the element we want to get
   * @param $key of the table
   * @return value
   */
  public function getValue($var, $key, $dao)
    {
    return $this->database->getValue($var, $key, $dao);
    }

  /** Function getValues */
  public function getValues($key)
    {
    return $this->database->getValues($key);
    }

  /** Returns the number of rows */
  public function getCountAll()
    {
    return $this->database->getCountAll();
    }

  /**
   * @method public compareDao($dao1, $dao2)
   *  Compare 2 dao (onlye the MIDAS_DATA
   * @param $dao1
   * @param $dao2
   * @return True ifthey are the same one
   */
  public function compareDao($dao1, $dao2)
    {
    if(!is_object($dao1) || !is_object($dao2))
      {
      return false;
      }
    foreach($this->_mainData as $name => $data)
      {
      if($data['type'] == MIDAS_DATA)
        {
        if($dao1->get($name) !== $dao2->get($name))
          {
          return false;
          }
        }
      }
    return true;
    } //end method compareDao

} // end class GlobalModel
