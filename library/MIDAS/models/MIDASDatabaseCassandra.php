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
    
} // end class MIDASDatabaseCassandra
?>
