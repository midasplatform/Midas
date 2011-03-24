<?php

/**
 *  MIDASDatabaseCassandra
 *  Global model methods
 */
class MIDASDatabaseCassandra implements MIDASDatabaseInterface
{
  protected $_name;
  protected $_mainData;
  protected $_key;
  protected $_db;
  
  /** Initialize */
  public function initialize($name,$key,$data)
    {      
    $this->_name = $name;
    $this->_mainData = $data;
    $this->_key = $key;
    
    if (!isset($this->_name))
      {
      throw new Zend_Exception("a Model PDO is not defined properly.");
      }
    if (!isset($this->_mainData))
      {
      throw new Zend_Exception("Model PDO " . $this->_name . " is not defined properly.");
      }
      
    $this->_db = Zend_Registry::get('dbAdapter');  
    }  // end function initialize

   /** Get the database */
   function getDB()
     {
     return $this->_db;  
     }    
    
  /**
   * @method public  getValues($key)
   *  Get all the value of a model
   * @param $key
   * @return An array with all the values
   */
  public function getValues($key)
    {
    $db = Zend_Registry::get('dbAdapter');
    
    try 
      {
      $column_family = new ColumnFamily($db, $this->_name);
      return $column_family->get($key);
      }
     catch(cassandra_NotFoundException $e) 
      {
      return false;  
      }  
     catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }
    } // end method getValues;
    
  /**
   * @method public save($dao)
   * Saves a DAO from the database
   * @param $dao
   * @return true/false
   */  
  public function save($dao)
    {
      
    } // end function save
    
  /**
   * @method public delete($dao)
   * Delete a DAO from the database
   * @param $dao
   * @return true/false
   */   
  public function delete($dao)
    {
    
    
    } // end function delete 
    
    
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
      throw new Zend_Exception("Database Cassandra " . $this->_name . ": var $var is not defined here.");
      }
      
    if (method_exists($this, 'get' . ucfirst($var)))
      {
      return call_user_func('get' . ucfirst($var), $key, $var);
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_DATA && $key!=null)
      {
      /*$result = $this->fetchRow($this->select()->where($this->_key . ' = ?', $key));
      if (!isset($result->$var))
        {
        return null;
        }
      return $result->$var;*/
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
      //return $model->__call("findBy" . ucfirst($this->_mainData[$var]['child_column']), array($dao->get($this->_mainData[$var]['parent_column'])));
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_MANY_TO_ONE)
      {
      require_once BASE_PATH.'/library/MIDAS/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
      //return $model->__call("getBy" . ucfirst($this->_mainData[$var]['child_column']), array($dao->get($this->_mainData[$var]['parent_column'])));
      }
    else if ($this->_mainData[$var]['type'] == MIDAS_MANY_TO_MANY)
      {
      //return $this->getLinkedObject($var, $dao);
      }
    else
      {
      throw new Zend_Exception('Unable to load data type ' . $var);
      }
    } 
    
} // end class MIDASDatabaseCassandra
?>
