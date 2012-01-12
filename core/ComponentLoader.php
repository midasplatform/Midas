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
 * \class MIDAS_ComponentLoader
 * \brief Create component object
 */
class MIDAS_ComponentLoader
  {
  /**
   * \fn public loadComponents()
   * \brief Loads components (array or string)
   */
  public function loadComponents($components, $module = '')
    {
    if(is_string($components))
      {
      $this->loadComponent($components, $module);
      }
    elseif(is_array($components))
      {
      foreach($components as $component)
        {
        $this->loadComponent($component, $module);
        }
      }
    }

  /**
   * \fn public loadComponent()
   * \brief Loads a component
   */
  public function loadComponent($component, $module = '')
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
  }
