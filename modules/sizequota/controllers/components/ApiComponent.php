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

// Web API error codes
define('MIDAS_SIZEQUOTA_INVALID_POLICY', -151);
define('MIDAS_SIZEQUOTA_INVALID_PARAMETER', -150);

/** Component for api methods */
class Sizequota_ApiComponent extends AppComponent
{
  public $userSession;

  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
        }
      }
    }

  /** Authenticate via token or session */
  private function _getUser($args)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $default = $this->userSession ? $this->userSession->Dao : null;
    return $authComponent->getUser($args, $default);
    }

  /**
   * Get the size quota for a user.
   * @param token Authentication token
   * @param user Id of the user to check
   * @return array('quota' => The size quota in bytes for the user, or empty string if unlimited,
                   'used' => Size in bytes currently used)
   */
  public function userGet($args)
    {
    $this->_checkKeys(array('token', 'user'), $args);
    $requestUser = $this->_getUser($args);

    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $userModel = $modelLoader->loadModel('User');

    $user = $userModel->load($args['user']);
    if(!$user)
      {
      throw new Exception('Invalid user id', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
      }

    if(!$folderModel->policyCheck($user->getFolder(), $requestUser, MIDAS_POLICY_READ))
      {
      throw new Exception('Read permission required', MIDAS_SIZEQUOTA_INVALID_POLICY);
      }
    $quotaModel = $modelLoader->loadModel('FolderQuota', 'sizequota');
    $quota = $quotaModel->getUserQuota($user);
    $used = $folderModel->getSize($user->getFolder());
    return array('quota' => $quota, 'used' => $used[0]->size);
    }

  /**
   * Get the size quota for a community.
   * @param token Authentication token
   * @param community Id of the community to check
   * @return array('quota' => The size quota in bytes for the community, or empty string if unlimited,
                   'used' => Size in bytes currently used)
   */
  public function communityGet($args)
    {
    $this->_checkKeys(array('token', 'community'), $args);
    $requestUser = $this->_getUser($args);

    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $commModel = $modelLoader->loadModel('Community');

    $comm = $commModel->load($args['community']);
    if(!$comm)
      {
      throw new Exception('Invalid community id', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
      }

    if(!$folderModel->policyCheck($comm->getFolder(), $requestUser, MIDAS_POLICY_READ))
      {
      throw new Exception('Read permission required', MIDAS_SIZEQUOTA_INVALID_POLICY);
      }
    $quotaModel = $modelLoader->loadModel('FolderQuota', 'sizequota');
    $quota = $quotaModel->getCommunityQuota($comm);
    $used = $folderModel->getSize($comm->getFolder());
    return array('quota' => $quota, 'used' => $used[0]->size);
    }

  /**
   * Set a quota for a folder. For MIDAS admin use only.
   * @param token Authentication token
   * @param folder The folder id
   * @param quota (optional) The quota. Pass a number of bytes or the empty string for unlimited.
     If this parameter isn't specified, deletes the current quota entry if one exists.
   */
  public function set($args)
    {
    $this->_checkKeys(array('token', 'folder'), $args);
    $user = $this->_getUser($args);

    if(!$user || !$user->isAdmin())
      {
      throw new Exception('Must be super-admin', MIDAS_SIZEQUOTA_INVALID_POLICY);
      }
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $folder = $folderModel->load($args['folder']);
    if(!$folder)
      {
      throw new Exception('Invalid folder id', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
      }
    if($folder->getParentId() > 0)
      {
      throw new Exception('Must be a root folder', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
      }
    $quota = array_key_exists('quota', $args) ? $args['quota'] : null;
    if($quota !== null && !preg_match('/^[0-9]*$/', $quota))
      {
      throw new Exception('Quota must be empty string or an integer if specified', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
      }
    $quotaModel = $modelLoader->loadModel('FolderQuota', 'sizequota');
    return $quotaModel->setQuota($folder, $quota);
    }

}
