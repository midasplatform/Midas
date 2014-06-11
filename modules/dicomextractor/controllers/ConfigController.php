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

class Dicomextractor_ConfigController extends Dicomextractor_AppController
  {
   public $_moduleForms = array('Config');
   public $_components = array('Utility', 'Date');

   /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();

    $formArray = $this->getFormAsArray($configForm);
    $formArray['dcm2xml']->setValue($applicationConfig['global']['dcm2xml']);
    $formArray['dcmj2pnm']->setValue($applicationConfig['global']['dcmj2pnm']);
    $formArray['dcmftest']->setValue($applicationConfig['global']['dcmftest']);
    if(isset($applicationConfig['global']['dcmdictpath']))
      {
      $formArray['dcmdictpath']->setValue(
        $applicationConfig['global']['dcmdictpath']);
      }
    else
      {
      $formArray['dcmdictpath']->setValue("");
      }

    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini"))
          {
          rename(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old");
          }
        $applicationConfig['global']['dcm2xml'] = $this->_getParam('dcm2xml');
        $applicationConfig['global']['dcmj2pnm'] = $this->_getParam('dcmj2pnm');
        $applicationConfig['global']['dcmftest'] = $this->_getParam('dcmftest');
        $applicationConfig['global']['dcmdictpath'] =
          $this->_getParam('dcmdictpath');
        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changes saved'));
        }
      }
    }
  } // end class
