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
 * Pdo Model
 */
class ItempolicygroupModel extends ItempolicygroupModelBase
{
    /** compute policy status */
    public function computePolicyStatus($item)
    {
        $sql = $this->database->select()->from(array('ipg' => 'itempolicygroup'), array('COUNT(*) as count'));
        $sql->where('ipg.item_id = ?', $item->getItemId());
        $sql->where('ipg.group_id = ?', MIDAS_GROUP_ANONYMOUS_KEY);
        $row = $this->database->fetchRow($sql);
        $count = (int)$row['count'];

        $itemModel = MidasLoader::loadModel('Item');
        if ($count > 0) {
            $item->setPrivacyStatus(MIDAS_PRIVACY_PUBLIC);
            $itemModel->save($item, false);

            return MIDAS_PRIVACY_PUBLIC;
        }
        $item->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE);
        $itemModel->save($item, false);

        return MIDAS_PRIVACY_PRIVATE;
    }

    /** getPolicy
     *
     * @return ItempolicygroupDao
     */
    public function getPolicy($group, $item)
    {
        if (!$group instanceof GroupDao) {
            throw new Zend_Exception("Should be a group.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }

        return $this->initDao(
            'Itempolicygroup',
            $this->database->fetchRow(
                $this->database->select()->where('item_id = ?', $item->getKey())->where(
                    'group_id = ?',
                    $group->getKey()
                )
            )
        );
    }

    /**
     * deletes all itempolicygroup rows associated with the passed in group
     *
     * @param GroupDao
     */
    public function deleteGroupPolicies($group)
    {
        if (!$group instanceof GroupDao) {
            throw new Zend_Exception("Should be a group.");
        }
        $clause = 'group_id = '.$group->getKey();
        Zend_Registry::get('dbAdapter')->delete($this->_name, $clause);
    }
}
