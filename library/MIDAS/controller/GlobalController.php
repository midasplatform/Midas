<?php

/**
 *  GlobalAction
 *  Provides global function to the controllers
 */
class MIDAS_GlobalController extends Zend_Controller_Action
  {

  protected $Models = array();
  protected $ModelLoader = null;

  public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
    if ($this->isDebug())
      {
      $this->_controllerTimer = microtime(true);
      }

    $this->loadElements();
    parent::__construct($request, $response, $invokeArgs);
    }
    
  /**
   * Pre-dispatch routines
   *
   * @return void
   */
  public function preDispatch()
    {   
    parent::preDispatch();
    if (!$this->isDebug())
      {
      $frontendOptions = array(
        'lifetime' => 86400,
        'automatic_serialization' => true
        );

      $backendOptions = array(
        'cache_dir' => BASE_PATH.'/tmp/cache/db'
        );

      $cache = Zend_Cache::factory('Core',
                                   'File',
                                   $frontendOptions,
                                   $backendOptions);

      // Passing the object to cache by default
      Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
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
    if ($this->isDebug() && $this->getEnvironment() != 'testing')
      {
      $time_end = microtime(true);
      $writer = new Zend_Log_Writer_Firebug();
      $logger = new Zend_Log($writer);
      $logger->info("---Timers--- Controller timer:" . round(1000*($time_end - $this->_controllerTimer),3)." ms - Global timer:" . round(1000*($time_end - START_TIME),3)." ms");

      $logger->info("---Memory Usage---".round((memory_get_usage() / (1024 * 1024)),3) . " MB");
      }

    if (Zend_Registry::get("configDatabase")->database->profiler == 1)
      {
      $this->showProfiler();
      }
      $this->view->addHelperPath(BASE_PATH."/application/views/helpers", "Zend_View_Helper_");
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
    $this->ModelLoader->loadModels($this->_models);
    $modelsArray = Zend_Registry::get('models');
    foreach ($modelsArray as $key => $tmp)
      {
      $this->$key = $tmp;
      }
    foreach ($this->_daos as $dao)
      {
      Zend_Loader::loadClass($dao . "Dao", BASE_PATH . '/application/models/dao');
      }

    Zend_Registry::set('components', array());

    foreach ($this->_components as $component)
      {
      $nameComponent = $component . "Component";
      Zend_Loader::loadClass($nameComponent, BASE_PATH . '/application/controllers/components');
      @$this->Component->$component = new $nameComponent();
      }

    Zend_Registry::set('forms', array());
    if(isset($this->_forms))
      {
      foreach ($this->_forms as $forms)
        {
        $nameForm = $forms . "Form";

        Zend_Loader::loadClass($nameForm, BASE_PATH . '/application/controllers/forms');
        @$this->Form->$forms = new $nameForm();
        }
      }
    }

  /**
   * @method public  showProfiler()
   *  Show profiler in the firebug console
   */
  public function showProfiler()
    {
    $writer = new Zend_Log_Writer_Firebug();
    $logger = new Zend_Log($writer);
    $configDatabase = Zend_Registry::get('configDatabase');
    if ($configDatabase->database->profiler != '1')
      {
      return;
      }
    $db = Zend_Registry::get('dbAdapter');
    $profiler = $db->getProfiler();
    $totalTime = $profiler->getTotalElapsedSecs();
    $queryCount = $profiler->getTotalNumQueries();
    if ($queryCount == 0)
      {
      return;
      }
    $longestTime = 0;
    $longestQuery = null;
    if(isset($profiler)&&!empty($profiler))
      {
      $querys=$profiler->getQueryProfiles();
      if(!empty($querys))
        {
        foreach ($profiler->getQueryProfiles() as $query)
          {
          if ($query->getElapsedSecs() > $longestTime)
            {
            $longestTime = $query->getElapsedSecs();
            $longestQuery = $query->getQuery();
            }
          }
        $stats='--- Profiler --- Executed ' . $queryCount . ' queries in ' . round(1000*$totalTime,3) .' ms';
        $stats.= ' Longest query length: ' . round(1000*$longestTime,3).' ms : '.$longestQuery;
        $logger->log(str_replace("'","`",$stats), Zend_Log::INFO);

        foreach ($profiler->getQueryProfiles() as $query)
          {
          $logger->log(str_replace("'","`",round(1000*($query->getElapsedSecs()),3). " ms | " . $query->getQuery()), Zend_Log::INFO);
          }
        }
      }
    }

  /**
   * @method public  isDebug()
   * Is Debug mode ON
   * @return boolean
   */
  public function isDebug()
    {
    $config = Zend_Registry::get('config');
    if ($config->mode->debug == 1)
      {
      return true;
      }
    else
      {
      return false;
      }
    }

  /**
   * @method public  getEnvironment()
   * get environnement set in the config
   * @return string
   */
  public function getEnvironment()
    {
    $config = Zend_Registry::get('configGlobal');
    return $config->environment;
    }
    
  /**
   * @method public getTempDirectory()
   * get the midas temporary directory
   * @return string
   */
  public function getTempDirectory()
    {
    return BASE_PATH.'/tmp/misc';
    }
  
    /** return an array of form element     */
    public function getFormAsArray(Zend_Form $form) 
    {
      $array = array();
      $array['action'] = $form->getAction();
      $array['method'] = $form->getMethod();
      foreach ( $form->getElements() as $element ) {
          $element->removeDecorator('HtmlTag');
          $element->removeDecorator('Label');
          $element->removeDecorator('DtDdWrapper');
          $array[$element->getName()] = $element;
      }
      return $array;
    }    
    
} // end class
?>