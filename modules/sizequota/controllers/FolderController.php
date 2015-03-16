<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
 * Folder controller for the sizequota module.
 *
 * @property Sizequota_FolderQuotaDao $Sizequota_FolderQuota
 */
class Sizequota_FolderController extends Sizequota_AppController
{
    /** @var array */
    public $_models = array('Assetstore', 'Folder', 'Setting');

    /** @var array */
    public $_moduleModels = array('FolderQuota');

    /**
     * Index action.
     *
     * @throws Zend_Exception
     */
    public function indexAction()
    {
        $this->disableLayout();

        if (!$this->getParam('folderId')) {
            throw new Zend_Exception('Invalid folder id parameter');
        }

        $folder = $this->Folder->load($this->getParam('folderId'));

        if (!$folder || !$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)) {
            throw new Zend_Exception('The folder does not exist or you do not have the necessary permissions');
        }

        if ($folder->getParentId() == -1) {
            $defaultQuota = $this->Setting->getValueByName(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_KEY, $this->moduleName);
        } elseif ($folder->getParentId() == -2) {
            $defaultQuota = $this->Setting->getValueByName(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_KEY, $this->moduleName);
        } else {
            throw new Zend_Exception('Must be a community or root user folder');
        }

        $form = new Sizequota_Form_Folder();

        $currentQuota = $this->Sizequota_FolderQuota->getQuota($folder);

        if ($currentQuota == false) {
            $quota = $defaultQuota;
            $useDefaultFolderQuota = 1;
        } else {
            $quota = $currentQuota->getQuota();
            $useDefaultFolderQuota = 0;
        }

        $quotaValueAndUnit = self::computeQuotaValueAndUnit($quota);

        $form->setDefault('folder_id', $folder->getFolderId());
        $form->setDefault('use_default_folder_quota', $useDefaultFolderQuota);
        $form->setDefault('folder_quota_value', $quotaValueAndUnit['value']);
        $form->setDefault('folder_quota_unit', $quotaValueAndUnit['unit']);

        $usedSpace = $this->Folder->getSize($folder);

        if ($quota == '') {
            $hQuota = $this->t('Unlimited');
            $hFreeSpace = '';
        } else {
            $hQuota = UtilityComponent::formatSize($quota);
            $hFreeSpace = UtilityComponent::formatSize($quota - $usedSpace);
        }

        $this->view->form = $form;
        $this->view->hDefaultQuota = UtilityComponent::formatSize($defaultQuota);
        $this->view->hFreeSpace = $hFreeSpace;
        $this->view->hQuota = $hQuota;
        $this->view->hUsedSpace = UtilityComponent::formatSize($usedSpace);
        $this->view->isAdmin = $this->userSession->Dao->isAdmin();
        $this->view->quota = $quota;
        $this->view->usedSpace = $usedSpace;

        session_start();
    }

    /** Submit action. */
    public function submitAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $quota = $this->getParam('folder_quota_value');
        $multiplier = $this->getParam('folder_quota_unit');

        if ($this->getParam('use_default_folder_quota') === 1) {
            $quota = null;
        }

        $folder = $this->Folder->load($this->getParam('folder_id'));

        if (!$folder) {
            echo JsonComponent::encode(array(false, 'Invalid folderId parameter'));

            return;
        }

        if (!is_null($quota) && $quota !== '') {
            if (!is_numeric($quota) || $quota < 0) {
                echo JsonComponent::encode(array(false, 'Invalid quota value. Please enter a positive number.'));

                return;
            }

            $quota *= $multiplier;
        }

        $this->Sizequota_FolderQuota->setQuota($folder, $quota);
        echo JsonComponent::encode(array(true, 'Changes saved'));
    }

    /** Get the amount of free space available for a folder. */
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
        $assetstoreFree = UtilityComponent::diskFreeSpace($this->Assetstore->getDefault()->getPath());
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

    /**
     * Compute quota value and unit.
     *
     * @param int|string $bytes
     * @return array
     */
    protected static function computeQuotaValueAndUnit($bytes)
    {
        if ($bytes >= MIDAS_SIZE_TB) {
            return array('value' => $bytes / MIDAS_SIZE_TB, 'unit' => MIDAS_SIZE_TB);
        }

        if ($bytes >= MIDAS_SIZE_GB) {
            return array('value' => $bytes / MIDAS_SIZE_GB, 'unit' => MIDAS_SIZE_GB);
        }

        if ($bytes >= MIDAS_SIZE_MB) {
            return array('value' => $bytes / MIDAS_SIZE_MB, 'unit' => MIDAS_SIZE_MB);
        }

        if ($bytes >= MIDAS_SIZE_KB) {
            return array('value' => $bytes / MIDAS_SIZE_KB, 'unit' => MIDAS_SIZE_KB);
        }

        if ($bytes > 0) {
            return array('value' => $bytes, 'unit' => MIDAS_SIZE_TB);
        }

        return array('value' => '', 'unit' => MIDAS_SIZE_MB);
    }
}
