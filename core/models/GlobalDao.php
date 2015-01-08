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
 * Generic DAO base class.
 *
 * @package Core\DAO
 */
class MIDAS_GlobalDao
{
    /** @var string */
    protected $key;

    /** Constructor. */
    public function __construct()
    {
        if ($this->getModel()->getKey() !== '') {
            $this->_key = $this->getModel()->getKey();
        } else {
            $this->_key = false;
        }
        $this->saved = false;
        $this->loadElements();
    }

    /** Load the components. */
    public function loadElements()
    {
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
    }

    /**
     * Initialize the DAO from the primary key.
     *
     * @param string $key primary key with which to initialize the DAO
     * @return bool true if the DAO is successfully initialized
     * @throws Zend_Exception
     */
    public function initValues($key)
    {
        if (!isset($key)) {
            throw new Zend_Exception("Model ".$this->getModel()->_name.": key is not defined here.");
        }
        $values = $this->getModel()->getValues($key);
        if ($values == null) {
            return false;
        }

        $maindata = $this->getModel()->getMainData();
        foreach ($maindata as $name => $type) {
            if (isset($values->$name) && $type['type'] == MIDAS_DATA) {
                $this->$name = $values->$name;
            }
        }

        return true;
    }

    /**
     * Return the names and values of the fields of the DAO as an associative array.
     *
     * @return array
     */
    public function toArray()
    {
        $return = array();
        $maindata = $this->getModel()->getMainData();
        foreach ($maindata as $name => $type) {
            if (isset($this->$name) && $type['type'] == MIDAS_DATA) {
                $return[$name] = $this->$name;
            } elseif ($type['type'] == MIDAS_DATA) {
                $return[$name] = null;
            }
        }

        return $return;
    }

    /**
     * Return the value of the key field of the DAO.
     *
     * @return mixed
     * @throws Zend_Exception
     */
    public function getKey()
    {
        if ($this->_key == false) {
            throw new Zend_Exception("Model  ".$this->getModel()->getName().": key is not defined here.");
        }
        $key = $this->getModel()->getKey();

        return $this->get($key);
    }

    /**
     * Return the value of a field of the DAO.
     *
     * @param string $var name of the field of which to return the value
     * @return mixed
     * @throws Zend_Exception
     */
    public function get($var)
    {
        $maindata = $this->getModel()->getMainData();
        if (!isset($maindata[$var])) {
            throw new Zend_Exception(
                "Model ".$this->getModel()->getName().": var ".$var." is not defined here."
            );
        }
        if (method_exists($this, 'get'.ucfirst($var))) {
            $name = 'get'.ucfirst($var);

            return $this->$name($var);
        } elseif (isset($this->$var)) {
            return $this->$var;
        } else {
            $key = $this->_key;
            if (!isset($this->$key)) {
                return $this->getModel()->getValue($var, null, $this);
            }

            return $this->getModel()->getValue($var, $this->$key, $this);
        }
    }

    /**
     * Set the value of a field of the DAO.
     *
     * @param string $var name of the field of which to set the value
     * @param mixed $value value of the field
     * @return void|mixed
     * @throws Zend_Exception
     */
    public function set($var, $value)
    {
        $maindata = $this->getModel()->getMainData();
        if (!isset($maindata[$var])) {
            throw new Zend_Exception(
                "Model ".$this->getModel()->getName().": var ".$var." is not defined here."
            );
        }
        if (method_exists($this, 'set'.ucfirst($var))) {
            return call_user_func('set'.ucfirst($var), $var, $value);
        }
        $this->$var = $value;
    }

    /**
     * Return a model.
     *
     * @param string|null $name name of the model
     * @return mixed|MIDASModel
     */
    public function getModel($name = null)
    {
        if ($name != null) {
            if (isset($this->_module)) {
                return MidasLoader::loadModel($name, $this->_module);
            }

            return MidasLoader::loadModel($name);
        }
        if (isset($this->_module)) {
            return MidasLoader::loadModel($this->_model, $this->_module);
        }

        return MidasLoader::loadModel($this->_model);
    }

    /**
     * Fetch the logger from the Zend registry.
     *
     * @return Zend_Log
     */
    public function getLogger()
    {
        return Zend_Registry::get('logger');
    }

    /**
     * Invoke inaccessible methods.
     *
     * @param string $method name of the method
     * @param array $params parameters of the method
     * @return mixed
     * @throws Zend_Exception
     */
    public function __call($method, $params)
    {
        if (substr($method, 0, 3) == 'get') {
            $var = $this->_getRealName(substr($method, 3));
            $maindata = $this->getModel()->getMainData();
            if (isset($maindata[$var])) {
                return $this->get($var);
            } else {
                throw new Zend_Exception(
                    "Dao:  ".__CLASS__.": method ".$method." doesn't exist (".strtolower(
                        substr($method, 3)
                    )." is not defined."
                );
            }
        } elseif (substr($method, 0, 3) == 'set') {
            $var = $this->_getRealName(substr($method, 3));
            $maindata = $this->getModel()->getMainData();
            if (isset($maindata[$var])) {
                return $this->set($var, $params[0]);
            } else {
                throw new Zend_Exception(
                    "Dao:  ".__CLASS__.": method ".$method." doesn't exist (".strtolower(
                        substr($method, 3)
                    )." is not defined."
                );
            }
        } else {
            throw new Zend_Exception("Dao:  ".__CLASS__.": method ".$method." doesn't exist.");
        }
    }

    /**
     * Return a method name given the name of an item of data in a model.
     *
     * @param string $var name of an item of data
     * @return string
     */
    private function _getRealName($var)
    {
        $return = "";
        preg_match_all('/[A-Z][^A-Z]*/', $var, $results);

        foreach ($results[0] as $key => $r) {
            if ($key == 0) {
                $return .= strtolower($r);
            } else {
                $return .= '_'.strtolower($r);
            }
        }

        return $return;
    }
}
