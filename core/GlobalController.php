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
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/**
 *  GlobalAction
 *  Provides global function to the controllers
 */
class MIDAS_GlobalController extends Zend_Controller_Action
  {

  protected $Models = array();
  protected $ModelLoader = null;

  /** contructor*/
  public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {

    if($this->isDebug())
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
    // Init the translater

    $translate = new Zend_Translate('csv', BASE_PATH.'/core/translation/fr-main.csv', 'en');
    Zend_Registry::set('translater', $translate);

    $translaters = array();
    $configs = array();
    $modulesEnable =  Zend_Registry::get('modulesEnable');
    foreach($modulesEnable as $module)
      {
      if(file_exists(BASE_PATH."/modules/".$module."/translation/fr-main.csv"))
        {
        $translationFile = BASE_PATH."/modules/".$module."/translation/fr-main.csv";
        }
      elseif(file_exists(BASE_PATH."/privateModules/".$module."/translation/fr-main.csv"))
        {
        $translationFile = BASE_PATH."/privateModules/".$module."/translation/fr-main.csv";
        }
      else
        {
        throw new Zend_Exception('No translation file found in module '.$module);
        }

      $translaters[$module] = new Zend_Translate('csv', $translationFile, "en");
      if(file_exists(BASE_PATH."/core/configs/".$module.".local.ini"))
        {
        $configs[$module] = new Zend_Config_Ini(BASE_PATH."/core/configs/".$module.".local.ini", 'global');
        }
      elseif(file_exists(BASE_PATH."/privateModules/".$module."/configs/module.ini"))
        {
        $configs[$module] = new Zend_Config_Ini(BASE_PATH."/privateModules/".$module."/configs/module.ini", 'global');
        }
      else
        {
        $configs[$module] = new Zend_Config_Ini(BASE_PATH."/modules/".$module."/configs/module.ini", 'global');
        }
      }
    Zend_Registry::set('translatersModules', $translaters);
    Zend_Registry::set('configsModules', $configs);

    $forward = $this->_getParam("forwardModule");
    $request = $this->getRequest();
    $response = $this->getResponse();
    if(!isset($forward))
      {
      foreach($configs as $key => $config)
        {
        if(file_exists(BASE_PATH.'/modules/'.$key.'/controllers/'.  ucfirst($request->getControllerName()).'CoreController.php'))
          {
          include_once BASE_PATH.'/modules/'.$key.'/controllers/'.  ucfirst($request->getControllerName()).'CoreController.php';
          $name = ucfirst($key).'_'.ucfirst($request->getControllerName()).'CoreController';
          $controller = new $name($request, $response);
          if(method_exists($controller, $request->getActionName().'Action'))
            {
            $this->_forward($request->getActionName(), $request->getControllerName().'Core', $key, array('forwardModule' => true));
            }
          }
        elseif(file_exists(BASE_PATH.'/privateModules/'.$key.'/controllers/'.  ucfirst($request->getControllerName()).'CoreController.php'))
          {
          include_once BASE_PATH.'/privateModules/'.$key.'/controllers/'.  ucfirst($request->getControllerName()).'CoreController.php';
          $name = ucfirst($key).'_'.ucfirst($request->getControllerName()).'CoreController';
          $controller = new $name($request, $response);
          if(method_exists($controller, $request->getActionName().'Action'))
            {
            $this->_forward($request->getActionName(), $request->getControllerName().'Core', $key, array('forwardModule' => true));
            }
          }
        }
      }
    parent::preDispatch();
    if(!$this->isDebug())
      {
      $frontendOptions = array(
        'lifetime' => 86400,
        'automatic_serialization' => true
        );

      $backendOptions = array(
        'cache_dir' => UtilityComponent::getCacheDirectory().'/db'
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
    if($this->isDebug() && $this->getEnvironment() != 'testing')
      {
      $timeEnd = microtime(true);
      $writer = new Zend_Log_Writer_Firebug();
      $logger = new Zend_Log($writer);
      $logger->info("---Timers--- Controller timer:" . round(1000 * ($timeEnd - $this->_controllerTimer), 3)." ms - Global timer:" . round(1000 * ($timeEnd - START_TIME), 3)." ms");

      $logger->info("---Memory Usage---".round((memory_get_usage() / (1024 * 1024)), 3) . " MB");
      }

    if(Zend_Registry::get("configDatabase")->database->profiler == 1)
      {
      $this->showProfiler();
      }
    $this->view->addHelperPath(BASE_PATH."/core/views/helpers", "Zend_View_Helper_");
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
        if(!class_exists($nameComponent))
          {
          throw new Zend_Exception('Unable to find '.$nameComponent);
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
        if(!class_exists($nameForm))
          {
          throw new Zend_Exception('Unable to find '.$nameForm);
          }
        $this->Form->$forms = new $nameForm();
        }
      }
    }//end loadElements
  /**
   * @method public  showProfiler()
   *  Show profiler in the firebug console
   */
  public function showProfiler()
    {
    $writer = new Zend_Log_Writer_Firebug();
    $logger = new Zend_Log($writer);
    $configDatabase = Zend_Registry::get('configDatabase');
    if($configDatabase->database->profiler != '1')
      {
      return;
      }
    $db = Zend_Registry::get('dbAdapter');

    if(method_exists($db, "getProfiler"))
      {
      $profiler = $db->getProfiler();
      $totalTime = $profiler->getTotalElapsedSecs();
      $queryCount = $profiler->getTotalNumQueries();
      if($queryCount == 0)
        {
        return;
        }
      }

    $longestTime = 0;
    $longestQuery = null;
    if(isset($profiler) && !empty($profiler))
      {
      $querys = $profiler->getQueryProfiles();
      if(!empty($querys))
        {
        foreach($profiler->getQueryProfiles() as $query)
          {
          if($query->getElapsedSecs() > $longestTime)
            {
            $longestTime = $query->getElapsedSecs();
            $longestQuery = $query->getQuery();
            }
          }
        $stats = '--- Profiler --- Executed ' . $queryCount . ' queries in ' . round(1000 * $totalTime, 3) .' ms';
        $stats .= ' Longest query length: ' . round(1000 * $longestTime, 3).' ms : '.$longestQuery;
        $logger->log(str_replace("'", "`", $stats), Zend_Log::INFO);

        foreach($profiler->getQueryProfiles() as $query)
          {
          $logger->log(str_replace("'", "`", round(1000 * ($query->getElapsedSecs()), 3). " ms | " . $query->getQuery()), Zend_Log::INFO);
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
    if($config->environment == 'production')
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
   * @method protected getTempDirectory()
   * get the midas temporary directory
   * @return string
   */
  protected function getTempDirectory()
    {
    return UtilityComponent::getTempDirectory();
    }

  /** return an array of form element     */
  public function getFormAsArray(Zend_Form $form)
    {
    $array = array();
    $array['action'] = $form->getAction();
    $array['method'] = $form->getMethod();
    foreach($form->getElements() as $element )
      {
      $element->removeDecorator('HtmlTag');
      $element->removeDecorator('Label');
      $element->removeDecorator('DtDdWrapper');
      $array[$element->getName()] = $element;
      }
    return $array;
    }
} // end class
