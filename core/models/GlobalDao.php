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
 * \class GlobalDao
 *  Global dao methods
 */
class MIDAS_GlobalDao
{
  protected $key;

  /**
   * @method public  __construct()
   *  Construct dao
   */
  public function __construct()
    {
    if($this->getModel()->getKey() !== '')
      {
      $this->_key = $this->getModel()->getKey();
      }
    else
      {
      $this->_key = false;
      }
    $this->saved = false;
    $this->loadElements();
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
        if(!isset($this->Component))
          {
          $this->Component =  new stdClass();
          }
        $this->Component->$component = new $nameComponent();
        }
      }
    }

  /**
   * @method initValues($key)
   *  Init the dao based on the primary key
   * @param $key Primary Key
   */
  public function initValues($key)
    {
    if(!isset($key))
      {
      throw new Zend_Exception("Model " . $this->getModel()->_name . ": key is not defined here.");
      }
    $values = $this->getModel()->getValues($key);
    if($values == null)
      {
      return false;
      }

    $maindata = $this->getModel()->getMainData();
    foreach($maindata as $name => $type)
      {
      if(isset($values->$name) && $type['type'] == MIDAS_DATA)
        {
        $this->$name = $values->$name;
        }
      }
    return true;
    }

  /** return values as an array*/
  public function toArray()
    {
    $return = array();
    $maindata = $this->getModel()->getMainData();
    foreach($maindata as $name => $type)
      {
      if(isset($this->$name) && $type['type'] == MIDAS_DATA)
        {
        $return[$name] = $this->$name;
        }
      }
    return $return;
    }

  /**
   * @method getKey()
   *  Return key value
   * @return key
   */
  public function getKey()
    {
    if($this->_key == false)
      {
      throw new Zend_Exception("Model  " . $this->getModel()->getName() . ": key is not defined here.");
      }
    $key = $this->getModel()->getKey();
    return $this->get($key);
    }

  /**
   * @method public  get($var)
   *  Generic get function. You can define custom function.
   * @param $var name of the element we want to get
   * @return value
   */
  public function get($var)
    {
    $maindata = $this->getModel()->getMainData();
    if(!isset($maindata[$var]))
      {
      throw new Zend_Exception("Model " . $this->getModel()->getName() . ": var ".$var." is not defined here.");
      }
    if(method_exists($this, 'get' . ucfirst($var)))
      {
      $name = 'get' . ucfirst($var);
      return $this->$name($var);
      }
    elseif(isset($this->$var))
      {
      return $this->$var;
      }
    else
      {
      $key = $this->_key;
      if(!isset($this->$key))
        {
        return $this->getModel()->getValue($var, null, $this);
        }
      return $this->getModel()->getValue($var, $this->$key, $this);
      }
    }

  /**
   * @method public  set($var, $value)
   *  Set a value
   * @param $var name of the element we want to set
   * @param $value
   */
  public function set($var, $value)
    {
    $maindata = $this->getModel()->getMainData();
    if(!isset($maindata[$var]))
      {
      throw new Zend_Exception("Model " . $this->getModel()->getName() . ": var ".$var." is not defined here.");
      }
    if(method_exists($this, 'set' . ucfirst($var)))
      {
      return call_user_func('set' . ucfirst($var), $var, $value);
      }
    $this->$var = $value;
    }

  /**
   * @method private getModel()
   *  Get Model
   * @return model
   */
  public function getModel($name = null)
    {
    require_once BASE_PATH . '/core/models/ModelLoader.php';
    $this->ModelLoader = new MIDAS_ModelLoader();
    if($name != null)
      {
      if(isset($this->_module))
        {
        return $this->ModelLoader->loadModel($name, $this->_module);
        }
      return $this->ModelLoader->loadModel($name);
      }
    if(isset($this->_module))
      {
      return $this->ModelLoader->loadModel($this->_model, $this->_module);
      }
    return $this->ModelLoader->loadModel($this->_model);
    } //end method getModel()

  /**
   * @method public function getLogger()
   * Get Logger
   * @return Zend_Log
   */
  public function getLogger()
    {
    return Zend_Registry::get('logger');
    } //end method getLogger()

  /**
   * @method public  __call($method, $params)
   *  Catch ifthe method doesn't exists and create a method dynamically
   * @param $method method name
   * @param $params array of param
   * @return return the result of the function dynamically created
   */
  public function __call($method, $params)
    {
    if(substr($method, 0, 3) == 'get')
      {
      $var = $this->_getRealName(substr($method, 3));
      $maindata = $this->getModel()->getMainData();
      if(isset($maindata[$var]))
        {
        return $this->get($var);
        }
      else
        {
        throw new Zend_Exception("Dao:  " . __CLASS__ . ": method ".$method." doesn't exist (" . strtolower(substr($method, 3)) . " is not defined.");
        }
      }
    else if(substr($method, 0, 3) == 'set')
      {
      $var = $this->_getRealName(substr($method, 3));
      $maindata = $this->getModel()->getMainData();
      if(isset($maindata[$var]))
        {
        return $this->set($var, $params[0]);
        }
      else
        {
        throw new Zend_Exception("Dao:  " . __CLASS__ . ": method ".$method." doesn't exist (" . strtolower(substr($method, 3)) . " is not defined.");
        }
      }
    else
      {
      throw new Zend_Exception("Dao:  " . __CLASS__ . ": method ".$method." doesn't exist.");
      }
    } // end __call

  /**
   * @method private _getRealName(
   * @param $var name
   * @return real var
   */
  private function _getRealName($var)
    {
    $return = "";
    preg_match_all('/[A-Z][^A-Z]*/', $var, $results);

    foreach($results[0] as $key => $r)
      {
      if($key == 0)
        {
        $return .= strtolower($r);
        }
      else
        {
        $return .= '_' . strtolower($r);
        }
      }
    return $return;
    }

}// end class
?>
