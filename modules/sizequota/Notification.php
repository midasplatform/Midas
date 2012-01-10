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
    $this->addCallBack('CALLBACK_CORE_GET_SIMPLEUPLOAD_EXTRA_HTML', 'getSimpleuploadExtraHtml');
    //$this->addCallBack('CALLBACK_CORE_VALIDATE_UPLOAD', 'validateUpload');

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

  /** Add free space information to the dom on the simple upload page */
  public function getSimpleuploadExtraHtml($args)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $folderQuotaModel = $modelLoader->loadModel('FolderQuota', $this->moduleName);

    $folder = $args['folder'];
    $rootFolder = $folderModel->getRoot($folder);
    $quota = $folderQuotaModel->getQuota($rootFolder);
    if($quota === false)
      {
      return '<div id="sizequotaFreeSpace" style="display:none;"></div>';
      }
    else
      {
      $freeSpace = number_format($quota->getQuota() - $folderModel->getSize($rootFolder), 0, '.', '');
      return '<div id="sizequotaFreeSpace" style="display:none;">'.$freeSpace.'</div>';
      }
    }

  /** Return whether or not the upload is allowed.  If uploading the file
   *  will cause the size to pass the quota, it will be rejected.
   */
  public function validateUpload($args)
    {
    //TODO
    return true;
    }
  } //end class
?>
