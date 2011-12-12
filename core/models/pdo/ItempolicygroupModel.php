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

require_once BASE_PATH.'/core/models/base/ItempolicygroupModelBase.php';

/**
 * \class ItempolicygroupModel
 * \brief Pdo Model
 */
class ItempolicygroupModel extends ItempolicygroupModelBase
{
  /** create a policy
   * @return ItempolicygroupDao*/
  public function createPolicy($group, $item, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$item->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group, $item) !== false)
      {
      $this->delete($this->getPolicy($group, $item));
      }
    $this->loadDaoClass('ItempolicygroupDao');
    $policyGroupDao = new ItempolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setItemId($item->getItemId());
    $policyGroupDao->setPolicy($policy);
    $this->save($policyGroupDao);

    $this->computePolicyStatus($item);
    return $policyGroupDao;
    }

  /** getPolicy
   * @return ItempolicygroupDao
   */
  public function getPolicy($group, $item)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    return $this->initDao('Itempolicygroup', $this->database->fetchRow($this->database->select()->where('item_id = ?', $item->getKey())->where('group_id = ?', $group->getKey())));
    }
} // end class
?>
