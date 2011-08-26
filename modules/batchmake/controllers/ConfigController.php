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
 *  Batchmake_ConfigController
 *  @todo TODO list for ConfigController and batchmake module
 *     - add phpdocumenter style comments
 *     - rework Notification
 *     - add tests
 *     - clean UI layout
 *     - repititions/redundancy in controller
 *     - repititions/redundancy in index.pthml
 *     - translations/centralized string resources
 *     - centralize constants
 *     - ? how to sync PHP, javascript, and css constants?  info, error, warning among them
 *     - ? how to internationalize javascript strings?
 *     - better way of loading internationalization component in component and form
 *     - For now, have a KWBatchmakeComponent, which includes kwutils stuff and kwbatchmake
 *     - separate kw utils, where does this go?
 *     - separate kwbatchmake, where does this go?
 *     - clean component, internationalize strings
 *     - look into zend internationalization/translate
 *     - look into cmake infinite loop
 *     - for web api, there is a json wrapper , ajax_web_api.js in midas2
 *     - an element: ajax_web_api.thtml look in assetstore for usage
 *     - should need to put the "writing" of the auth token info in some common controller code
 *     - then include the element in any view
 *     - then the json .js wrapper will automaticially negotiate
 *     - ? how to use web api in a module?
 *     - want to namaespace constants MIDAS_BATCHMAKE
 *     - for imports have a static var for one time class loading
 *     - change ajax callst o be through web api
 *     - kwutils, try to use zend framework
 */
class Batchmake_ConfigController extends Batchmake_AppController
{




  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Internationalization');
  public $_moduleComponents = array('KWBatchmake');


  /**
   * @method archiveOldModuleLocal()
   * will archive the current module.local config file
   * written in the hope of being reusable
   */
  protected function archiveOldModuleLocal()
    {
    if(file_exists(BATCHMAKE_MODULE_LOCAL_OLD_CONFIG))
      {
      unlink(BATCHMAKE_MODULE_LOCAL_OLD_CONFIG);
      }
    if(file_exists(BATCHMAKE_MODULE_LOCAL_CONFIG))
      {
      rename(BATCHMAKE_MODULE_LOCAL_CONFIG, BATCHMAKE_MODULE_LOCAL_OLD_CONFIG);
      }
    }



  /**
   * @method indexAction()
   */
  function indexAction()
    {

    $applicationConfig = $this->ModuleComponent->KWBatchmake->loadApplicationConfig();
    $configPropertiesRequirements = $this->ModuleComponent->KWBatchmake->getConfigPropertiesRequirements();
       
    $configForm = $this->ModuleForm->Config->createConfigForm($configPropertiesRequirements);
    $formArray = $this->getFormAsArray($configForm);    
    foreach($configPropertiesRequirements as $configProperty => $configPropertyRequirement)
      {
      $formArray[$configProperty]->setValue($applicationConfig[GLOBAL_CONFIG_NAME][$this->moduleName.'.'.$configProperty]);
      } 
    $this->view->configForm = $formArray;
    
    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');

      if(isset($submitConfig))
        {
        $this->archiveOldModuleLocal();
        foreach($configPropertiesRequirements as $configProperty => $configPropertyRequirement)
          {
          $applicationConfig[GLOBAL_CONFIG_NAME][$this->moduleName.'.'.$configProperty] = $this->_getParam($configProperty);
          }     
        $this->Component->Utility->createInitFile(BATCHMAKE_MODULE_LOCAL_CONFIG, $applicationConfig);
        $msg = $this->Component->Internationalization->translate(CHANGES_SAVED_STRING);
        echo JsonComponent::encode(array(true, $msg));
        }
      }

    } 


  /**
   * @method testconfig() 
   * performs validation on the current configuration set through the UI
   */
  protected function testconfig()
    {

    //default of correct config
    $total_config_correct = 1;
    // get the passed in config values to check
    $configStatus = array();
    

    $configPropertiesRequirements = $this->ModuleComponent->KWBatchmake->GetConfigPropertiesRequirements();
    foreach($configPropertiesRequirements as $configProperty => $configPropertyRequirement)
      {
      $configPropertyVal = $this->_getParam($configProperty);
      if($configPropertyVal)
        {
        // if the property exists, check its configuration      
        list($result, $status) = $this->ModuleComponent->KWBatchmake->CheckFileFlag($configPropertyVal, $configPropertyRequirement);
        $configStatus[] = array(PROPERTY_KEY => $configProperty, STATUS_KEY => $status, TYPE_KEY => $result ? STATUS_TYPE_INFO : STATUS_TYPE_ERROR);
        // the property is in error, therefore so is the global config
        if(!$result)
          {
          $total_config_correct = 0;
          }
        }
      else
        {
        // property doesn't exist, both the property and global config are in error
        $configStatus[] = array(PROPERTY_KEY => $configProperty, STATUS_KEY => CONFIG_VALUE_MISSING, TYPE_KEY => STATUS_TYPE_ERROR);
        $total_config_correct = 0;
        }
      }

    // for now assuming will run via condor, so require all of the condor setup

    $appsPaths = $this->ModuleComponent->KWBatchmake->GetApplicationsPaths();
    foreach($appsPaths as $app => $pathProperty)
      {
      $appPath = $this->_getParam($pathProperty) ."/" . $this->ModuleComponent->KWBatchmake->FormatAppName($app);
      list($result, $status) = $this->ModuleComponent->KWBatchmake->CheckFileFlag($appPath, CHECK_IF_EXECUTABLE);
      $applicationString = $this->Component->Internationalization->translate(APPLICATION_STRING);
      $configStatus[] = array(PROPERTY_KEY => $applicationString . ' ' .$appPath, STATUS_KEY => $status, TYPE_KEY => $result ? STATUS_TYPE_INFO : STATUS_TYPE_ERROR);
      // the property is in error, therefore so is the global config
      if(!$result)
        {
        $total_config_correct = 0;
        }
      }

    // Process web server user information

    // TODO what should be done if there are warnings??
    $processUser  = posix_getpwuid(posix_geteuid());
    $processGroup = posix_getgrgid(posix_geteuid());

    $phpProcessString = $this->Component->Internationalization->translate(PHP_PROCESS_STRING);
    $phpProcessUserString = $phpProcessString . ' ' . $this->Component->Internationalization->translate(PHP_PROCESS_USER_STRING);
    $phpProcessNameString = $this->Component->Internationalization->translate(PHP_PROCESS_NAME_STRING);
    $phpProcessGroupString = $this->Component->Internationalization->translate(PHP_PROCESS_GROUP_STRING);
    $phpProcessHomeString = $this->Component->Internationalization->translate(PHP_PROCESS_HOME_STRING);
    $phpProcessShellString = $this->Component->Internationalization->translate(PHP_PROCESS_SHELL_STRING);
    $unknownString = $this->Component->Internationalization->translate(UNKNOWN_STRING);

    $phpProcessUserNameString = $phpProcessUserString . '[' . $phpProcessNameString . ']';
    $phpProcessUserGroupString = $phpProcessUserString . '[' . $phpProcessGroupString . ']';
    $phpProcessUserHomeString = $phpProcessUserString . '[' . $phpProcessHomeString . ']';
    $phpProcessUserShellString = $phpProcessUserString . '[' . $phpProcessShellString . ']';

    $processProperties = array($phpProcessUserNameString => !empty($processUser[PHP_PROCESS_NAME_STRING]) ? $processUser[PHP_PROCESS_NAME_STRING] : "",
    $phpProcessUserGroupString => !empty($processGroup[PHP_PROCESS_NAME_STRING]) ? $processGroup[PHP_PROCESS_NAME_STRING] : "",
    $phpProcessUserHomeString => !empty($processUser[DIR_KEY]) ? $processUser[DIR_KEY] : "",
    $phpProcessUserShellString => !empty($processUser[PHP_PROCESS_SHELL_STRING]) ? $processUser[PHP_PROCESS_SHELL_STRING] : "");

    foreach($processProperties as $property => $value)
      {
      $status   = !empty($value);
      $configStatus[]   = array(PROPERTY_KEY => $property, 
      STATUS_KEY => $status ? $value : $unknownString, 
      TYPE_KEY => $status ? STATUS_TYPE_INFO : STATUS_TYPE_WARNING);
      }
   

    return array($total_config_correct, $configStatus);

    }


  /**
   * @method testconfigAction() 
   * ajax function which tests config setup, performing 
   * validation on the current configuration set through the UI
   */
  public function testconfigAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      $ajaxDirectLoadErrorString = $this->Component->Internationalization->translate(AJAX_DIRECT_LOAD_ERROR_STRING);
      throw new Zend_Exception($ajaxDirectLoadErrorString);
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $config_status = $this->testconfig();
    echo JsonComponent::encode($config_status);
    }//end testconfigAction
 










}//end class
