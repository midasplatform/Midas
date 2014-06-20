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

    if(file_exists(LOCAL_CONFIGS_PATH."/api.local.ini"))
      {
      $applicationConfig = parse_ini_file(LOCAL_CONFIGS_PATH."/api.local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/api/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();

    $formArray = $this->getFormAsArray($configForm);
    $formArray['methodprefix']->setValue($applicationConfig['global']['methodprefix']);

    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(LOCAL_CONFIGS_PATH."/api.local.ini.old"))
          {
          unlink(LOCAL_CONFIGS_PATH."/api.local.ini.old");
          }
        if(file_exists(LOCAL_CONFIGS_PATH."/api.local.ini"))
          {
          rename(LOCAL_CONFIGS_PATH."/api.local.ini", LOCAL_CONFIGS_PATH."/api.local.ini.old");
          }
        $applicationConfig['global']['methodprefix'] = $this->_getParam('methodprefix');
        $this->Component->Utility->createInitFile(LOCAL_CONFIGS_PATH."/api.local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }
  } // end class
