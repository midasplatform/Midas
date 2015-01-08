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

/** ItempolicygroupModelBase */
abstract class ItempolicygroupModelBase extends AppModel
{
    /** Constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'itempolicygroup';

        $this->_mainData = array(
            'item_id' => array('type' => MIDAS_DATA),
            'group_id' => array('type' => MIDAS_DATA),
            'policy' => array('type' => MIDAS_DATA),
            'date' => array('type' => MIDAS_DATA),
            'item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'item_id',
                'child_column' => 'item_id',
            ),
            'group' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Group',
                'parent_column' => 'group_id',
                'child_column' => 'group_id',
            ),
        );
        $this->initialize(); // required
    }

    /** Delete group policies */
    abstract public function deleteGroupPolicies($group);

    /** Get policy */
    abstract public function getPolicy($group, $item);

    /** Compute policy status */
    abstract public function computePolicyStatus($item);

    /** @return ItempolicygroupDao */
    public function createPolicy($group, $item, $policy)
    {
        if (!$group instanceof GroupDao) {
            throw new Zend_Exception("Should be a group.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }
        if (!is_numeric($policy)) {
            throw new Zend_Exception("Should be a number.");
        }
        if (!$group->saved && !$item->saved) {
            throw new Zend_Exception("Save the daos first.");
        }
        $policyGroupDao = $this->getPolicy($group, $item);
        if ($policyGroupDao !== false) {
            $this->delete($policyGroupDao);
        }

        /** @var ItempolicygroupDao $policyGroupDao */
        $policyGroupDao = MidasLoader::newDao('ItempolicygroupDao');
        $policyGroupDao->setGroupId($group->getGroupId());
        $policyGroupDao->setItemId($item->getItemId());
        $policyGroupDao->setPolicy($policy);
        $this->save($policyGroupDao);

        if ($policyGroupDao->getGroupId() == MIDAS_GROUP_ANONYMOUS_KEY) {
            $this->computePolicyStatus($item);
        }

        return $policyGroupDao;
    }

    /** delete */
    public function delete($dao)
    {
        $item = $dao->getItem();
        parent::delete($dao);
        if ($dao->getGroupId() == MIDAS_GROUP_ANONYMOUS_KEY) {
            $this->computePolicyStatus($item);
        }
    }
}
