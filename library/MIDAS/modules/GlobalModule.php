<?php

/**
 *  GlobalAction
 *  Provides global function to the controllers
 */
require_once BASE_PATH.'/core/AppController.php';
class MIDAS_GlobalModule extends AppController
  {


  public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {    

    parent::__construct($request, $response, $invokeArgs);
    if(!isset($this->moduleName))
      {
      throw new Zend_Exception("Please set the module name in AppController");
      }
    $this->loadModuleElements();
    $fc=Zend_Controller_Front::getInstance();
    $this->view->moduleWebroot=$fc->getBaseUrl().'/modules/'.$this->moduleName;
    
    $stack=debug_backtrace();
    $forward=$this->_getParam('forwardModule');
    }
    

  public function preDispatch()
    {   
    parent::preDispatch();   
    $this->view->setScriptPath(BASE_PATH."/modules/{$this->moduleName}/views");
    if(file_exists(BASE_PATH."/modules/{$this->moduleName}/layouts/layout.phtml"))
      {
      $this->_helper->layout->setLayoutPath(BASE_PATH."/modules/{$this->moduleName}/layouts");  
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


    $this->view->addHelperPath(BASE_PATH."/{$this->moduleName}/views/helpers", "Zend_View_Helper_");
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
      $this->ModelLoader->loadModels($this->_moduleModels,$this->moduleName);
      $modelsArray = Zend_Registry::get('models');
      foreach ($this->_moduleModels as  $value)
        {
        if(isset($modelsArray[$this->moduleName.$value]))
          {
          $tmp=ucfirst($this->moduleName).'_'.$value;
          $this->{$tmp} = $modelsArray[$this->moduleName.$value];
          }
        }
      }
      
    if(isset($this->_moduleDaos))
      {
      foreach ($this->_moduleDaos as $dao)
        {
        include_once ( BASE_PATH . "/modules/{$this->moduleName}/models/dao/{$dao}Dao.php");
        }
      }

    if(isset($this->_moduleComponents))
      {
      foreach ($this->_moduleComponents as $component)
        {
        $nameComponent = ucfirst($this->moduleName).'_'.$component . "Component";
        include_once ( BASE_PATH . "/modules/{$this->moduleName}/controllers/components/{$component}Component.php");
        @$this->ModuleComponent->$component = new $nameComponent();
        }
      }
      
    if(isset($this->_moduleForms))
      {
      foreach ($this->_moduleForms as $forms)
        {
        $nameForm = ucfirst($this->moduleName).'_'.$forms . "Form";
        include_once ( BASE_PATH . "/modules/{$this->moduleName}/controllers/forms/{$forms}Form.php");
        @$this->ModuleForm->$forms = new $nameForm();
        }
      }
    }

  public function callCoreAction()
    {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $controllerName=ucfirst(str_replace('Core', '', $request->getControllerName()));
    if(file_exists(BASE_PATH.'/core/controllers/'. $controllerName.'Controller.php'))
      {
      include_once BASE_PATH.'/core/controllers/'. $controllerName.'Controller.php';
      $name=$controllerName.'Controller';
      $controller=new $name($request,$response);
      if(method_exists($controller, $request->getActionName().'Action'))
        {
        $controller->userSession=$this->userSession;
        $controller->{$request->getActionName().'Action'}();
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
?>