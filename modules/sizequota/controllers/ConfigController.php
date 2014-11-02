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
    public $_models = array('Assetstore', 'Folder', 'Setting');
    public $_moduleModels = array('FolderQuota');
    public $_moduleForms = array('Config');

    /** index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $defaultUserQuota = $this->Setting->getValueByName('defaultuserquota', $this->moduleName);
        $defaultCommunityQuota = $this->Setting->getValueByName('defaultcommunityquota', $this->moduleName);

        $configForm = $this->ModuleForm->Config->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);
        if ($defaultUserQuota !== null) {
            if ($defaultUserQuota !== '') {
                list($userQuotaValue, $this->view->unitValueUser) = explode(
                    ' ',
                    UtilityComponent::formatSize($defaultUserQuota, '')
                );
                $formArray['defaultuserquota']->setValue($userQuotaValue);
            }
        }
        if ($defaultCommunityQuota !== null) {
            if ($defaultCommunityQuota !== '') {
                list($communityQuotaValue, $this->view->unitValueCommunity) = explode(
                    ' ',
                    UtilityComponent::formatSize($defaultCommunityQuota, '')
                );
                $formArray['defaultcommunityquota']->setValue($communityQuotaValue);
            }
        }

        $this->view->configForm = $formArray;

        if ($this->_request->isPost()) {
            $this->disableLayout();
            $this->disableView();
            $submitConfig = $this->getParam('submitConfig');
            if (isset($submitConfig)) {
                $communityQuotaUnit = $this->getParam('communityQuotaUnit');
                $userQuotaUnit = $this->getParam('userQuotaUnit');
                $defaultUserQuota = $this->getParam('defaultuserquota');
                $defaultCommunityQuota = $this->getParam('defaultcommunityquota');
                if (!$this->_isValidQuota(array($defaultUserQuota, $defaultCommunityQuota))
                ) {
                    echo JsonComponent::encode(array(false, 'Invalid quota value. Please enter a positive integer.'));

                    return;
                }
                if ($defaultUserQuota !== '') {
                    $defaultUserQuota *= (float) $userQuotaUnit;
                }
                if ($defaultCommunityQuota !== '') {
                    $defaultCommunityQuota *= (float) $communityQuotaUnit;
                }

                $this->Setting->setConfig('defaultuserquota', (string) $defaultUserQuota, $this->moduleName);
                $this->Setting->setConfig('defaultcommunityquota', (string) $defaultCommunityQuota, $this->moduleName);
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
        }
    }

    /** Renders the view for folder-specific quotas */
    public function folderAction()
    {
        $this->disableLayout();
        if (!$this->getParam('folderId')) {
            throw new Zend_Exception('Invalid parameters');
        }
        $folder = $this->Folder->load($this->getParam('folderId'));

        if (!$folder) {
            throw new Zend_Exception('Invalid folderId parameter');
        }
        if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('Invalid policy');
        }

        if ($folder->getParentId() == -1) { // user folder
            $defaultQuota = $this->Setting->getValueByName('defaultuserquota', $this->moduleName);
        } elseif ($folder->getParentId() == -2) { // community folder
            $defaultQuota = $this->Setting->getValueByName('defaultcommunityquota', $this->moduleName);
        } else {
            throw new Zend_Exception('Must be a community or user root folder');
        }

        $currentQuota = $this->Sizequota_FolderQuota->getQuota($folder);
        $configForm = $this->ModuleForm->Config->createFolderForm($defaultQuota);
        $formArray = $this->getFormAsArray($configForm);
        if ($currentQuota === false) {
            $formArray['usedefault']->setValue(MIDAS_USE_DEFAULT_QUOTA);
            $this->view->quota = $defaultQuota;
        } else {
            $formArray['usedefault']->setValue(MIDAS_USE_SPECIFIC_QUOTA);
            $this->view->quota = $currentQuota->getQuota();
            if ($this->view->quota !== '') {
                list($quotaFormValue, $this->view->unitFormValue) = explode(
                    ' ',
                    UtilityComponent::formatSize($this->view->quota, '')
                );
                $formArray['quota']->setValue($quotaFormValue);
            }
        }
        $this->view->usedSpace = $this->Folder->getSize($folder);
        $this->view->hUsedSpace = UtilityComponent::formatSize($this->view->usedSpace);
        if ($this->view->quota == '') {
            $this->view->hQuota = $this->t('Unlimited');
            $this->view->hFreeSpace = '';
        } else {
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
        $quota = $this->getParam('quota');
        $multiplier = $this->getParam('unit');
        $useDefault = $this->getParam('usedefault');
        if ($useDefault == MIDAS_USE_DEFAULT_QUOTA) {
            $quota = null;
        }
        $folder = $this->Folder->load($this->getParam('folderId'));
        if (!$folder) {
            echo JsonComponent::encode(array(false, 'Invalid folderId parameter'));

            return;
        }
        if ($quota !== null && !$this->_isValidQuota(array($quota))) {
            echo JsonComponent::encode(array(false, 'Invalid quota value. Please enter a positive number.'));

            return;
        }
        if ($quota !== null && $quota !== '') {
            $quota *= $multiplier;
        }

        $this->Sizequota_FolderQuota->setQuota($folder, $quota);
        echo JsonComponent::encode(array(true, 'Changes saved'));
    }

    /** Get the amount of free space available for a folder */
    public function getfreespaceAction()
    {
        $this->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $folderId = $this->getParam('folderId');
        if (!isset($folderId)) {
            echo JsonComponent::encode(array('status' => false, 'message' => 'Missing folderId parameter'));

            return;
        }

        $folder = $this->Folder->load($folderId);
        if (!$folder) {
            echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid folder'));

            return;
        }
        if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid policy'));

            return;
        }

        if ($folder->getParentId() < 0) {
            $rootFolder = $folder;
        } else {
            $rootFolder = $this->Folder->getRoot($folder);
        }
        $quota = $this->Sizequota_FolderQuota->getFolderQuota($rootFolder);
        $assetstoreFree = disk_free_space($this->Assetstore->getDefault()->getPath());
        if ($quota == '') {
            $free = $assetstoreFree;
        } else {
            $used = $this->Folder->getSize($rootFolder);
            $free = min($assetstoreFree, $quota - $used);
        }

        $free = number_format($free, 0, '.', '');
        $hFree = UtilityComponent::formatSize($free);
        echo JsonComponent::encode(array('status' => true, 'freeSpace' => $free, 'hFreeSpace' => $hFree));
    }

    /** Test whether the provided quota values are legal */
    private function _isValidQuota($quotas)
    {
        foreach ($quotas as $quota) {
            if ($quota !== '' && (!is_numeric($quota) || ((float) $quota) < 0)
            ) {
                return false;
            }
        }

        return true;
    }
}
