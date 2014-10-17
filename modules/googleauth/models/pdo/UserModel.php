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

require_once BASE_PATH.'/modules/googleauth/models/base/UserModelBase.php';

/** pdo model implementation */
class Googleauth_UserModel extends Googleauth_UserModelBase
{
    /**
     * Retrieve a dao with the given google person ID, or null if no such user
     * exists.
     *
     * @param $pid The google person ID to check.
     */
    public function getByGooglePersonId($pid)
    {
        $sql = $this->database->select()->where('google_person_id = ?', $pid);
        $row = $this->database->fetchRow($sql);

        return $this->initDao('User', $row, 'googleauth');
    }

    /**
     * Delete this to wipe the link between a google OAuth user and a core user
     * record. Must call when a core user record is being deleted.
     *
     * @param $userDao The core user dao.
     */
    public function deleteByUser($userDao)
    {
        $this->database->getDB()->delete('googleauth_user', 'user_id = '.$userDao->getKey());
    }
}
