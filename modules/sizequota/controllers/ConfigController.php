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

/** sizequota main config controller */
class Sizequota_ConfigController extends Sizequota_AppController
{
  public $_moduleForms = array('Config');

  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $modelLoader = new MIDAS_ModelLoader();
    $settingModel = $modelLoader->loadModel('Setting');
    $defaultUserQuota = $settingModel->getValueByName('defaultuserquota', $this->moduleName);
    $defaultCommunityQuota = $settingModel->getValueByName('defaultcommunityquota', $this->moduleName);

    $configForm = $this->ModuleForm->Config->createConfigForm();
    $formArray = $this->getFormAsArray($configForm);
    if($defaultUserQuota !== null)
      {
      $formArray['defaultuserquota']->setValue($defaultUserQuota);
      }
    if($defaultCommunityQuota !== null)
      {
      $formArray['defaultcommunityquota']->setValue($defaultCommunityQuota);
      }
    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        $defaultUserQuota = $this->_getParam('defaultuserquota');
        $defaultCommunityQuota = $this->_getParam('defaultcommunityquota');
        if(!$this->_isValidQuota(array($defaultUserQuota, $defaultCommunityQuota)))
          {
          echo JsonComponent::encode(array(false, 'Invalid quota value. Please enter a positive integer.'));
          return;
          }
        $settingModel->setConfig('defaultuserquota', $defaultUserQuota, $this->moduleName);
        $settingModel->setConfig('defaultcommunityquota', $defaultCommunityQuota, $this->moduleName);
        echo JsonComponent::encode(array(true, 'Changes saved'));
        }
      }
    }

  /** Test whether the provided quota value is legal */
  private function _isValidQuota($quotas)
    {
    foreach($quotas as $quota)
      {
      if(!preg_match('/^[0-9]*$/', $quota))
        {
        return false;
        }
      }
    return true;
    }

}//end class