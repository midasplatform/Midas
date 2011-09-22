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

/**
 *  MIDASDatabasePdo
 *  Global model methods
 */
class MIDASDatabasePdo extends Zend_Db_Table_Abstract implements MIDASDatabaseInterface
{

  protected $_name;
  protected $_mainData;
  protected $_key;

  /**
   * @method public  __construct()
   *  Construct model
   */
  public function __construct($config = array())
    {
    parent::__construct($config);
    } // end __construct()

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
    }  // end function initialize


  /** Return the database */
  public function getDB()
    {
    return $this->_db;
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
    if(!isset($this->_mainData[$var]))
      {
      throw new Zend_Exception("Database PDO " . $this->_name . ": var ".$var." is not defined here.");
      }
    if(method_exists($this, 'get' . ucfirst($var)))
      {
      return call_user_func('get' . ucfirst($var), $key, $var);
      }
    else if($this->_mainData[$var]['type'] == MIDAS_DATA && $key != null)
      {
      $result = $this->fetchRow($this->select()->where($this->_key . ' = ?', $key));
      if(!isset($result->$var))
        {
        return null;
        }
      return $result->$var;
      }
    else if($this->_mainData[$var]['type'] == MIDAS_ONE_TO_MANY)
      {
      require_once BASE_PATH . '/core/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $module = '';
      if(isset($this->_mainData[$var]['module']) && $this->_mainData[$var]['module'] != 'core')
        {
        $module = $this->_mainData[$var]['module'];
        }
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model'], $module);
      if(!$dao->get($this->_mainData[$var]['parent_column']))
        {
        throw new Zend_Exception($this->_mainData[$var]['parent_column']. " is not defined in the dao: ".get_class($dao));
        }
      return $model->__call("findBy" . ucfirst($this->_mainData[$var]['child_column']), array($dao->get($this->_mainData[$var]['parent_column'])));
      }
    else if($this->_mainData[$var]['type'] == MIDAS_MANY_TO_ONE)
      {
      require_once BASE_PATH . '/core/models/ModelLoader.php';
      $this->ModelLoader = new MIDAS_ModelLoader();
      $module = '';
      if(isset($this->_mainData[$var]['module']) && $this->_mainData[$var]['module'] == 'core')
        {
        $module = $this->_mainData[$var]['module'];
        }
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model'], $module);
      $key = $model->getKey();
      if($this->_mainData[$var]['child_column'] == $key)
        {
        return $model->load($dao->get($this->_mainData[$var]['parent_column']));
        }
      if(!method_exists($model, 'getBy'.ucfirst($this->_mainData[$var]['child_column'])))
        {
        throw new Zend_Exception(get_class($model).'::getBy'.ucfirst($this->_mainData[$var]['child_column'])." is not implemented");
        }
      return call_user_func(array($model, 'getBy'.ucfirst($this->_mainData[$var]['child_column'])),
                            $dao->get($this->_mainData[$var]['parent_column']));

      }
    else if($this->_mainData[$var]['type'] == MIDAS_MANY_TO_MANY)
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
    require_once BASE_PATH . '/core/models/ModelLoader.php';
    $this->ModelLoader = new MIDAS_ModelLoader();
    if(isset($this->_mainData[$var]['module']))
      {
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model'],
                                             $this->_mainData[$var]['module']);
      }
    else
      {
      $model = $this->ModelLoader->loadModel($this->_mainData[$var]['model']);
      }

    $parentColumn = $this->_mainData[$var]['parent_column'];
    $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from($model->getName())
            ->joinUsing($this->_mainData[$var]['table'], $this->_mainData[$var]['child_column'])
            ->where($this->_mainData[$var]['parent_column'] . ' = ?', $dao->$parentColumn);
    $rowset = $this->fetchAll($sql);

    $return = array();
    foreach($rowset as $row)
      {
      if(isset($this->_mainData[$var]['module']))
        {
        $return[] = $model->initDao($this->_mainData[$var]['model'],
                                    $row,
                                    $this->_mainData[$var]['module']);
        }
      else
        {
        $return[] = $model->initDao($this->_mainData[$var]['model'], $row);
        }
      }
    return $return;
    } //end getLinkedObject

  /**
   * @method public  getBy($var, $option)
   *  Get DAO by $var = $value
   * @param $var name of the attribute we search
   * @param $value
   * @return dao
   */
  /*public function getBy($var, $value)
    {
    return $this->fetchRow($this->select()->where($var . ' = ?', $value));
    } //end getBy*/

  /**
   * @method  function link($var, $daoParent, $daoSon)
   *  create a link between 2 tables
   * @param $var name of the attribute we search
   * @param $daoParent
   * @param $daoSon
   * @return sql result
   */
  public function link($var, $daoParent, $daoSon)
    {
    $objs = $daoParent->get($var);

    $modelloader = new MIDAS_ModelLoader();
    if(isset($this->_mainData[$var]['module']))
      {
      $model = $modelloader->loadModel($this->_mainData[$var]['model'],
                                       $this->_mainData[$var]['module']);
      }
    else
      {
      $model = $modelloader->loadModel($this->_mainData[$var]['model']);
      }
    foreach($objs as $obj)
      {
      if($model->compareDao($obj, $daoSon))
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
    $select = $db->select()->from($this->_mainData[$var]['table'], array('nrows' => 'COUNT(*)'))
                             ->where($parentcolumn."=?", $data[$this->_mainData[$var]['parent_column']])
                             ->where($childcolumn."=?", $data[$this->_mainData[$var]['child_column']]);

    $row = $db->fetchRow($select);
    if($row['nrows'] == 0)
      {
      return $db->insert($this->_mainData[$var]['table'], $data);
      }
    return false;
    } //end method link


  /**
   * @method public  removeLink($var, $daoParent, $daoSon)
   *  remove a link between 2 tables
   * @param $var name of the attribute we search
   * @param $daoParent
   * @param $daoSon
   * @return sql result
   */
  public function removeLink($var, $daoParent, $daoSon)
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
    return $this->fetchAll($this->select()->where($var . ' = ?', $value));
    } //end method findBy

  /**
   * @method public  getAll()
   *  find all DAO
   * @return daos
   */
  public function getAll($modelName)
    {
    $rowset = $this->fetchAll($this->select());
    $return = array();
    $this->ModelLoader = new MIDAS_ModelLoader();
    $model = $this->ModelLoader->loadModel($modelName);
    foreach($rowset as $row)
      {
      $return[] = $model->initDao($modelName, $row);
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
  public function save($dataarray)
    {
    if(isset($this->_key) && isset($dataarray[$this->_key]))
      {
      $key = $dataarray[$this->_key];
      unset($dataarray[$this->_key]);
      $nupdated = $this->update($dataarray, array($this->_key.'=?' => $key));
      if($nupdated == 0)
        {
        return false;
        }
      return $key;
      }
    else
      {
      $insertedid = $this->insert($dataarray);
      if(!$insertedid)
        {
        return false;
        }
      return $insertedid;
      }
    } // end method save

  /**
   * @method public delete()
   *  Delete in the db
   * @param $dao
   */
  public function delete($dao)
    {
    $instance = ucfirst($this->_name)."Dao";
    if(strtolower(get_class($dao)) !=  strtolower($instance))
      {
      throw new Zend_Exception("Should be an object (".$instance."). It was: ".get_class($dao) );
      }
    if(!$dao->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }
    if(!isset($this->_key) || !$this->_key)
      {
      $query = array();
      foreach($this->_mainData as $name => $option)
        {
        if($option['type'] == MIDAS_DATA)
          {
          $query[$name. ' = ?'] = $dao->$name;
          }
        }
      if(empty($query))
        {
        throw new Zend_Exception("Huge error, you almost deleted everything" );
        }
      parent::delete($query);
      $dao->saved = false;
      return true;
      }
    $key = $dao->getKey();
    if(!isset($key))
      {
      throw new Zend_Exception("Unable to find the key" );
      }
    parent::delete(array($this->_key . ' = ?' => $dao->getKey()));
    $key = $dao->_key;
    unset($dao->$key);
    $dao->saved = false;
    return true;
    }//end delete


  /** getAllByKey() */
  public function getAllByKey($keys)
    {
    // Make sure we have only numerics
    foreach($keys as $k => $v)
      {
      if(!is_numeric($v))
        {
        unset($keys[$k]);
        }
      }
    if(empty($keys))
      {
      return array();
      }
    return $this->fetchAll($this->select()->where($this->_key . ' IN (?)', $keys));
    }

  /** return the number row in the table
   * @return int */
  public function getCountAll()
    {
    $count = $this->fetchRow($this->select()->from($this->_name, 'count(*) as COUNT'));
    return $count['COUNT'];
    }//end getCountAll

} //end class MIDASDatabasePdo
?>
