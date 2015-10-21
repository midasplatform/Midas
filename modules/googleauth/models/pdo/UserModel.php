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

require_once BASE_PATH.'/modules/googleauth/models/base/UserModelBase.php';

/** Google user model for the googleauth module. */
class Googleauth_UserModel extends Googleauth_UserModelBase
{
    /**
     * Retrieve a Google user DAO with the given Google person id, or false if
     * no such user exists.
     *
     * @param string $googlePersonId Google person id to check
     * @return false|Googleauth_UserDao Google user DAO
     */
    public function getByGooglePersonId($googlePersonId)
    {
        $sql = $this->database->select()->where('google_person_id = ?', $googlePersonId);
        $row = $this->database->fetchRow($sql);

        return $this->initDao('User', $row, 'googleauth');
    }

    /**
     * Delete this to wipe the link between a Google user and a core user
     * record. Must call when a core user record is being deleted.
     *
     * @param UserDao $userDao User DAO
     */
    public function deleteByUser($userDao)
    {
        $this->database->getDB()->delete('googleauth_user', 'user_id = '.$userDao->getUserId());
    }
}
