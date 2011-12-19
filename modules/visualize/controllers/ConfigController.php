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

class Visualize_ConfigController extends Visualize_AppController
{
   public $_moduleForms=array('Config');
   public $_components=array('Utility', 'Date');
   public $_moduleModels=array();

   
   /** index action*/
   function indexAction()
    {
    $this->requireAdminPrivileges();
    
    $module = 'visualize';
    
    if(file_exists(BASE_PATH."/core/configs/".$module.".local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/".$module.".local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/'.$module.'/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();
    
    $formArray = $this->getFormAsArray($configForm);    
    $formArray['useparaview']->setValue($applicationConfig['global']['useparaview']);
    $formArray['userwebgl']->setValue($applicationConfig['global']['userwebgl']);
    $formArray['pwapp']->setValue($applicationConfig['global']['pwapp']);
    $formArray['customtmp']->setValue($applicationConfig['global']['customtmp']);
    $formArray['usesymlinks']->setValue($applicationConfig['global']['usesymlinks']);
    $formArray['pvbatch']->setValue($applicationConfig['global']['pvbatch']);
    $formArray['paraviewworkdir']->setValue($applicationConfig['global']['paraviewworkdir']);
    
    $this->view->configForm = $formArray;
    
    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH."/core/configs/".$module.".local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/".$module.".local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/".$module.".local.ini"))
          {
          rename(BASE_PATH."/core/configs/".$module.".local.ini",BASE_PATH."/core/configs/".$module.".local.ini.old");
          }
        $applicationConfig['global']['useparaview'] = $this->_getParam('useparaview');
        $applicationConfig['global']['customtmp'] = $this->_getParam('customtmp');
        $applicationConfig['global']['userwebgl'] = $this->_getParam('userwebgl');
        $applicationConfig['global']['usesymlinks'] = $this->_getParam('usesymlinks');
        $applicationConfig['global']['pwapp'] = $this->_getParam('pwapp');
        $applicationConfig['global']['pvbatch'] = $this->_getParam('pvbatch');
        $applicationConfig['global']['paraviewworkdir'] = $this->_getParam('paraviewworkdir');
        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/".$module.".local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    } 
    
}//end class