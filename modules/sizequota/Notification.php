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

/** notification manager for sizequota module */
class Sizequota_Notification extends MIDAS_Notification
  {
  public $moduleName = 'sizequota';
  public $_moduleComponents = array();
  public $_models = array('Folder');

  /** init notification process */
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_MANAGE_TABS', 'getCommunityTab');
    $this->addCallBack('CALLBACK_CORE_GET_USER_TABS', 'getUserTab');
    //$this->addCallBack('CALLBACK_CORE_VALIDATE_UPLOAD', 'validateUpload');
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