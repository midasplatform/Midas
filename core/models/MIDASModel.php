<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** global midas model library */
class MIDASModel
{
    /** @var MIDASDatabasePdo */
    protected $database;

    /** @var string */
    protected $_name = '';

    /** @var string */
    protected $_key = '';

    /** @var array */
    protected $_mainData = array();

    /** @var array */
    protected $_components = array();

    /** Constructor. */
    public function __construct()
    {
        $this->database = new MIDASDatabasePdo();
    }

    /** Initialize this model. */
    public function initialize()
    {
        $this->loadElements(); // load the components for the models
        $this->database->initialize($this->_name, $this->_key, $this->_mainData);
    }

    /**
     * get the midas temporary directory.
     *
     * @param string $subDirectory
     * @param bool $createDirectory
     * @return string
     */
    protected function getTempDirectory($subDirectory = 'misc', $createDirectory = true)
    {
        return UtilityComponent::getTempDirectory($subDirectory, $createDirectory);
    }

    /**
     * Save a DAO to the database.
     * If you want to explicitly save null and unset fields as "NULL" in the database,
     * set the member "setExplicitNullFields" on the dao to true.
     *
     * @param MIDAS_GlobalDao $dao DAO
     * @throws Zend_Exception
     */
    public function save($dao)
    {
        $instance = $this->_name.'Dao';
        if (isset($this->_daoName) && isset($this->moduleName)) {
            $instance = ucfirst($this->moduleName).'_'.$this->_daoName;
        }
        if (!$dao instanceof $instance) {
            throw new Zend_Exception('Should be an object of type '.$instance.', was type '.get_class($dao));
        }

        $dataarray = array();
        foreach ($this->_mainData as $key => $var) {
            if (isset($dao->$key)) {
                $dataarray[$key] = $dao->$key;
            } elseif (isset($dao->setExplicitNullFields) && $dao->setExplicitNullFields && $this->_mainData[$key]['type'] == MIDAS_DATA) {
                $dataarray[$key] = null;
            }
        }

        $insertedid = $this->database->save($dataarray);

        if ($insertedid !== false) {
            if (isset($this->_key) && !empty($this->_key)) {
                $key = $this->_key;
                $dao->$key = $insertedid;
            }
            $dao->saved = true;
        }
    }

    /**
     * Delete a DAO.
     *
     * @param MIDAS_GlobalDao $dao DAO
     * @throws Zend_Exception
     */
    public function delete($dao)
    {
        $this->database->delete($dao);
    }

    /** Load components. */
    public function loadElements()
    {
        Zend_Registry::set('components', array());
        if (isset($this->_components)) {
            foreach ($this->_components as $component) {
                $nameComponent = $component.'Component';

                Zend_Loader::loadClass($nameComponent, BASE_PATH.'/core/controllers/components');
                if (!isset($this->Component)) {
                    $this->Component = new stdClass();
                }
                $this->Component->$component = new $nameComponent();
            }
        }
    }

    /**
     * Return the key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the main data.
     *
     * @return array
     */
    public function getMainData()
    {
        return $this->_mainData;
    }

    /**
     * Get Logger.
     *
     * @return Zend_Log
     */
    public function getLogger()
    {
        return Zend_Registry::get('logger');
    }

    /**
     * Initialize a DAO.
     *
     * @param string $name name of the DAO
     * @param array|Zend_Db_Table_Row_Abstract $data array or table row of values
     * @param string $module
     * @returns false|MIDAS_GlobalDao
     * @throws Zend_Exception
     */
    public function initDao($name, $data, $module = null, $removeOld = false)
    {
        // If no data found we return false
        if (!$data) {
            return false;
        }

        if ($module == null) {
            $name = $name.'Dao';
            Zend_Loader::loadClass($name, BASE_PATH.'/core/models/dao');
        } else {
            if (file_exists(BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'Dao.php')) {
                require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'Dao.php';
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'Dao.php')) {
                require_once BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'Dao.php';
            } else {
                throw new Zend_Exception('Unable to find dao file '.$name);
            }
            $name = ucfirst($module).'_'.$name.'Dao';
        }
        if (class_exists($name)) {
            $obj = new $name();
            $model = $obj->getModel();
            foreach ($model->_mainData as $name => $option) {
                if (isset($data[$name])) {
                    $obj->$name = $data[$name];
                }
                else {
                    if ($removeOld) {
                        unset($model->_mainData[$name]);
                    }
                }
            }
            $obj->saved = true;

            return $obj;
        } else {
            throw new Zend_Exception('Unable to load dao '.$name);
        }
    }

    /**
     * Catch if the method does not exist and create a method dynamically.
     *
     * @param string $method method name
     * @param array $params array of param
     * @return mixed return the result of the function dynamically created
     * @throws Zend_Exception
     */
    public function __call($method, $params)
    {
        if (substr($method, 0, 5) == 'getBy') {
            throw new Zend_Exception(
                __CLASS__.' '.$this->_name.': '.$method.' has been deprecated. Please fix.'
            );
        } elseif (substr($method, 0, 6) == 'findBy') {
            if (isset($this->_mainData[strtolower(substr($method, 6))])) {
                return $this->findBy(strtolower(substr($method, 6)), $params[0]);
            } else {
                throw new Zend_Exception(
                    'Dao:  '.__CLASS__.' '.$this->_name.': method '.$method." doesn't exist (".strtolower(
                        substr(
                            $method,
                            6
                        )
                    ).' is not defined.'
                );
            }
        } else {
            throw new Zend_Exception($this->_name.'Model : method '.$method." doesn't exist.");
        }
    }

    /**
     * find all DAO by $var = $value.
     *
     * @param string $var name of the attribute we search
     * @param mixed $value
     * @return array DAOs
     * @throws Zend_Exception
     */
    public function findBy($var, $value)
    {
        if (!isset($this->_mainData[$var])) {
            throw new Zend_Exception('Model PDO '.$this->_name.': var '.$var.' is not defined here.');
        } else {
            $module = '';
            if (isset($this->moduleName)) {
                $module = $this->moduleName;
            }
            $rowset = $this->database->findBy($var, $value);
            $return = array();

            // if there are any rows, set the daoName
            if (isset($rowset) && count($rowset) > 0) {
                if (isset($this->_daoName)) {
                    $daoName = substr($this->_daoName, 0, strlen($this->_daoName) - 3);
                } else {
                    // can't just convert the name to dao name, in case it is in a module
                    if (isset($this->moduleName)) {
                        // we want to split the string, expecting 2 parts, module_model
                        // just use the model name for the dao
                        $parts = explode('_', $this->_name);
                        $daoName = ucfirst($parts[1]);
                    } else {
                        // if no module, just upper case the model name
                        $daoName = ucfirst($this->_name);
                    }
                }
            }
            foreach ($rowset as $row) {
                $tmpDao = $this->initDao($daoName, $row, $module);
                $return[] = $tmpDao;
                unset($tmpDao);
            }

            return $return;
        }
    }

    /**
     * @deprecated Use MidasLoader::newDao to load the class and instantiate a DAO
     * @param string $name
     * @param string $module
     * @throws Zend_Exception
     */
    public function loadDaoClass($name, $module = 'core')
    {
        if ($module == 'core') {
            Zend_Loader::loadClass($name, BASE_PATH.'/core/models/dao');
            if (!class_exists($name)) {
                throw new Zend_Exception('Unable to load dao class '.$name);
            }
        } else {
            if (file_exists(BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'Dao.php')) {
                require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'Dao.php';
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'Dao.php')) {
                require_once BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'Dao.php';
            }
            if (file_exists(BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'.php')) {
                require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'.php';
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'.php')) {
                require_once BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'.php';
            } else {
                throw new Zend_Exception('Unable to find dao file '.$name);
            }

            if (!class_exists(ucfirst($module).'_'.$name)) {
                throw new Zend_Exception('Unable to load dao class '.ucfirst($module).'_'.$name);
            }
        }
    }

    /**
     * Load a dao.
     *
     * @param null|array|mixed $key
     * @return array|bool|MIDAS_GlobalDao|mixed
     * @throws Zend_Exception
     */
    public function load($key = null)
    {
        if (isset($this->_daoName)) {
            $name = $this->_daoName;
        } else {
            $name = ucfirst($this->_name).'Dao';
        }
        if (isset($this->_daoName) && isset($this->moduleName)) {
            $dao = MidasLoader::newDao($name, $this->moduleName);
        } elseif (isset($this->moduleName)) {
            $dao = MidasLoader::newDao(ucfirst(substr($name, strpos($name, '_') + 1)), $this->moduleName);
        } else {
            $dao = MidasLoader::newDao($name);
        }
        if (!isset($this->_key) && $key != null) {
            throw new Zend_Exception(
                'MIDASDatabasePDO '.$this->_name.': key is not defined here. (you should write your own load method)'
            );
        }
        if (is_array($key)) {
            if (empty($key)) {
                return array();
            }
            if (empty($key)) {
                return array();
            }
            unset($dao);
            $rowset = $this->database->getAllByKey($key);
            $return = array();
            foreach ($rowset as $row) {
                $tmpDao = $this->initDao(ucfirst($this->_name), $row);
                $return[] = $tmpDao;
                unset($tmpDao);
            }

            return $return;
        } else {
            if ($key !== null && method_exists($dao, 'initValues')) {
                if (!$dao->initValues($key)) {
                    unset($dao);

                    return false;
                }
                $dao->saved = true;
            }

            return $dao;
        }
    }

    /**
     * Generic get function. You can define custom function.
     *
     * @param string $var name of the element we want to get
     * @param string $key key of the table
     * @param MIDAS_GlobalDao $dao DAO
     * @return mixed
     * @throws Zend_Exception
     */
    public function getValue($var, $key, $dao)
    {
        return $this->database->getValue($var, $key, $dao);
    }

    /**
     * Function getValues.
     *
     * @param $key
     * @return array
     */
    public function getValues($key)
    {
        return $this->database->getValues($key);
    }

    /**
     * Returns the number of rows.
     *
     * @return int
     */
    public function getCountAll()
    {
        return $this->database->getCountAll();
    }

    /**
     * Compare two DAO (only the MIDAS_DATA.
     *
     * @param $dao1
     * @param $dao2
     * @param bool $juggleTypes
     * @return bool true if they are the same one
     */
    public function compareDao($dao1, $dao2, $juggleTypes = false)
    {
        if (!is_object($dao1) || !is_object($dao2)) {
            return false;
        }
        foreach ($this->_mainData as $name => $data) {
            if ($data['type'] == MIDAS_DATA) {
                if ($juggleTypes) {
                    if ($dao1->get($name) != $dao2->get($name)) {
                        return false;
                    }
                } else {
                    if ($dao1->get($name) !== $dao2->get($name)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
