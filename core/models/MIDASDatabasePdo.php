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
 * PDO database interface.
 *
 * @package Core\Database
 */
class MIDASDatabasePdo extends Zend_Db_Table_Abstract implements MIDASDatabaseInterface
{
    /** @var string */
    protected $_name;

    /** @var array */
    protected $_mainData;

    /** @var string */
    protected $_key;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Initialize the PDO database interface.
     *
     * @param string $name
     * @param string $key
     * @param array $data
     * @throws Zend_Exception
     */
    public function initialize($name, $key, $data)
    {
        $this->_name = $name;
        $this->_mainData = $data;
        $this->_key = $key;

        if (!isset($this->_name)) {
            throw new Zend_Exception("a Model PDO is not defined properly.");
        }
        if (!isset($this->_mainData)) {
            throw new Zend_Exception("Model PDO ".$this->_name." is not defined properly.");
        }
    }

    /**
     * Return the database.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDB()
    {
        return $this->_db;
    }

    /**
     * Generic get function. You can define custom functions.
     *
     * @param string $var name of the element we want to get
     * @param string $key key of the table
     * @param MIDAS_GlobalDao $dao
     * @return null|mixed
     * @throws Zend_Exception
     */
    public function getValue($var, $key, $dao)
    {
        if (!isset($this->_mainData[$var])) {
            throw new Zend_Exception("Database PDO ".$this->_name.": var ".$var." is not defined here.");
        }
        if (method_exists($this, 'get'.ucfirst($var))) {
            return call_user_func('get'.ucfirst($var), $key, $var);
        } elseif ($this->_mainData[$var]['type'] == MIDAS_DATA && $key != null) {
            $result = $this->fetchRow($this->select()->where($this->_key.' = ?', $key));
            if (!isset($result->$var)) {
                return null;
            }

            return $result->$var;
        } elseif ($this->_mainData[$var]['type'] == MIDAS_ONE_TO_MANY) {
            $module = '';
            if (isset($this->_mainData[$var]['module']) && $this->_mainData[$var]['module'] != 'core') {
                $module = $this->_mainData[$var]['module'];
            }
            $model = MidasLoader::loadModel($this->_mainData[$var]['model'], $module);
            if (!$dao->get($this->_mainData[$var]['parent_column'])) {
                throw new Zend_Exception(
                    $this->_mainData[$var]['parent_column']." is not defined in the dao: ".get_class($dao)
                );
            }

            return $model->__call(
                "findBy".ucfirst($this->_mainData[$var]['child_column']),
                array($dao->get($this->_mainData[$var]['parent_column']))
            );
        } elseif ($this->_mainData[$var]['type'] == MIDAS_MANY_TO_ONE) {
            $module = '';
            if (isset($this->_mainData[$var]['module']) && $this->_mainData[$var]['module'] != 'core') {
                $module = $this->_mainData[$var]['module'];
            }
            $model = MidasLoader::loadModel($this->_mainData[$var]['model'], $module);
            $key = $model->getKey();
            if ($this->_mainData[$var]['child_column'] == $key) {
                return $model->load($dao->get($this->_mainData[$var]['parent_column']));
            }
            if (!method_exists($model, 'getBy'.ucfirst($this->_mainData[$var]['child_column']))
            ) {
                throw new Zend_Exception(
                    get_class($model).'::getBy'.ucfirst(
                        $this->_mainData[$var]['child_column']
                    )." is not implemented"
                );
            }

            return call_user_func(
                array($model, 'getBy'.ucfirst($this->_mainData[$var]['child_column'])),
                $dao->get($this->_mainData[$var]['parent_column'])
            );
        } elseif ($this->_mainData[$var]['type'] == MIDAS_MANY_TO_MANY) {
            return $this->getLinkedObject($var, $dao);
        } else {
            throw new Zend_Exception('Unable to load data type '.$var);
        }
    }

    /**
     * Get linked objects.
     *
     * @param string $var what object
     * @param MIDAS_GlobalDao $dao using DAO data
     * @return array array of objects
     */
    protected function getLinkedObject($var, $dao)
    {
        if (isset($this->_mainData[$var]['module'])) {
            $model = MidasLoader::loadModel($this->_mainData[$var]['model'], $this->_mainData[$var]['module']);
        } else {
            $model = MidasLoader::loadModel($this->_mainData[$var]['model']);
        }

        $parentColumn = $this->_mainData[$var]['parent_column'];
        $sql = $this->select()->setIntegrityCheck(false)->from($model->getName())->joinUsing(
            $this->_mainData[$var]['table'],
            $this->_mainData[$var]['child_column']
        )->where($this->_mainData[$var]['parent_column'].' = ?', $dao->$parentColumn);
        $rowset = $this->fetchAll($sql);

        $return = array();
        foreach ($rowset as $row) {
            if (isset($this->_mainData[$var]['module'])) {
                $return[] = $model->initDao($this->_mainData[$var]['model'], $row, $this->_mainData[$var]['module']);
            } else {
                $return[] = $model->initDao($this->_mainData[$var]['model'], $row);
            }
        }

        return $return;
    }

    /**
     * Create a link between two tables.
     *
     * @param string $var name of the attribute we search
     * @param MIDAS_GlobalDao $daoParent
     * @param MIDAS_GlobalDao $daoSon
     * @return false|int SQL result
     */
    public function link($var, $daoParent, $daoSon)
    {
        $objs = $daoParent->get($var);

        if (isset($this->_mainData[$var]['module'])) {
            $model = MidasLoader::loadModel($this->_mainData[$var]['model'], $this->_mainData[$var]['module']);
        } else {
            $model = MidasLoader::loadModel($this->_mainData[$var]['model']);
        }
        foreach ($objs as $obj) {
            if ($model->compareDao($obj, $daoSon)) {
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
        $select = $db->select()->from($this->_mainData[$var]['table'], array('nrows' => 'COUNT(*)'))->where(
            $parentcolumn."=?",
            $data[$this->_mainData[$var]['parent_column']]
        )->where($childcolumn."=?", $data[$this->_mainData[$var]['child_column']]);

        $row = $db->fetchRow($select);
        if ($row['nrows'] == 0) {
            return $db->insert($this->_mainData[$var]['table'], $data);
        }

        return false;
    }

    /**
     * Remove a link between two tables.
     *
     * @param string $var name of the attribute we search
     * @param MIDAS_GlobalDao $daoParent
     * @param MIDAS_GlobalDao $daoSon
     * @return int SQL result
     */
    public function removeLink($var, $daoParent, $daoSon)
    {
        unset($daoParent->$var);
        $data = array();

        $data[$this->_mainData[$var]['parent_column']] = $daoParent->get($this->_mainData[$var]['parent_column']);
        $data[$this->_mainData[$var]['child_column']] = $daoSon->get($this->_mainData[$var]['child_column']);
        $db = Zend_Registry::get('dbAdapter');

        return $db->delete(
            $this->_mainData[$var]['table'],
            array(
                $this->_mainData[$var]['parent_column'].' = ?' => $daoParent->get(
                    $this->_mainData[$var]['parent_column']
                ),
                $this->_mainData[$var]['child_column'].' = ?' => $daoSon->get($this->_mainData[$var]['child_column']),
            )
        );
    }

    /**
     * Find all DAOs by $var = $value.
     *
     * @param string $var name of the attribute we search
     * @param mixed $value
     * @return array list of DAOs
     */
    public function findBy($var, $value)
    {
        return $this->fetchAll($this->select()->where($var.' = ?', $value));
    }

    /**
     * Find all DAOs.
     *
     * @param string $modelName
     * @param string $module
     * @return array list of DAOs
     */
    public function getAll($modelName, $module = '')
    {
        $rowset = $this->fetchAll($this->select());
        $return = array();
        $model = MidasLoader::loadModel($modelName, $module);
        foreach ($rowset as $row) {
            $return[] = $model->initDao($modelName, $row, $module);
        }

        return $return;
    }

    /**
     * Get all the values of a model.
     *
     * @param string $key
     * @return array list of values
     */

    public function getValues($key)
    {
        return $this->fetchRow($this->select()->where($this->_key.' = ?', $key));
    }

    /**
     * Save or update.
     *
     * @param $dataArray array with dao information
     * @return false|int
     */
    public function save($dataArray)
    {
        if (isset($this->_key) && isset($dataArray[$this->_key])) {
            $key = $dataArray[$this->_key];
            unset($dataArray[$this->_key]);
            $numUpdated = $this->update($dataArray, array($this->_key.'=?' => $key));
            if ($numUpdated == 0) {
                return false;
            }

            return $key;
        } else {
            $insertedId = $this->insert($dataArray);

            if (!$insertedId) {
                return false;
            }

            return $insertedId;
        }
    }

    /**
     * Delete from the database.
     *
     * @param MIDAS_GlobalDao $dao
     * @return true
     * @throws Zend_Exception
     */
    public function delete($dao)
    {
        if (!$dao->saved) {
            throw new Zend_Exception("The dao should be saved first ...");
        }
        if (!isset($this->_key) || !$this->_key) {
            $query = array();
            foreach ($this->_mainData as $name => $option) {
                if ($option['type'] == MIDAS_DATA) {
                    $query[$name.' = ?'] = $dao->$name;
                }
            }
            if (empty($query)) {
                throw new Zend_Exception("Huge error, you almost deleted everything");
            }
            parent::delete($query);
            $dao->saved = false;

            return true;
        }
        $key = $dao->getKey();
        if (!isset($key)) {
            throw new Zend_Exception("Unable to find the key");
        }
        parent::delete(array($this->_key.' = ?' => $dao->getKey()));
        $key = $dao->_key;
        $dao->set($key, null);
        $dao->saved = false;

        return true;
    }

    /**
     * Return all by key.
     *
     * @param array $keys
     * @return array
     */
    public function getAllByKey($keys)
    {
        // Make sure we have only numeric values
        foreach ($keys as $k => $v) {
            if (!is_numeric($v)) {
                unset($keys[$k]);
            }
        }
        if (empty($keys)) {
            return array();
        }

        return $this->fetchAll($this->select()->where($this->_key.' IN (?)', $keys));
    }

    /**
     * Return the number of rows in the table.
     *
     * @return int
     */
    public function getCountAll()
    {
        $row = $this->fetchRow($this->select()->from($this->_name, 'count(*) as COUNT'));

        return $row['COUNT'];
    }
}
