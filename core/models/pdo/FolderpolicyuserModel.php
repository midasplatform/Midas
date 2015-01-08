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

require_once BASE_PATH.'/core/models/base/FolderpolicyuserModelBase.php';

/**
 * Pdo Model
 */
class FolderpolicyuserModel extends FolderpolicyuserModelBase
{
    /**
     * Get policy
     *
     * @param UserDao $user
     * @param FolderDao $folder
     * @return false|FolderpolicyuserDao
     * @throws Zend_Exception
     */
    public function getPolicy($user, $folder)
    {
        if (!$user instanceof UserDao) {
            throw new Zend_Exception("Should be a user.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }

        return $this->initDao(
            'Folderpolicyuser',
            $this->database->fetchRow(
                $this->database->select()->where('folder_id = ?', $folder->getKey())->where(
                    'user_id = ?',
                    $user->getKey()
                )
            )
        );
    }
}
