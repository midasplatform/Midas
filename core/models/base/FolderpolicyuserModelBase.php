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

/** FolderpolicyuserModelBase */
abstract class FolderpolicyuserModelBase extends AppModel
{
    /** Constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'folderpolicyuser';
        $this->_mainData = array(
            'folder_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'policy' => array('type' => MIDAS_DATA),
            'date' => array('type' => MIDAS_DATA),
            'folder' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Folder',
                'parent_column' => 'folder_id',
                'child_column' => 'folder_id',
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
    abstract public function getPolicy($user, $folder);

    /** delete */
    public function delete($dao)
    {
        parent::delete($dao);
    }

    /**
     * Create a policy.
     *
     * @return FolderpolicyuserDao
     */
    public function createPolicy($user, $folder, $policy)
    {
        if (!$user instanceof UserDao) {
            throw new Zend_Exception('Should be a user.');
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception('Should be a folder.');
        }
        if (!is_numeric($policy)) {
            throw new Zend_Exception('Should be a number.');
        }
        if (!$user->saved && !$folder->saved) {
            throw new Zend_Exception('Save the daos first.');
        }
        $policyUser = $this->getPolicy($user, $folder);
        if ($policyUser !== false) {
            $this->delete($policyUser);
        }

        /** @var FolderpolicyuserDao $policyUser */
        $policyUser = MidasLoader::newDao('FolderpolicyuserDao');
        $policyUser->setUserId($user->getUserId());
        $policyUser->setFolderId($folder->getFolderId());
        $policyUser->setPolicy($policy);
        $this->save($policyUser);

        return $policyUser;
    }
}
