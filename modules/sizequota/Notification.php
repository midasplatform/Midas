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

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

/** Notification manager for the sizequota module */
class Sizequota_Notification extends ApiEnabled_Notification
{
    /** @var string */
    public $moduleName = 'sizequota';

    /** @var array */
    public $_moduleComponents = array('Api');

    /** @var array */
    public $_models = array('Assetstore', 'Folder');

    /** Init notification process. */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', 'getCommunityTab');
        $this->addCallBack('CALLBACK_CORE_GET_USER_TABS', 'getUserTab');
        $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getScript');
        $this->addCallBack('CALLBACK_CORE_GET_SIMPLEUPLOAD_EXTRA_HTML', 'getExtraHtmlSimple');
        $this->addCallBack('CALLBACK_CORE_GET_JAVAUPLOAD_EXTRA_HTML', 'getExtraHtmlSimple');
        $this->addCallBack('CALLBACK_CORE_GET_REVISIONUPLOAD_EXTRA_HTML', 'getExtraHtmlRevision');
        $this->addCallBack('CALLBACK_CORE_GET_JAVAREVISIONUPLOAD_EXTRA_HTML', 'getExtraHtmlRevision');
        $this->addCallBack('CALLBACK_CORE_VALIDATE_UPLOAD', 'validateUpload');
        $this->addCallBack('CALLBACK_CORE_VALIDATE_UPLOAD_REVISION', 'validateUploadRevision');

        $this->enableWebAPI($this->moduleName);
    }

    /**
     * Add a tab to the manage community page for size quota.
     *
     * @param array $args parameters
     * @return array
     */
    public function getCommunityTab($args)
    {
        $community = $args['community'];
        $fc = Zend_Controller_Front::getInstance();
        $moduleWebroot = $fc->getBaseUrl().'/'.$this->moduleName;

        return array(
            $this->t('Storage Quota') => $moduleWebroot.'/folder/index?folderId='.$community->getFolderId(),
        );
    }

    /**
     * Add a tab to the user's main page for size quota.
     *
     * @param array $args parameters
     * @return array
     * @throws Zend_Exception
     */
    public function getUserTab($args)
    {
        $user = $args['user'];
        if ($this->Folder->policyCheck($user->getFolder(), $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            $fc = Zend_Controller_Front::getInstance();
            $moduleWebroot = $fc->getBaseUrl().'/'.$this->moduleName;

            return array(
                $this->t('Storage Quota') => $moduleWebroot.'/folder/index?folderId='.$user->getFolderId(),
            );
        } else {
            return array();
        }
    }

    /**
     * Add our JavaScript callback script.
     *
     * @return string
     */
    public function getScript()
    {
        $fc = Zend_Controller_Front::getInstance();
        $modulePublicWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;

        return '<script type="text/javascript" src="'.$modulePublicWebroot.'/public/js/common/common.notify.js"></script>';
    }

    /**
     * Add free space information to the DOM on the simple upload and Java upload pages.
     *
     * @param array $args parameters, including "folder", the folder DAO that you are uploading into
     * @return string
     * @throws Zend_Exception
     */
    public function getExtraHtmlSimple($args)
    {
        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var Sizequota_FolderQuotaModel $folderQuotaModel */
        $folderQuotaModel = MidasLoader::loadModel('FolderQuota', $this->moduleName);

        $folder = $args['folder'];
        if (!$folder) {
            return '<div id="sizequotaFreeSpace" style="display:none;">0</div>'.'<div id="sizequotaHFreeSpace" style="display:none;">--</div>';
        }
        $rootFolder = $folderModel->getRoot($folder);
        $quota = $folderQuotaModel->getFolderQuota($rootFolder);
        $assetstoreFree = UtilityComponent::diskFreeSpace($this->Assetstore->getDefault()->getPath());
        if ($quota == '') {
            $free = $assetstoreFree;
        } else {
            $used = $folderModel->getSize($rootFolder);
            $free = min($assetstoreFree, $quota - $used);
        }

        $free = number_format($free, 0, '.', '');
        $hFree = UtilityComponent::formatSize($free);

        return '<div id="sizequotaFreeSpace" style="display:none;">'.htmlspecialchars($free, ENT_QUOTES, 'UTF-8').'</div>'.'<div id="sizequotaHFreeSpace" style="display:none;">'.htmlspecialchars($hFree, ENT_QUOTES, 'UTF-8').'</div>';
    }

    /**
     * Add free space information to the DOM on the revision uploader page.
     *
     * @param array $args parameters, including "item", the item DAO that you are uploading a new revision into
     * @return string
     * @throws Zend_Exception
     */
    public function getExtraHtmlRevision($args)
    {
        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var Sizequota_FolderQuotaModel $folderQuotaModel */
        $folderQuotaModel = MidasLoader::loadModel('FolderQuota', $this->moduleName);

        $item = $args['item'];
        if (!$item) {
            return '<div id="sizequotaFreeSpace" style="display:none;">0</div>'.'<div id="sizequotaHFreeSpace" style="display:none;">--</div>';
        }
        $folders = $item->getFolders();
        if (count($folders) == 0) {
            // don't allow any more uploading into an orphaned item
            return '<div id="sizequotaFreeSpace" style="display:none;">0</div>'.'<div id="sizequotaHFreeSpace" style="display:none;">--</div>';
        } else {
            $rootFolder = $folderModel->getRoot($folders[0]);
            $quota = $folderQuotaModel->getFolderQuota($rootFolder);
            $assetstoreFree = UtilityComponent::diskFreeSpace($this->Assetstore->getDefault()->getPath());
            if ($quota == '') {
                $free = $assetstoreFree;
            } else {
                $used = $folderModel->getSize($rootFolder);
                $free = min($assetstoreFree, $quota - $used);
            }

            $free = number_format($free, 0, '.', '');
            $hFree = UtilityComponent::formatSize($free);

            return '<div id="sizequotaFreeSpace" style="display:none;">'.htmlspecialchars($free, ENT_QUOTES, 'UTF-8').'</div>'.'<div id="sizequotaHFreeSpace" style="display:none;">'.htmlspecialchars($hFree, ENT_QUOTES, 'UTF-8').'</div>';
        }
    }

    /**
     * Return whether or not the upload is allowed.  If uploading the file
     * will cause the size to surpass the quota, it will be rejected.
     *
     * @param array $args parameters, including "size", the size of the uploaded file,
     *                    and "folderId", the id of the folder being uploaded into
     * @return array array('status' => boolean, 'message' => 'error message if status is false')
     */
    public function validateUpload($args)
    {
        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var Sizequota_FolderQuotaModel $folderQuotaModel */
        $folderQuotaModel = MidasLoader::loadModel('FolderQuota', $this->moduleName);

        $folder = $folderModel->load($args['folderId']);
        if (!$folder) {
            return array('status' => false, 'message' => 'Invalid folder id');
        }
        $rootFolder = $folderModel->getRoot($folder);
        $quota = $folderQuotaModel->getFolderQuota($rootFolder);
        if ($quota == '') {
            return array('status' => true);
        }

        $freeSpace = $quota - $folderModel->getSize($rootFolder);
        $uploadSize = $args['size'];
        if ($uploadSize > $freeSpace) {
            return array(
                'status' => false,
                'message' => 'Upload quota exceeded. Free space: '.$freeSpace.'. Attempted upload size: '.$uploadSize.' into folder '.$args['folderId'],
            );
        }

        return array('status' => true);
    }

    /**
     * Return whether or not the upload is allowed.  If uploading the revision
     * will cause the size to surpass the quota, it will be rejected.
     *
     * @param array $args parameters, including "size", size of the uploaded file,
     *                    and "itemId", the id of the item being uploaded into
     * @return array array('status' => boolean, 'message' => 'error message if status is false')
     */
    public function validateUploadRevision($args)
    {
        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var Sizequota_FolderQuotaModel $folderQuotaModel */
        $folderQuotaModel = MidasLoader::loadModel('FolderQuota', $this->moduleName);

        $item = $itemModel->load($args['itemId']);
        if (!$item) {
            return array('status' => false, 'message' => 'Invalid item id');
        }
        $folders = $item->getFolders();
        if (count($folders) == 0) {
            return array('status' => false, 'message' => 'Cannot upload into an orphaned item');
        }
        $rootFolder = $folderModel->getRoot($folders[0]);
        $quota = $folderQuotaModel->getFolderQuota($rootFolder);
        if ($quota == '') {
            return array('status' => true);
        }

        $freeSpace = $quota - $folderModel->getSize($rootFolder);
        $uploadSize = $args['size'];
        if ($uploadSize > $freeSpace) {
            return array(
                'status' => false,
                'message' => 'Upload quota exceeded. Free space: '.$freeSpace.'. Attempted upload size: '.$uploadSize.' into item '.$args['itemId'],
            );
        }

        return array('status' => true);
    }
}
