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
require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

/** notification manager for sizequota module */
class Sizequota_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'sizequota';
  public $_moduleComponents = array('Api');
  public $_models = array('Folder');

  /** init notification process */
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', 'getCommunityTab');
    $this->addCallBack('CALLBACK_CORE_GET_USER_TABS', 'getUserTab');
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getScript');
    $this->addCallBack('CALLBACK_CORE_GET_SIMPLEUPLOAD_EXTRA_HTML', 'getExtraHtmlSimple');
    $this->addCallBack('CALLBACK_CORE_GET_JAVAUPLOAD_EXTRA_HTML', 'getExtraHtmlSimple');
    $this->addCallBack('CALLBACK_CORE_GET_REVISIONUPLOAD_EXTRA_HTML', 'getExtraHtmlRevision');
    $this->addCallBack('CALLBACK_CORE_VALIDATE_UPLOAD', 'validateUpload');
    $this->addCallBack('CALLBACK_CORE_VALIDATE_UPLOAD_REVISION', 'validateUploadRevision');

    $this->enableWebAPI($this->moduleName);
    }

  /** Add a tab to the manage community page for size quota */
  public function getCommunityTab($args)
    {
    $community = $args['community'];
    $fc = Zend_Controller_Front::getInstance();
    $moduleWebroot = $fc->getBaseUrl().'/'.$this->moduleName;
    return array($this->t('Storage Quota') => $moduleWebroot.'/config/folder?folderId='.$community->getFolderId());
    }

  /** Add a tab to the user's main page for size quota */
  public function getUserTab($args)
    {
    $user = $args['user'];
    if($this->Folder->policyCheck($user->getFolder(), $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      $fc = Zend_Controller_Front::getInstance();
      $moduleWebroot = $fc->getBaseUrl().'/'.$this->moduleName;
      return array($this->t('Storage Quota') => $moduleWebroot.'/config/folder?folderId='.$user->getFolderId());
      }
    else
      {
      return array();
      }
    }

  /** Add our javascript callback script */
  public function getScript()
    {
    $fc = Zend_Controller_Front::getInstance();
    $modulePublicWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    return '<script type="text/javascript" src="'.$modulePublicWebroot.'/public/js/common/common.notify.js"></script>';
    }

  /**
   * Add free space information to the dom on the simple upload & java upload pages
   * @param folder The folder dao that you are uploading into
   */
  public function getExtraHtmlSimple($args)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $folderQuotaModel = $modelLoader->loadModel('FolderQuota', $this->moduleName);

    $folder = $args['folder'];
    $rootFolder = $folderModel->getRoot($folder);
    $quota = $folderQuotaModel->getFolderQuota($rootFolder);
    if($quota == '')
      {
      return '<div id="sizequotaFreeSpace" style="display:none;"></div>'.
             '<div id="sizequotaHFreeSpace" style="display:none;">'.$this->t('Unlimited').'</div>';
      }
    else
      {
      $used = $folderModel->getSize($rootFolder);
      $freeSpace = number_format($quota - $used, 0, '.', '');
      $hFreeSpace = UtilityComponent::formatSize($quota - $used);
      return '<div id="sizequotaFreeSpace" style="display:none;">'.$freeSpace.'</div>'.
             '<div id="sizequotaHFreeSpace" style="display:none;">'.$hFreeSpace.'</div>';
      }
    }

  /**
   * Add free space information to the dom on the revision uploader page
   * @param item The item dao that you are uploading a new revision into
   */
  public function getExtraHtmlRevision($args)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $folderQuotaModel = $modelLoader->loadModel('FolderQuota', $this->moduleName);

    $item = $args['item'];
    $folders = $item->getFolders();
    if(count($folders) == 0)
      {
      //don't allow any more uploading into an orphaned item
      return '<div id="sizequotaFreeSpace" style="display:none;">0</div>'.
             '<div id="sizequotaHFreeSpace" style="display:none;">'.$this->t('None').'</div>';
      }
    else
      {
      $rootFolder = $folderModel->getRoot($folders[0]);
      $quota = $folderQuotaModel->getFolderQuota($rootFolder);
      if($quota == '')
        {
        return '<div id="sizequotaFreeSpace" style="display:none;"></div>'.
               '<div id="sizequotaHFreeSpace" style="display:none;">'.$this->t('Unlimited').'</div>';
        }
      else
        {
        $used = $folderModel->getSize($rootFolder);
        $freeSpace = number_format($quota - $used, 0, '.', '');
        $hFreeSpace = UtilityComponent::formatSize($quota - $used);
        return '<div id="sizequotaFreeSpace" style="display:none;">'.$freeSpace.'</div>'.
               '<div id="sizequotaHFreeSpace" style="display:none;">'.$hFreeSpace.'</div>';
        }
      }
    }

  /**
   * Return whether or not the upload is allowed.  If uploading the file
   * will cause the size to surpass the quota, it will be rejected.
   * @param size Size of the uploaded file
   * @param folderId The id of the folder being uploaded into
   * @return array('status' => boolean, 'message' => 'error message if status is false')
   */
  public function validateUpload($args)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $folderQuotaModel = $modelLoader->loadModel('FolderQuota', $this->moduleName);

    $folder = $folderModel->load($args['folderId']);
    if(!$folder)
      {
      return array('status' => false, 'message' => 'Invalid folder id');
      }
    $rootFolder = $folderModel->getRoot($folder);
    $quota = $folderQuotaModel->getFolderQuota($rootFolder);
    if($quota == '')
      {
      return array('status' => true);
      }

    $freeSpace = $quota - $folderModel->getSize($rootFolder);
    $uploadSize = $args['size'];
    if($uploadSize > $freeSpace)
      {
      return array('status' => false,
                   'message' => 'Upload quota exceeded. Free space: '.$freeSpace.
                                '. Attempted upload size: '.$uploadSize.
                                ' into folder '.$args['folderId']);
      }
    return array('status' => true);
    }

  /**
   * Return whether or not the upload is allowed.  If uploading the revision
   * will cause the size to surpass the quota, it will be rejected.
   * @param size Size of the uploaded file
   * @param itemId The id of the item being uploaded into
   * @return array('status' => boolean, 'message' => 'error message if status is false')
   */
  public function validateUploadRevision($args)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $itemModel = $modelLoader->loadModel('Item');
    $folderQuotaModel = $modelLoader->loadModel('FolderQuota', $this->moduleName);

    $item = $itemModel->load($args['itemId']);
    if(!$item)
      {
      return array('status' => false, 'message' => 'Invalid item id');
      }
    $folders = $item->getFolders();
    if(count($folders) == 0)
      {
      return array('status' => false, 'message' => 'Cannot upload into an orphaned item');
      }
    $rootFolder = $folderModel->getRoot($folders[0]);
    $quota = $folderQuotaModel->getFolderQuota($rootFolder);
    if($quota == '')
      {
      return array('status' => true);
      }

    $freeSpace = $quota - $folderModel->getSize($rootFolder);
    $uploadSize = $args['size'];
    if($uploadSize > $freeSpace)
      {
      return array('status' => false,
                   'message' => 'Upload quota exceeded. Free space: '.$freeSpace.
                                '. Attempted upload size: '.$uploadSize.
                                ' into item '.$args['itemId']);
      }
    return array('status' => true);
    }

  } //end class
?>
