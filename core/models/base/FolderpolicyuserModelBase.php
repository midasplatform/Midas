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

/** FolderpolicyuserModelBase */
abstract class FolderpolicyuserModelBase extends AppModel
{
  /** Constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'folderpolicyuser';
    $this->_mainData = array(
          'folder_id' => array('type' => MIDAS_DATA),
          'user_id' => array('type' => MIDAS_DATA),
          'policy' => array('type' => MIDAS_DATA),
          'date' => array('type' => MIDAS_DATA),
          'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id'),
          'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id')
        );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getPolicy($user, $folder);

  /** delete */
  public function delete($dao)
    {
    $folder = $dao->getFolder();
    parent::delete($dao);
    $modelLoad = new MIDAS_ModelLoader();
    $folderGroupModel = $modelLoad->loadModel('Folderpolicygroup');
    $folderGroupModel->computePolicyStatus($folder);
    }//end delete

  /** create a policy
   * @return FolderpolicyuserDao*/
  public function createPolicy($user, $folder, $policy)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$user->saved && !$folder->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($user, $folder) !== false)
      {
      $this->delete($this->getPolicy($user, $folder));
      }
    $this->loadDaoClass('FolderpolicyuserDao');
    $policyUser = new FolderpolicyuserDao();
    $policyUser->setUserId($user->getUserId());
    $policyUser->setFolderId($folder->getFolderId());
    $policyUser->setPolicy($policy);
    $this->save($policyUser);

    $modelLoad = new MIDAS_ModelLoader();
    $folderGroupModel = $modelLoad->loadModel('Folderpolicygroup');
    $folderGroupModel->computePolicyStatus($folder);
    return $policyUser;
    }

} // end class FolderpolicyuserModelBase
