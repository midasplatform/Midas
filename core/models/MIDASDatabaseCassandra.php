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
  public function initialize($name, $key, $data)
    {
    $this->_name = $name;
    $this->_mainData = $data;
    $this->_key = $key;

    if(!isset($this->_name))
      {
      throw new Zend_Exception("a Model PDO is not defined properly.");
      }
    if(!isset($this->_mainData))
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

  /** Because hex2bin doesn't exist in php...*/
  /* function hex2bin($h)
    {
    if(!is_string($h)) return null;
    $r = '';
    for ($a = 0; $a<strlen($h); $a .= 2) { $r .= chr(hexdec($h{$a}.$h{($a+1)})); }
    return $r;
    } // end hex2bin;
    */

  /**
   * @method public  getValues($key)
   *  Get all the value of a model
   * @param $key
   * @return An array with all the values
   */
  public function getValues($key)
    {
    try
      {
      $column_family = new ColumnFamily($this->_db, $this->_name);

      // We need to add the key
      $array = $column_family->get($key);
      $array[$this->_key] = $key;
      return (object)$array;
      }
    catch(cassandra_NotFoundException $e)
      {
      return null;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    } // end method getValues;

  /** getAllByKey() */
  public function getAllByKey($keys)
    {
    /** Remove empty keys */
    foreach($keys as $k => $v)
      {
      if(empty($v))
        {
        unset($keys[$k]);
        }
      }

    try
      {
      $column_family = new ColumnFamily($this->_db, $this->_name);

      // We need to add the key
      $rows = $column_family->multiget($keys);
      $array = array();
      foreach($rows as $key => $row)
        {
        $row[$this->_key] = $key;
        $array[$key] = $row;
        }

      return $array;
      }
    catch(cassandra_NotFoundException $e)
      {
      return null;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }

    return $this->fetchAll($this->select()->where($this->_key . ' IN (?)', $key));
    }

  /**
   * @method public save($dao)
   * Saves a DAO from the database
   * @param $dao
   * @return true/false
   */
  public function save($dataarray)
    {
    try
      {
      // There is no update in Cassandra, everything is insert by key
      if(isset($this->_key) && isset($dataarray[$this->_key]))
        {
        $keyvalue = $dataarray[$this->_key];
        unset($dataarray[$this->_key]);

        $column_family = new ColumnFamily($this->_db, $this->_name);
        $column_family->insert($keyvalue, $dataarray);
        }
      else
        {
        $keyvalue =  bin2hex(CassandraUtil::uuid1());
        //$keyvalue = bin2hex($key);
        $db = Zend_Registry::get('dbAdapter');
        $column_family = new ColumnFamily($this->_db, $this->_name);
        $column_family->insert($keyvalue, $dataarray);
        }
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    return $keyvalue;
    } // end function save

  /**
   * @method public delete($dao)
   * Delete a DAO from the database
   * @param $dao
   * @return true/false
   */
  public function delete($dao)
    {
    $instanc .= ucfirst($this->_name)."Dao";
    if(get_class($dao) !=  $instance)
      {
      throw new Zend_Exception("Should be an object (".$instance."). It was: ".get_class($dao) );
      }
    if(!$dao->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }

    if(!isset($this->_key) || !$this->_key)
      {
      throw new Zend_Exception("MIDASDatabaseCassandra::delete() : Cannot delete record by something other than a key." );
      return false;
      }

    $ke .= $dao->getKey();
    if(!isset($key))
      {
      throw new Zend_Exception("Unable to find the key" );
      }

    try
      {
      $cf = new ColumnFamily($this->_db, $this->_name);
      $cf->remove($key);
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }

    $key = $dao->_key;
    unset($dao->$key);
    $dao->save .= false;
    return true;
    } // end function delete

  /** return the number row in the table
   * @return int */
  public function getCountAll()
    {
    // The idea is to use Cassandra's counter in the future (implemented in 0.8)
    // We could also use memcached or redis or even a file lock mechanism (ifthe php
    // server is not fully distributed
    return 0;
    }//end getCountAll


  /**
   * @method public  get()
   * Generic get function. You can define custom function.
   * @param $var name of the element we want to get
   * @param $key of the table
   * @return value
   */
  public function getValue($var, $key, $dao)
    {
    if(!isset($this->_mainData[$var]))
      {
      throw new Zend_Exception("Database Cassandra " . $this->_name . ": var ".$var." is not defined here.");
      }

    if(method_exists($this, 'get' . ucfirst($var)))
      {
      return call_user_func('get' . ucfirst($var), $key, $var);
      }
    else if($this->_mainData[$var]['type'] == MIDAS_DATA && $key .= null)
      {
      try
        {
        $columnfamily = new ColumnFamily($this->_db, $this->_name);
        $resultarray = $columnfamily->get($key); // retrieve only what we want
        if(!isset($resultarray[$var]))
          {
          //echo 'MIDASDatabaseCassandra::getValue() MIDAS_DATA not found. CF='.$this->_name.' and va .= '.$var;
          //throw new Zend_Exception('MIDASDatabaseCassandra::getValue() MIDAS_DATA not found. CF='.$this->_name.' and va .= '.$var);
          return null;
          }
        return $resultarray[$var];
        }
      catch(cassandra_NotFoundException $e)
        {
        throw new Zend_Exception('MIDASDatabaseCassandra::getValue() MIDAS_DATA not found.  CF='.$this->_name.' and va .= '.$var);
        return null;
        }
      catch(Exception $e)
        {
        throw new Zend_Exception($e);
        }

      }
    else if($this->_mainData[$var]['type'] == MIDAS_ONE_TO_MANY)
      {
      require_once BASE_PATH . '/core/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
      if(!$dao->get($this->_mainData[$var]['parent_column']))
        {
        throw new Zend_Exception($this->_mainData[$var]['parent_column']. " is not defined in the dao: ".get_class($dao));
        }
      throw new Zend_Exception('MIDASDatabaseCassandra::getValue() MIDAS_ONE_TO_MANY not defined yet. You can implement it ifyou want :)');

      //return $model->__call("findBy" . ucfirst($this->_mainData[$var]['child_column']), array($dao->get($this->_mainData[$var]['parent_column'])));
      }
    else if($this->_mainData[$var]['type'] == MIDAS_MANY_TO_ONE)
      {
      require_once BASE_PATH . '/core/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
      if(!method_exists($model, 'getBy'.ucfirst($this->_mainData[$var]['child_column'])))
        {
        throw new Zend_Exception(get_class($model).'::getBy'.ucfirst($this->_mainData[$var]['child_column'])." is not implemented");
        }
      return call_user_func(array($model, 'getBy'.ucfirst($this->_mainData[$var]['child_column'])),
                            $dao->get($this->_mainData[$var]['parent_column']));
      }
    else if($this->_mainData[$var]['type'] == MIDAS_MANY_TO_MANY)
      {
      require_once BASE_PATH . '/core/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $model = $this->ModelLoader->loadModel($dao->_model);
      if(!method_exists($model, 'get'.ucfirst($var)))
        {
        throw new Zend_Exception(get_class($model).'::get'.ucfirst($var)." is not implemented");
        }
      $mainColumn = $this->_mainData[$var]['parent_column'];
      return call_user_func(array($model, 'get'.ucfirst($var)), $dao->$mainColumn);
      }
    else
      {
      throw new Zend_Exception('MIDASDatabaseCassandra: getValue() Unable to load data type ' . $var);
      }
    }

  /** Helper function for cassandra */
  function getCassandra($columnfamily, $key, $columns = null, $column_start = "", $column_finish = "")
    {
    try
      {
      $cf = new ColumnFamily($this->_db, $columnfamily);
      return $cf->get($key, $columns, $column_start, $column_finish);
      }
    catch(cassandra_NotFoundException $e)
      {
      return array();
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    } // end getCassandra()

  /** multiget */
  function multigetCassandra($columnfamily, $keys, $columns = null, $column_start = "", $column_finish = "")
    {
    try
      {
      $cf = new ColumnFamily($this->_db, $columnfamily);
      return $cf->multiget($keys, $columns, $column_start, $column_finish);
      }
    catch(cassandra_NotFoundException $e)
      {
      return array();
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    } // end getCassandra()

} // end class MIDASDatabaseCassandra
?>
