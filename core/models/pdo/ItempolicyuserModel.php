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

require_once BASE_PATH.'/core/models/base/ItempolicyuserModelBase.php';

/**
 * \class ItempolicyuserModel
 * \brief Pdo Model
 */
class ItempolicyuserModel  extends ItempolicyuserModelBase
{
  /** create a policy
   * @return ItempolicyuserDao*/
  public function createPolicy($user, $item, $policy)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$user->saved && !$item->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($user, $item) !== false)
      {
      $this->delete($this->getPolicy($user, $item));
      }
    $this->loadDaoClass('ItempolicyuserDao');
    $policyUser = new ItempolicyuserDao();
    $policyUser->setUserId($user->getUserId());
    $policyUser->setItemId($item->getItemId());
    $policyUser->setPolicy($policy);
    $this->save($policyUser);

    $modelLoad = new MIDAS_ModelLoader();
    $fitemGroupModel = $modelLoad->loadModel('Itempolicygroup');
    $fitemGroupModel->computePolicyStatus($item);
    return $policyUser;
    }

  /** getPolicy
   * @return ItempolicyuserDao
   */
  public function getPolicy($user, $item)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    return $this->initDao('Itempolicyuser', $this->database->fetchRow($this->database->select()->where('item_id = ?', $item->getKey())->where('user_id = ?', $user->getKey())));
    }
}
?>
