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

require_once BASE_PATH.'/core/models/base/FolderpolicygroupModelBase.php';

/**
 * \class FolderpolicygroupModel
 * \brief Pdo Model
 */
class FolderpolicygroupModel extends FolderpolicygroupModelBase
{
  /** getPolicy
   * @return FolderpolicygroupDao
   */
  public function getPolicy($group, $folder)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    return $this->initDao('Folderpolicygroup', $this->database->fetchRow($this->database->select()->where('folder_id = ?', $folder->getKey())->where('group_id = ?', $group->getKey())));
    }

  /** compute policy status*/
  public function computePolicyStatus($folder)
    {
    $sql = $this->database->select()->from(array('fpg' => 'folderpolicygroup'), array('COUNT(*) as count'));
    $sql->where('fpg.folder_id = ?', $folder->getFolderId());
    $sql->where('fpg.group_id = ?', MIDAS_GROUP_ANONYMOUS_KEY);
    $row = $this->database->fetchRow($sql);
    $count = (int)$row['count'];

    $folderModel = MidasLoader::loadModel('Folder');
    if($count > 0)
      {
      $folder->setPrivacyStatus(MIDAS_PRIVACY_PUBLIC);
      $folderModel->save($folder);
      return MIDAS_PRIVACY_PUBLIC;
      }
    $folder->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE);
    $folderModel->save($folder);
    return MIDAS_PRIVACY_PRIVATE;
    }// end computePolicyStatus

  /**
   * deletes all folderpolicygroup rows associated with the passed in group
   * @param GroupDao
   */
  public function deleteGroupPolicies($group)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    $clause = 'group_id = '.$group->getKey();
    Zend_Registry::get('dbAdapter')->delete($this->_name, $clause);
    }
}
