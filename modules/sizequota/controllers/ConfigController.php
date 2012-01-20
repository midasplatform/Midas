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

/**
 * This controller is used to manage size quotas on folders.
 * It contains the actions for the main module configuration page as well as for
 * maintaining folder-specific quotas.
 */
class Sizequota_ConfigController extends Sizequota_AppController
{
  public $_models = array('Folder', 'Setting');
  public $_moduleModels = array('FolderQuota');
  public $_moduleForms = array('Config');

  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $modelLoader = new MIDAS_ModelLoader();
    $defaultUserQuota = $this->Setting->getValueByName('defaultuserquota', $this->moduleName);
    $defaultCommunityQuota = $this->Setting->getValueByName('defaultcommunityquota', $this->moduleName);

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
        $this->Setting->setConfig('defaultuserquota', $defaultUserQuota, $this->moduleName);
        $this->Setting->setConfig('defaultcommunityquota', $defaultCommunityQuota, $this->moduleName);
        echo JsonComponent::encode(array(true, 'Changes saved'));
        }
      }
    }

  /** Renders the view for folder-specific quotas */
  public function folderAction()
    {
    $this->disableLayout();
    if(!$this->_getParam('folderId'))
      {
      throw new Zend_Exception('Invalid parameters');
      }
    $folder = $this->Folder->load($this->_getParam('folderId'));

    if(!$folder)
      {
      throw new Zend_Exception('Invalid folderId parameter');
      }
    if(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('Invalid policy');
      }

    if($folder->getParentId() == -1) //user folder
      {
      $defaultQuota = $this->Setting->getValueByName('defaultuserquota', $this->moduleName);
      }
    else if($folder->getParentId() == -2) //community folder
      {
      $defaultQuota = $this->Setting->getValueByName('defaultcommunityquota', $this->moduleName);
      }
    else
      {
      throw new Zend_Exception('Must be a community or user root folder');
      }

    $currentQuota = $this->Sizequota_FolderQuota->getQuota($folder);
    $configForm = $this->ModuleForm->Config->createFolderForm($defaultQuota);
    $formArray = $this->getFormAsArray($configForm);
    if($currentQuota === false)
      {
      $formArray['usedefault']->setValue(MIDAS_USE_DEFAULT_QUOTA);
      $this->view->quota = $defaultQuota;
      }
    else
      {
      $formArray['usedefault']->setValue(MIDAS_USE_SPECIFIC_QUOTA);
      $formArray['quota']->setValue($currentQuota->getQuota());
      $this->view->quota = $currentQuota->getQuota();
      }
    $this->view->usedSpace = $this->Folder->getSize($folder);
    $this->view->hUsedSpace = UtilityComponent::formatSize($this->view->usedSpace);
    if($this->view->quota == '')
      {
      $this->view->hQuota = $this->t('Unlimited');
      $this->view->hFreeSpace = '';
      }
    else
      {
      $this->view->hQuota = UtilityComponent::formatSize($this->view->quota);
      $this->view->hFreeSpace = UtilityComponent::formatSize($this->view->quota - $this->view->usedSpace);
      }
    $this->view->configForm = $formArray;
    $this->view->folder = $folder;
    $this->view->isAdmin = $this->userSession->Dao->isAdmin();
    }

  /** Used to manage folder-specific quotas (form handler) */
  public function foldersubmitAction()
    {
    $this->requireAdminPrivileges();

    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $quota = $this->_getParam('quota');
    $useDefault = $this->_getParam('usedefault');
    if($useDefault == MIDAS_USE_DEFAULT_QUOTA)
      {
      $quota = null;
      }
    $folder = $this->Folder->load($this->_getParam('folderId'));
    if(!$folder)
      {
      echo JsonComponent::encode(array(false, 'Invalid folderId parameter'));
      return;
      }
    if($quota !== null && !$this->_isValidQuota(array($quota)))
      {
      echo JsonComponent::encode(array(false, 'Invalid quota value. Please enter a positive integer.'));
      return;
      }

    $this->Sizequota_FolderQuota->setQuota($folder, $quota);
    echo JsonComponent::encode(array(true, 'Changes saved'));
    }

  /** Get the amount of free space available for a folder */
  public function getfreespaceAction()
    {
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $folderId = $this->_getParam('folderId');
    if(!isset($folderId))
      {
      echo JsonComponent::encode(array('status' => false, 'message' => 'Missing folderId parameter'));
      return;
      }

    $folder = $this->Folder->load($folderId);
    if(!$folder)
      {
      echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid folder'));
      return;
      }
    if(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid policy'));
      return;
      }

    if($folder->getParentId() < 0)
      {
      $rootFolder = $folder;
      }
    else
      {
      $rootFolder = $this->Folder->getRoot($folder);
      }
    $quota = $this->Sizequota_FolderQuota->getFolderQuota($rootFolder);
    if($quota == '')
      {
      $freeSpace = '';
      $hFreeSpace = $this->t('Unlimited');
      }
    else
      {
      $used = $this->Folder->getSize($rootFolder);
      $freeSpace = number_format($quota - $used, 0, '.', '');
      $hFreeSpace = UtilityComponent::formatSize($quota - $used);
      }
    echo JsonComponent::encode(array('status' => true,
                                     'freeSpace' => $freeSpace,
                                     'hFreeSpace' => $hFreeSpace));
    }

  /** Test whether the provided quota values are legal */
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