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
