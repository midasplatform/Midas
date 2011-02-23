<?php

/**
 *  GlobalModelPdo
 *  Global model methods
 */
class MIDAS_GlobalModelPdo extends Zend_Db_Table_Abstract
{
  /**
   * @method public  __construct()
   *  Construct model
   */
  public function __construct($config = array())
    {
    parent::__construct($config);
    if (!isset($this->_name))
      {
      throw new Zend_Exception("a Model PDO is not defined properly.");
      }
    if (!isset($this->_mainData))
      {
      throw new Zend_Exception("Model PDO " . $this->_name . " is not defined properly.");
      }
    $this->loadElements();
    }


  /**
   * @method public  loadElements()
   *  Loads model and components
   */
  public function loadElements()
    {
    Zend_Registry::set('components', array());
    if (isset($this->_components))
      {
      foreach ($this->_components as $component)
        {
        $nameComponent = $component . "Component";

        Zend_Loader::loadClass($nameComponent, BASE_PATH . '/application/controllers/components');
        @$this->Component->$component = new $nameComponent();
        }
      }
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
   * @method public  get()
   * Generic get function. You can define custom function.
   * @param $var name of the element we want to get
   * @param $key of the table
   * @return value
   */
  public function getValue($var, $key, $dao)
    {
    if (!isset($this->_mainData[$var]))
      {
      throw new Zend_Exception("Model PDO " . $this->_name . ": var $var is not defined here.");
      }
    if (method_exists($this, 'get' . ucfirst($var)))
      {
      return call_user_func('get' . ucfirst($var), $key, $var);
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_DATA&&$key!=null)
      {
      $result = $this->fetchRow($this->select()->where($this->_key . ' = ?', $key));
      if (!isset($result->$var))
        {
        return null;
        }
      return $result->$var;
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_ONE_TO_MANY)
      {
      require_once BASE_PATH.'/library/MIDAS/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
      if(!$dao->get($this->_mainData[$var]['parent_column']))
        {
        throw new Zend_Exception($this->_mainData[$var]['parent_column']. " is not defined in the dao: ".get_class($dao));
        }
      return $model->__call("findBy" . ucfirst($this->_mainData[$var]['child_column']), array($dao->get($this->_mainData[$var]['parent_column'])));
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_MANY_TO_ONE)
      {
      require_once BASE_PATH.'/library/MIDAS/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
      return $model->__call("getBy" . ucfirst($this->_mainData[$var]['child_column']), array($dao->get($this->_mainData[$var]['parent_column'])));
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_MANY_TO_MANY)
      {
      return $this->getLinkedObject($var, $dao);
      }
    else
      {
      throw new Zend_Exception('Unable to load data type ' . $var);
      }
    }

  /**
   * @method protected function getLinkedObject($var, $dao)
   *  get linked objects
   * @param $var What object
   * @param $dao Using dao data
   * @return An array of object
   */
  protected function getLinkedObject($var, $dao)
    {
    require_once BASE_PATH.'/library/MIDAS/models/ModelLoader.php';
    $this->ModelLoader = new MIDAS_ModelLoader();
    $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
    $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from($model->_name)
            ->joinUsing($this->_mainData[$var]['table'], $this->_mainData[$var]['child_column'])
            ->where($this->_mainData[$var]['parent_column'] . ' = ?', $dao->{$this->_mainData[$var]['parent_column']});
    $rowset = $this->fetchAll($sql);

    $return = array();
    foreach ($rowset as $row)
      {
      $return[] = $model->initDao($this->_mainData[$var]['model'], $row);
      }
    return $return;
    } //end getLinkedObject

  /**
   * @method public  initDao()
   *  init a dao
   * @param $name name of the dao
   * @param $data array of values
   */
  protected function initDao($name, $data)
    {
    // If no data found we return false
    if(!$data)
      {
      return false;
      }

    $name = $name . 'Dao';
    Zend_Loader::loadClass($name, BASE_PATH.'/application/models/dao');
    if (class_exists($name))
      {
      $obj = new $name();
      $model = $obj->getModel();
      foreach ($model->_mainData as $name => $option)
        {
        if (isset($data->$name))
          {
          $obj->$name = $data->$name;
          }
        }
      $obj->saved=true;
      return $obj;
      }
    else
      {
      throw new Zend_Exception('Unable to load dao ' . $name);
      }
    } //end initDao

  /**
   * @method public  getBy($var,$option)
   *  Get DAO by $var = $value
   * @param $var name of the attribute we search
   * @param $value
   * @return dao
   */
  public function getBy($var, $value)
    {
    if (!isset($this->_mainData[$var]))
      {
      throw new Zend_Exception("Model PDO " . $this->_name . ": var $var is not defined here.");
      }
    else
      {
      $dao= $this->initDao(ucfirst($this->_name), $this->fetchRow($this->select()->where($var . ' = ?', $value)));
      return $dao;
      }
    } //end getBy

  /**
   * @method  function addLink($var,$daoParent, $daoSon)
   *  create a link between 2 tables
   * @param $var name of the attribute we search
   * @param $daoParent
   * @param $daoSon
   * @return sql result
   */
  protected function link($var, $daoParent, $daoSon)
    {
    $objs = $daoParent->get($var);

    $modelloader = new MIDAS_ModelLoader();
    $model = $modelloader->loadModel($this->_mainData[$var]['model']);
    foreach ($objs as $obj)
      {
      if ($model->compareDao($obj, $daoSon))
        {
        return;
        }
      }
    unset($daoParent->$var);
    $data = array();

    $data[$this->_mainData[$var]['parent_column']] = $daoParent->get($this->_mainData[$var]['parent_column']);
    $data[$this->_mainData[$var]['child_column']] = $daoSon->get($this->_mainData[$var]['child_column']);
    $db = Zend_Registry::get('dbAdapter');

    $parentcolumn = $this->_mainData[$var]['parent_column'];
    $childcolumn = $this->_mainData[$var]['child_column'];

    // By definition a link is unique, so we should check
    $select = $db->select()->from($this->_mainData[$var]['table'],array('nrows' => 'COUNT(*)'))
                             ->where($parentcolumn."=?",$data[$this->_mainData[$var]['parent_column']])
                             ->where($childcolumn."=?",$data[$this->_mainData[$var]['child_column']]);

    $row = $db->fetchRow($select);
    if($row['nrows'] == 0)
      {
      return $db->insert($this->_mainData[$var]['table'], $data);
      }
    return false;
    } //end method link


  /**
   * @method public  removeLink($var,$daoParent, $daoSon)
   *  remove a link between 2 tables
   * @param $var name of the attribute we search
   * @param $daoParent
   * @param $daoSon
   * @return sql result
   */
  protected function removeLink($var, $daoParent, $daoSon)
    {
    unset($daoParent->$var);
    $data = array();

    $data[$this->_mainData[$var]['parent_column']] = $daoParent->get($this->_mainData[$var]['parent_column']);
    $data[$this->_mainData[$var]['child_column']] = $daoSon->get($this->_mainData[$var]['child_column']);
    $db = Zend_Registry::get('dbAdapter');
    return $db->delete($this->_mainData[$var]['table'], array($this->_mainData[$var]['parent_column'] . ' = ?' => $daoParent->get($this->_mainData[$var]['parent_column']),
      $this->_mainData[$var]['child_column'] . ' = ?' => $daoSon->get($this->_mainData[$var]['child_column'])
    ));
    } //end method removeLink


  /**
   * @method public  findBy($var, $value)
   *  find all DAO by $var = $value
   * @param $var name of the attribute we search
   * @param $value
   * @return daos
   */
  public function findBy($var, $value)
    {
    if (!isset($this->_mainData[$var]))
      {
      throw new Zend_Exception("Model PDO " . $this->_name . ": var $var is not defined here.");
      }
    else
      {
      $rowset = $this->fetchAll($this->select()->where($var . ' = ?', $value));
      $return = array();
      foreach ($rowset as $row)
        {
        $tmpDao= $this->initDao(ucfirst($this->_name), $row);
        $return[] = $tmpDao;
        unset($tmpDao);
        }
      return $return;
      }
    } //end method findBy

  /**
   * @method public  getAll()
   *  find all DAO
   * @return daos
   */
  public function getAll()
    {
    $rowset = $this->fetchAll($this->select());
    $return = array();
    foreach ($rowset as $row)
      {
      $return[] = $this->initDao(ucfirst($this->_name), $row);
      }
    return $return;
    } //end method getAll


  /**
   * @method public  getValues($key)
   *  Get all the value of a model
   * @param $key
   * @return An array with all the values
   */
  public function getValues($key)
    {
    return $this->fetchRow($this->select()->where($this->_key . ' = ?', $key));
    } // end method getValues;

  /**
   * @method public  save($data)
   *  Save or update
   * @param $data array with dao information
   * @return boolean
   */
  public function save($dao)
    {
    $instance=$this->_name."Dao";
    if(!$dao instanceof  $instance)
      {
      throw new Zend_Exception("Should be an object ($instance).");
      }

    $data = array();
    foreach($this->_mainData as $key => $var)
      {
      if(isset($dao->$key))
        {
        $data[$key] = $dao->$key;
        }
      }

    $dataFiltered = array();
    foreach($data as $key => $d)
      {
      if(isset($this->_mainData[$key]))
        {
        $dataFiltered[$key] = $d;
        }
      }

    if(isset($this->_key)&&isset($dataFiltered[$this->_key]))
      {
      $key = $dataFiltered[$this->_key];
      unset($dataFiltered[$this->_key]);
      $nupdated=$this->update($dataFiltered, array($this->_key.'=?'=>$key));
      if($nupdated==0)
        {
        return false;
        }
      return true;
      }
    else
      {
      $insertedid = $this->insert($dataFiltered);
      if(!$insertedid)
        {
        return false;
        }
      $dao->saved=true;
      if(isset($this->_key))
        {
        $key =  $this->_key;
        $dao->$key = $insertedid;
        }
      return true;
      }
    } // end method save

  /**
   * @method public  delete()
   *  Delete in the db
   * @param $dao
   */
  public function delete($dao)
    {
    $instance=ucfirst($this->_name)."Dao";
    if(get_class($dao) !=  $instance)
      {
      throw new Zend_Exception("Should be an object ($instance). It was: ".get_class($dao) );
      }
    if(!$dao->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }
    if(!isset($this->_key))
      {
      $query=array();
      foreach ($this->_mainData as $name => $option)
        {
        if($option['type']==MIDAS_DATA)
          {
          $query[$name. ' = ?']=$dao->$name;
          }
        }
      if(empty($query))
        {
        throw new Zend_Exception("Huge error, you almost deleted everything" );
        }
      parent::delete($query);
      $dao->saved=false;
      return true;
      }
    $key=$dao->getKey();
    if(!isset($key))
      {
      throw new Zend_Exception("Unable to find the key" );
      }
    parent::delete(array($this->_key . ' = ?' => $dao->getKey()));
    unset($dao->{$dao->_key});
    $dao->saved=false;
    return true;
    }//end delete


  /**
   * @method public  __call($method, $params)
   *  Catch if the method doesn't exists and create a method dynamically
   * @param $method method name
   * @param $params array of param
   * @return return the result of the function dynamically created
   */
  public function __call($method, $params)
    {
    if (substr($method, 0, 5) == 'getBy')
      {

      if (isset($this->_mainData[strtolower(substr($method, 5))]))
        {

        return $this->getBy(strtolower(substr($method, 5)), $params[0]);
        }
      else
        {
        throw new Zend_Exception("Dao:  " . __CLASS__ . " " . $this->_name . ": method $method doesn't exist (" . strtolower(substr($method, 5)) . " is not defined.");
        }
      }
    elseif (substr($method, 0, 6) == 'findBy')
      {

      if (isset($this->_mainData[strtolower(substr($method, 6))]))
        {

        return $this->findBy(strtolower(substr($method, 6)), $params[0]);
        }
      else
        {
        throw new Zend_Exception("Dao:  " . __CLASS__ . " " . $this->_name . ": method $method doesn't exist (" . strtolower(substr($method, 6)) . " is not defined.");
        }
      }
    else
      {
      throw new Zend_Exception("Model:  " . __CLASS__ . " " . $this->_name . ": method $method doesn't exist.");
      }
    }// end method __call

  /**
   * @method public compareDao($dao1,$dao2)
   *  Compare 2 dao (onlye the MIDAS_DATA
   * @param $dao1
   * @param $dao2
   * @return True if they are the same one
   */
  public function compareDao($dao1, $dao2)
    {
    if(!is_object($dao1) || !is_object($dao2))
      {
      return false;
      }
    foreach ($this->_mainData as $name => $data)
      {
      if ($data['type'] == MIDAS_DATA)
        {
        if ($dao1->get($name) !== $dao2->get($name))
          {
          return false;
          }
        }
      }
    return true;
    } //end method compareDao


    /** load Dao class*/
  public function loadDaoClass($name)
    {
    Zend_Loader::loadClass($name, BASE_PATH . '/application/models/dao');
    if (!class_exists($name))
      {
      throw new Zend_Exception('Unable to load dao class ' . $name);
      }
    }

  /**
   * @fn public  load()
   * @brief Load a dao   *
   * @return return dao
   */
  public function load($key=null)
    {
    if (isset($this->_daoName))
      {
      $name=$this->_daoName;
      }
    else
      {
      $name = ucfirst($this->_name) . 'Dao';
      }
    Zend_Loader::loadClass($name, BASE_PATH . '/application/models/dao');
    if (class_exists($name))
      {
      if(!isset($this->_key)&&$key!=null)
        {
        throw new Zend_Exception("Model PDO " . $this->_name . ": key is not defined here. (you should write your own load method)");
        }
      if(is_array($key))
        {
        if(empty($key))
          {
          return array();
          }
        $cond='';
        foreach($key as $k=>$v)
          {
          if (!is_numeric($v))
            {
            unset($key[$k]);
            }
          }
        $rowset = $this->fetchAll($this->select()->where($this->_key . ' IN (?)', $key));
        $return = array();
        foreach ($rowset as $row)
          {
          $tmpDao= $this->initDao(ucfirst($this->_name), $row);
          $return[] = $tmpDao;
          unset($tmpDao);
          }
        return $return;
        }
      else
        {
        $obj = new $name();
        if ($key !== null && method_exists($obj, 'initValues'))
          {
          if (!$obj->initValues($key))
            {
            unset($obj);
            return false;
            }
          $obj->saved=true;
          }
        return $obj;
        }
      }
    else
      {
      throw new Zend_Exception('Unable to load dao ' . $name);
      }
    } //end load

  } //end class

?>
