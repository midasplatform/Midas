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
 * DEPRECATED: Use MidasLoader static methods loadModel() and loadModels() instead.
 */
class MIDAS_ModelLoader
  {
  /**
   * \fn public  loadModels()
   * \brief Loads models (array or string)
   */
  public function loadModels($models, $module = '')
    {
    if(is_string($models))
      {
      $this->loadModel($models, $module);
      }
    else if(is_array($models))
      {
      foreach($models as $model)
        {
        $this->loadModel($model, $module);
        }
      }
    }

  /**
   * \fn public  loadModel()
   * \brief Loads a model
   */
  public function loadModel($model, $module = '')
    {
    $models = Zend_Registry::get('models');

    if(!isset($models[$module.$model]))
      {
      if($module == '')
        {
        if(file_exists(BASE_PATH.'/core/models/base/'.$model.'ModelBase.php'))
          {
          include_once BASE_PATH.'/core/models/base/'.$model.'ModelBase.php';
          }
        include_once BASE_PATH.'/core/models/pdo/'.$model.'Model.php';
        $name = $model . 'Model';
        }
      else
        {
        if(file_exists(BASE_PATH.'/modules/'.$module.'/models/base/'.$model.'ModelBase.php'))
          {
          include_once BASE_PATH.'/modules/'.$module.'/models/base/'.$model.'ModelBase.php';
          }
        else if(file_exists(BASE_PATH.'/privateModules/'.$module.'/models/base/'.$model.'ModelBase.php'))
          {
          include_once BASE_PATH.'/privateModules/'.$module.'/models/base/'.$model.'ModelBase.php';
          }

        if(file_exists(BASE_PATH.'/modules/'.$module.'/models/pdo/'.$model.'Model.php'))
          {
          include_once BASE_PATH.'/modules/'.$module.'/models/pdo/'.$model.'Model.php';
          }
        else if(file_exists(BASE_PATH.'/privateModules/'.$module.'/models/pdo/'.$model.'Model.php'))
          {
          include_once BASE_PATH.'/privateModules/'.$module.'/models/pdo/'.$model.'Model.php';
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
  }
