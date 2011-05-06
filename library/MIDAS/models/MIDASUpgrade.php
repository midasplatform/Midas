<?php

/**
 *  MIDASUpgrade
 */
class MIDASUpgrade
{
  
  protected $db;
  
  /**
   * @method public  __construct()
   *  Construct model
   */
  public function __construct($db, $module)
    {
    $this->db = $db;
    $this->moduleName = $module;
    $this->loadElements();
    if($module != 'core')
      {
      $this->loadModuleElements();
      }
    } // end __construct()
    
  /** preUpgrade called before the upgrade*/
  public function preUpgrade()
    {
    
    }
    
  /** calls if mysql enable*/
  public function mysql()
    {
    
    }
    
  /** called is pgsql enabled*/
  public function pgsql()
    {
    
    }
    
  /** called after the upgrade*/
  public function postUpgrade()
    {
    
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
        include_once (BASE_PATH . "/modules/".$this->moduleName."/models/dao/".$dao."Dao.php");
        }
      }

    if(isset($this->_moduleComponents))
      {
      foreach($this->_moduleComponents as $component)
        {
        $nameComponent = ucfirst($this->moduleName).'_'.$component . "Component";
        include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/components/".$component."Component.php");
        @$this->ModuleComponent->$component = new $nameComponent();
        }
      }
      
    if(isset($this->_moduleForms))
      {
      foreach($this->_moduleForms as $forms)
        {
        $nameForm = ucfirst($this->moduleName).'_'.$forms . "Form";
        include_once (BASE_PATH . "/modules/".$this->moduleName."/controllers/forms/".$forms."Form.php");
        @$this->ModuleForm->$forms = new $nameForm();
        }
      }
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
        @$this->Component->$component = new $nameComponent();
        }
      }

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach($this->_forms as $forms)
        {
        $nameForm = $forms . "Form";

        Zend_Loader::loadClass($nameForm, BASE_PATH . '/core/controllers/forms');
        @$this->Form->$forms = new $nameForm();
        }
      }
    }//end loadElements
} //end class MIDASDatabasePdo
?>
