<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
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
 *  Batchmake_ConfigController
 *  @todo TODO list for ConfigController and batchmake module
 *     - better way of loading internationalization component in component and form
 *     - look into zend internationalization/translate
 *     - for web api, there is a json wrapper , ajax_web_api.js in midas2
 *     - an element: ajax_web_api.thtml look in assetstore for usage
 *     - should need to put the "writing" of the auth token info in some common controller code
 *     - then include the element in any view
 *     - then the json .js wrapper will automaticially negotiate
 *     - change ajax callst o be through web api, and standard ajax
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
    if(file_exists(MIDAS_BATCHMAKE_MODULE_LOCAL_OLD_CONFIG))
      {
      unlink(MIDAS_BATCHMAKE_MODULE_LOCAL_OLD_CONFIG);
      }
    if(file_exists(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG))
      {
      rename(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG, MIDAS_BATCHMAKE_MODULE_LOCAL_OLD_CONFIG);
      }
    }



  /**
   * will create default paths in the midas temp directory
   * for any properties not already set, except for the
   * condor bin dir; imposing a firmer hand on the user
   * @param type $currentConfig
   */
  protected function createDefaultConfig($currentConfig)
    {
    $tmpDir = $this->getTempDirectory();
    $defaultConfigDirs = array(MIDAS_BATCHMAKE_TMP_DIR_PROPERTY => $tmpDir.'/batchmake/tmp',
    MIDAS_BATCHMAKE_BIN_DIR_PROPERTY => $tmpDir.'/batchmake/bin',
    MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY => $tmpDir.'/batchmake/script',
    MIDAS_BATCHMAKE_APP_DIR_PROPERTY => $tmpDir.'/batchmake/bin',
    MIDAS_BATCHMAKE_DATA_DIR_PROPERTY => $tmpDir.'/batchmake/data');
    $returnedConfig = array();
    foreach($currentConfig as $configProp => $configDir)
      {
      if((!isset($configProp) || !isset($configDir) || $configDir == "") && array_key_exists($configProp, $defaultConfigDirs))
        {
        $configDir = $defaultConfigDirs[$configProp];
        $returnedConfig[$configProp] = $configDir;
        // also create this directory to be sure it exists
        if(!KWUtils::mkDir($configDir))
          {
          throw new Zend_Exception("Cannot create directory ".$configDir);
          }
        }
      else
        {
        $returnedConfig[$configProp] = $configDir;
        }
      }
    return $returnedConfig;
    }





  /**
   * @method indexAction(), will test the configuration that the user has set
   * and return validation info for the passed in properties.
   */
  public function indexAction()
    {

    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }

    // get all the properties, not just the batchmake config
    $fullConfig = $this->ModuleComponent->KWBatchmake->loadConfigProperties(null, false);
    // now get just the batchmake ones
    $batchmakeConfig = $this->ModuleComponent->KWBatchmake->filterBatchmakeConfigProperties($fullConfig);
    // create these properties as default dir locations in tmp
    $batchmakeConfig = $this->createDefaultConfig($batchmakeConfig);
    $configPropertiesRequirements = $this->ModuleComponent->KWBatchmake->getConfigPropertiesRequirements();



    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam(MIDAS_BATCHMAKE_SUBMIT_CONFIG);

      if(isset($submitConfig))
        {
        // user wants to save config
        $this->archiveOldModuleLocal();
        // save only those properties we are interested for local configuration
        foreach($configPropertiesRequirements as $configProperty => $configPropertyRequirement)
          {
          $fullConfig[MIDAS_BATCHMAKE_GLOBAL_CONFIG_NAME][$this->moduleName.'.'.$configProperty] = $this->_getParam($configProperty);
          }
        $this->Component->Utility->createInitFile(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG, $fullConfig);
        $msg = $this->t(MIDAS_BATCHMAKE_CHANGES_SAVED_STRING);
        echo JsonComponent::encode(array(true, $msg));
        }
      }
    else
      {
      // populate config form with values
      $configForm = $this->ModuleForm->Config->createConfigForm($configPropertiesRequirements);
      $formArray = $this->getFormAsArray($configForm);
      foreach($configPropertiesRequirements as $configProperty => $configPropertyRequirement)
        {
        $formArray[$configProperty]->setValue($batchmakeConfig[$configProperty]);
        }
      $this->view->configForm = $formArray;
      }

    }



}//end class
