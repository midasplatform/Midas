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
 *  Provides global function to the notification managers
 */
class MIDAS_Notification
  {

  private $_task = array();
  private $_notification = array();

    /** translation */
  protected function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    } //end method t

  /** contructor*/
  public function __construct()
    {
    $this->loadElements();
    $this->loadModuleElements();
    $this->init();
    }

  /** register task*/
  public function addTask($name, $method, $comment)
    {
    $this->_task[$name] = array('method' => $method, 'comment' => $comment);
    }// end assTask

  /** register callback*/
  public function addCallBack($name, $method)
    {
    if(isset($this->_notification[$name]))
      {
      $this->_notification[$name] = array();
      }
    $this->_notification[$name][] = array('type' => 'callback', 'call' => $method);
    }// end addCallBack

  /** register callback*/
  public function addEvent($name, $task, $priority = MIDAS_EVENT_PRIORITY_NORMAL)
    {
    if(isset($this->_notification[$name]))
      {
      $this->_notification[$name] = array();
      }
    $this->_notification[$name][] = array('type' => 'task', 'call' => $task, 'priority' => $priority);
    }// end addCallBack

  /** get Tasks */
  public function getTasks()
    {
    return $this->_task;
    }

  /** get Tasks */
  public function getNotifications()
    {
    return $this->_notification;
    }

  /**
   * Get Logger
   * @return Zend_Log
   */
  public function getLogger()
    {
    return Zend_Registry::get('logger');
    }

  /**
   * @method public  loadElements()
   *  Loads model and components
   */
  public function loadElements()
    {
    Zend_Registry::set('models', array());
    $this->ModelLoader = new MIDAS_ModelLoader();
    if(isset($this->_models))
      {
      $this->ModelLoader->loadModels($this->_models);
      }
    $modelsArray = Zend_Registry::get('models');
    foreach($modelsArray as $key => $tmp)
      {
      $this->$key = $tmp;
      }

    if(isset($this->_daos))
      {
      foreach($this->_daos as $dao)
        {
        Zend_Loader::loadClass($dao . "Dao", BASE_PATH . '/core/models/dao');
        }
      }

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

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach($this->_forms as $forms)
        {
        $nameForm = $forms . "Form";

        Zend_Loader::loadClass($nameForm, BASE_PATH . '/core/controllers/forms');
        if(!isset($this->Form))
          {
          $this->Form =  new stdClass();
          }
        $this->Form->$forms = new $nameForm();
        }
      }
    }//end loadElements

  /**
   * @method public  loadElements()
   *  Loads model and components
   */
  public function loadModuleElements()
    {
    $this->ModelLoader = new MIDAS_ModelLoader();
    if(isset($this->_moduleModels))
      {
      $this->ModelLoader->loadModels($this->_moduleModels, $this->moduleName);
      $modelsArray = Zend_Registry::get('models');
      foreach($this->_moduleModels as  $value)
        {
        if(isset($modelsArray[$this->moduleName.$value]))
          {
          $tmp = ucfirst($this->moduleName).'_'.$value;
          $this->$tmp = $modelsArray[$this->moduleName.$value];
          }
        }
      }

    if(isset($this->_moduleDaos))
      {
      foreach($this->_moduleDaos as $dao)
        {
        if(file_exists(BASE_PATH . "/modules/".$this->moduleName."/models/dao/".$dao."Dao.php"))
          {
          include_once (BASE_PATH . "/modules/".$this->moduleName."/models/dao/".$dao."Dao.php");
          }
        elseif(file_exists(BASE_PATH . "/privateModules/".$this->moduleName."/models/dao/".$dao."Dao.php"))
          {
          include_once (BASE_PATH . "/privateModules/".$this->moduleName."/models/dao/".$dao."Dao.php");
          }
        else
          {
          throw new Zend_Exception('Unable to find dao  '.$dao);
          }
        }
      }

    if(isset($this->_moduleComponents))
      {
      foreach($this->_moduleComponents as $component)
        {
        $nameComponent = ucfirst($this->moduleName).'_'.$component . "Component";
        if(file_exists(BASE_PATH . "/modules/".$this->moduleName."/controllers/components/".$component."Component.php"))
          {
          include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/components/".$component."Component.php");
          }
        elseif(file_exists(BASE_PATH . "/privateModules/".$this->moduleName."/controllers/components/".$component."Component.php"))
          {
          include_once (BASE_PATH . "/privateModules/".$this->moduleName."/controllers/components/".$component."Component.php");
          }
        else
          {
          throw new Zend_Exception('Unable to find components  '.$component);
          }
        if(!class_exists($nameComponent))
          {
          throw new Zend_Exception('Unable to find '.$nameComponent);
          }
        if(!isset($this->ModuleComponent))
          {
          $this->ModuleComponent =  new stdClass();
          }
        $this->ModuleComponent->$component = new $nameComponent();
        }
      }

    if(isset($this->_moduleForms))
      {
      foreach($this->_moduleForms as $forms)
        {
        $nameForm = ucfirst($this->moduleName).'_'.$forms . "Form";
        include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php");
        if(file_exists(BASE_PATH . "/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php"))
          {
          include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php");
          }
        elseif(file_exists(BASE_PATH . "/privateModules/".$this->moduleName."/controllers/forms/".$forms."Form.php"))
          {
          include_once (BASE_PATH . "/privateModules/".$this->moduleName."/controllers/forms/".$forms."Form.php");
          }
        else
          {
          throw new Zend_Exception('Unable to find form  '.$forms);
          }
        if(!class_exists($nameForm))
          {
          throw new Zend_Exception('Unable to find '.$nameForm);
          }
        if(!isset($this->ModuleForm))
          {
          $this->ModuleForm =  new stdClass();
          }
        $this->ModuleForm->$forms = new $nameForm();
        }
      }
    }
} // end class