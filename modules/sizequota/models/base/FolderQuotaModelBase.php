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

/** Base class for the job log model */
abstract class Sizequota_FolderQuotaModelBase extends Sizequota_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'sizequota_folderquota';
    $this->_daoName = 'FolderQuotaDao';
    $this->_key = 'folderquota_id';

    $this->_mainData = array(
      'folderquota_id' => array('type' => MIDAS_DATA),
      'folder_id' => array('type' => MIDAS_DATA),
      'quota' => array('type' => MIDAS_DATA),
      'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id')
      );
    $this->initialize();
    }

  /** Get the quota dao for a particular folder, or return false if none is set */
  public abstract function getQuota($folder);

  /** Get the quota in bytes for a particular folder, or return the default if none is set */
  public function getFolderQuota($folder)
    {
    $quotaDao = $this->getQuota($folder);
    if(!$quotaDao)
      {
      $modelLoader = new MIDAS_ModelLoader();
      $settingModel = $modelLoader->loadModel('Setting');
      if($folder->getParentId() == -1) //user
        {
        $settingName = 'defaultuserquota';
        }
      else
        {
        $settingName = 'defaultcommunityquota';
        }
      return $settingModel->getValueByName($settingName, $this->moduleName);
      }
    return $quotaDao->getQuota();
    }

  /** Get the quota in bytes for a particular user, or return the default if none is set */
  public function getUserQuota($user)
    {
    $quotaDao = $this->getQuota($user->getFolder());
    if(!$quotaDao)
      {
      $modelLoader = new MIDAS_ModelLoader();
      $settingModel = $modelLoader->loadModel('Setting');
      return $settingModel->getValueByName('defaultuserquota', $this->moduleName);
      }
    return $quotaDao->getQuota();
    }

  /** Get the quota in bytes for a particular community, or return the default if none is set */
  public function getCommunityQuota($community)
    {
    $quotaDao = $this->getQuota($community->getFolder());
    if(!$quotaDao)
      {
      $modelLoader = new MIDAS_ModelLoader();
      $settingModel = $modelLoader->loadModel('Setting');
      return $settingModel->getValueByName('defaultcommunityquota', $this->moduleName);
      }
    return $quotaDao->getQuota();
    }

  /**
   * Set the quota on a folder.  Passing null or false as the quota will delete the entry for that folder.
   * Returns the saved folder quota dao, or false if it was deleted.
   */
  public function setQuota($folder, $quota)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception('Should be a folder.');
      }

    $oldQuota = $this->getQuota($folder);
    if($oldQuota !== false)
      {
      $this->delete($oldQuota);
      }
    if($quota !== null && $quota !== false)
      {
      $this->loadDaoClass('FolderQuotaDao', 'sizequota');
      $folderQuota = new Sizequota_FolderQuotaDao();
      $folderQuota->setFolderId($folder->getKey());
      $folderQuota->setQuota($quota);
      $this->save($folderQuota);

      return $folderQuota;
      }
    else
      {
      return false;
      }
    }
}
?>
