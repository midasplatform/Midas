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
    $this->loadModuleElements();
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
    $this->ModelLoader->loadModels($this->_moduleModels,$this->moduleName);
    $modelsArray = Zend_Registry::get('models');
    foreach ($modelsArray as $key => $tmp)
      {
      $this->$key = $tmp;
      }
    foreach ($this->_moduleDaos as $dao)
      {
      include_once ( BASE_PATH . "/modules/{$this->moduleName}/models/dao/{$dao}Dao.php");
      }

    foreach ($this->_moduleComponents as $component)
      {
      $nameComponent = ucfirst($this->moduleName).'_'.$component . "Component";
      include_once ( BASE_PATH . "/modules/{$this->moduleName}/controllers/components/{$component}Component.php");
      @$this->ModuleComponent->$component = new $nameComponent();
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

} // end class
?>