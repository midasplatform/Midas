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

require_once BASE_PATH.'/core/AppController.php';
/**
 *  GlobalAction
 *  Provides global function to the controllers
 */
class MIDAS_GlobalModule extends AppController
  {
  /** contructor*/
  public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
    parent::__construct($request, $response, $invokeArgs);
    $this->loadModuleElements();
    if(!isset($this->moduleName))
      {
      throw new Zend_Exception("Please set the module name in AppController");
      }
    $fc = Zend_Controller_Front::getInstance();
    $this->view->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    $this->view->moduleName = $this->moduleName;

    if(file_exists(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini'))
      {
      $config = new Zend_Config_Ini(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini', 'global', true);
      }
    elseif(file_exists(BASE_PATH.'/privateModules/'.$this->moduleName.'/configs/module.ini'))
      {
      $config = new Zend_Config_Ini(BASE_PATH.'/privateModules/'.$this->moduleName.'/configs/module.ini', 'global', true);
      }
    else
      {
      throw new Zend_Exception('Unable to find configuration file');
      }
    
    $this->view->moduleFullName = $config->fullname;
    $this->view->moduleDescription = $config->description;

    $stack = debug_backtrace();
    $forward = $this->_getParam('forwardModule');

    // Add variables to the view that allow the retrieval of any enabled module
    // webroots
    $allModules = Zend_Registry::get('modulesEnable');
    foreach($allModules as &$mod)
      {
      $modWebroot = $mod.'Webroot';
      $this->view->$modWebroot = $fc->getBaseUrl().'/modules/'.$mod;
      }
    }

  /** pre dispatch (zend)*/
  public function preDispatch()
    {
    parent::preDispatch();
    if(file_exists(BASE_PATH."/modules/".$this->moduleName."/views"))
      {
      $this->view->setScriptPath(BASE_PATH."/modules/".$this->moduleName."/views");
      }
    elseif(file_exists(BASE_PATH."/privateModules/".$this->moduleName."/views"))
      {
      $this->view->setScriptPath(BASE_PATH."/privateModules/".$this->moduleName."/views");
      }
    else
      {
      throw new Zend_Exception('Unable to find module '.$this->moduleName.' view directory');
      }
    if($this->isTestingEnv())
      {
      $this->disableLayout();
      }
    else
      {

      if(file_exists(BASE_PATH."/modules/".$this->moduleName."/layouts/layout.phtml"))
        {
        $this->_helper->layout->setLayoutPath(BASE_PATH."/modules/".$this->moduleName."/layouts");
        }
      }
    }

  /**
   * Post-dispatch routines
   *
   * Common usages for postDispatch() include rendering content in a sitewide
   * template, link url correction, setting headers, etc.
   *
   * @return void
   */
  public function postDispatch()
    {

    parent::postDispatch();


    $this->view->addHelperPath(BASE_PATH."/".$this->moduleName."/views/helpers", "Zend_View_Helper_");
    }

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

  /** call controller core method */
  public function callCoreAction()
    {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $controllerName = ucfirst(str_replace('Core', '', $request->getControllerName()));
    if(file_exists(BASE_PATH.'/core/controllers/'. $controllerName.'Controller.php'))
      {
      include_once BASE_PATH.'/core/controllers/'. $controllerName.'Controller.php';
      $name = $controllerName.'Controller';
      $controller = new $name($request, $response);
      if(method_exists($controller, $request->getActionName().'Action'))
        {
        $controller->userSession = $this->userSession;
        $controller->logged = $this->logged;
        $actionName = $request->getActionName().'Action';
        $controller->$actionName();
        return true;
        }
      else
        {
        return false;
        }
      }
    else
      {
      return false;
      }
    }
} // end class