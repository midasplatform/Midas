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

/** api config controller */
class Api_ConfigController extends Api_AppController
  {
  public $_moduleForms = array('Config');
  public $_components = array('Utility');

  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $options = array('allowModifications' => true);
    if(file_exists(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini'))
      {
      $config = new Zend_Config_Ini(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini', 'global', $options);
      }
    else
      {
      $config = new Zend_Config_Ini(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini', 'global', $options);
      }

    $configForm = $this->ModuleForm->Config->createConfigForm();
    $formArray = $this->getFormAsArray($configForm);
    $formArray['methodprefix']->setValue($config->methodprefix);
    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->getParam('submitConfig');
      if(isset($submitConfig))
        {
        $config->methodprefix = $this->getParam('methodprefix');

        $writer = new Zend_Config_Writer_Ini();
        $writer->setConfig($config);
        $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
        $writer->write();
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }
  } // end class
