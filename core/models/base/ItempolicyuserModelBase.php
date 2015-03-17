<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** ItempolicyuserModelBase */
abstract class ItempolicyuserModelBase extends AppModel
{
    /** Constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'itempolicyuser';

        $this->_mainData = array(
            'item_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'policy' => array('type' => MIDAS_DATA),
            'item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'item_id',
                'child_column' => 'item_id',
            ),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
        );
        $this->initialize(); // required
    }

    /** Get policy */
    abstract public function getPolicy($user, $item);

    /** @return ItempolicyuserDao */
    public function createPolicy($user, $item, $policy)
    {
        if (!$user instanceof UserDao) {
            throw new Zend_Exception("Should be a user.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }
        if (!is_numeric($policy)) {
            throw new Zend_Exception("Should be a number.");
        }
        if (!$user->saved && !$item->saved) {
            throw new Zend_Exception("Save the daos first.");
        }
        $policyUser = $this->getPolicy($user, $item);
        if ($policyUser !== false) {
            $this->delete($policyUser);
        }

        /** @var ItempolicyuserDao $policyUser */
        $policyUser = MidasLoader::newDao('ItempolicyuserDao');
        $policyUser->setUserId($user->getUserId());
        $policyUser->setItemId($item->getItemId());
        $policyUser->setPolicy($policy);
        $this->save($policyUser);

        return $policyUser;
    }

    /** delete */
    public function delete($dao)
    {
        parent::delete($dao);
    }
}
