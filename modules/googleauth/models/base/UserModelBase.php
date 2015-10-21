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

/**
 * Google user base model for the googleauth module.
 *
 * We must store the fact that a given user record represents a user who has
 * authenticated via Google OAuth, so we use this model to store that info.
 */
abstract class Googleauth_UserModelBase extends Googleauth_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'googleauth_user';
        $this->_key = 'googleauth_user_id';

        $this->_mainData = array(
            'googleauth_user_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'google_person_id' => array('type' => MIDAS_DATA),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
        );
        $this->initialize(); // required
    }

    /**
     * Retrieve a Google user DAO with the given Google person id, or false if
     * no such user exists.
     *
     * @param string $googlePersonId Google person id to check
     * @return false|Googleauth_UserDao Google user DAO
     */
    abstract public function getByGooglePersonId($googlePersonId);

    /**
     * Delete this to wipe the link between a Google user and a core user
     * record. Must call when a core user record is being deleted.
     *
     * @param UserDao $userDao User DAO
     */
    abstract public function deleteByUser($userDao);

    /**
     * Create a new record of a user who authenticates via Google OAuth.
     *
     * @param UserDao $user User DAO representing this user's information
     * @param int $googlePersonId Unique identifier value for the Google user
     * @return Googleauth_UserDao Created Google user DAO
     */
    public function createGoogleUser($user, $googlePersonId)
    {
        /** @var Googleauth_UserDao $googleAuthUserDao */
        $googleAuthUserDao = MidasLoader::newDao('UserDao', 'googleauth');
        $googleAuthUserDao->setUserId($user->getKey());
        $googleAuthUserDao->setGooglePersonId($googlePersonId);
        $this->save($googleAuthUserDao);

        return $googleAuthUserDao;
    }
}
