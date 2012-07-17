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
 * This utility class has static methods used to load Model, Component, and Dao objects.
 * It handles importing the required file and instantiating the object of the desired type.
 */
class MidasLoader
{
  /**
   * Load a component
   * @param component The name of the component to load
   * @param module (Optional) The name of the module to load the component from. Defaults to core.
   */
  public static function loadComponent($component, $module = '')
    {
    $components = Zend_Registry::get('components');
    if(!isset($components[$module.$component]))
      {
      if($module == '')
        {
        include_once BASE_PATH.'/core/controllers/components/'.$component.'Component.php';
        $name = $component . 'Component';
        }
      else
        {
        if(file_exists(BASE_PATH.'/modules/'.$module.'/controllers/components/'.$component.'Component.php'))
          {
          include_once BASE_PATH.'/modules/'.$module.'/controllers/components/'.$component.'Component.php';
          }
        elseif(file_exists(BASE_PATH.'/privateModules/'.$module.'/controllers/components/'.$component.'Component.php'))
          {
          include_once BASE_PATH.'/privateModules/'.$module.'/controllers/components/'.$component.'Component.php';
          }
        else
          {
          throw new Zend_Exception("Component file doesn't exit");
          }
        $name = ucfirst($module).'_'.$component.'Component';
        }
      if(class_exists($name))
        {
        $components[$module.$component] = new $name;
        Zend_Registry::set('components', $components);
        }
      else
        {
        throw new Zend_Exception('Unable to load class '.$name);
        }
      }
    return $components[$module.$component];
    }

  /**
   * Load a model
   * @param model The name of the model to load
   * @param module (Optional) The name of the module to load the model from. Defaults to core.
   */
  public static function loadModel($model, $module = '')
    {
    $databaseType = Zend_Registry::get('configDatabase')->database->type;
    $models = Zend_Registry::get('models');

    if(!isset($models[$module.$model]))
      {
      if($module == '')
        {
        if(file_exists(BASE_PATH.'/core/models/base/'.$model.'ModelBase.php'))
          {
          include_once BASE_PATH.'/core/models/base/'.$model.'ModelBase.php';
          }
        include_once BASE_PATH.'/core/models/'.$databaseType.'/'.$model.'Model.php';
        $name = $model . 'Model';
        }
      else
        {
        if(file_exists(BASE_PATH.'/modules/'.$module.'/models/base/'.$model.'ModelBase.php'))
          {
          include_once BASE_PATH.'/modules/'.$module.'/models/base/'.$model.'ModelBase.php';
          }
        elseif(file_exists(BASE_PATH.'/privateModules/'.$module.'/models/base/'.$model.'ModelBase.php'))
          {
          include_once BASE_PATH.'/privateModules/'.$module.'/models/base/'.$model.'ModelBase.php';
          }

        if(file_exists(BASE_PATH.'/modules/'.$module.'/models/'.$databaseType.'/'.$model.'Model.php'))
          {
          include_once BASE_PATH.'/modules/'.$module.'/models/'.$databaseType.'/'.$model.'Model.php';
          }
        elseif(file_exists(BASE_PATH.'/privateModules/'.$module.'/models/'.$databaseType.'/'.$model.'Model.php'))
          {
          include_once BASE_PATH.'/privateModules/'.$module.'/models/'.$databaseType.'/'.$model.'Model.php';
          }
        else
          {
          throw new Zend_Exception("Unable to find model file ".$model);
          }

        $name = ucfirst($module).'_'.$model.'Model';
        }

      if(class_exists($name))
        {
        $models[$module.$model] = new $name;
        Zend_Registry::set('models', $models);
        }
      else
        {
        throw new Zend_Exception('Unable to load class '.$name);
        }
      }
    return $models[$module.$model];
    }

  public static function newDao($name, $module = 'core')
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
      if(file_exists(BASE_PATH.'/modules/'.$module.'/models/dao/'.$name. 'Dao.php'))
        {
        require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name. 'Dao.php';
        }
      elseif(file_exists(BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name. 'Dao.php'))
        {
        require_once BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name. 'Dao.php';
        }
      if(file_exists(BASE_PATH.'/modules/'.$module.'/models/dao/'.$name. '.php'))
        {
        require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name. '.php';
        }
      elseif(file_exists(BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name. '.php'))
        {
        require_once BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name. '.php';
        }
      else
        {
        throw new Zend_Exception("Unable to find dao file ".$name);
        }

      if(!class_exists(ucfirst($module).'_'.$name))
        {
        throw new Zend_Exception('Unable to load dao class ' . ucfirst($module).'_'.$name);
        }
      }

    }
} // end class
