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

/**
 * We must store the fact that a given user record represents a user who has
 * authenticated via Google Oauth, so we use this model to store that info.
 */
abstract class Googleauth_UserModelBase extends Googleauth_AppModel
{
    /** constructor */
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

    /** Get by Google person id */
    abstract public function getByGooglePersonId($pid);

    /** Delete by user */
    abstract public function deleteByUser($userDao);

    /**
     * Create a new record of a user who authenticates via google auth.
     *
     * @param $user The user dao representing this user's information
     * @param $googlePersonId The unique identifier value for the google user
     * @return The created googleauth_user dao.
     */
    public function createGoogleUser($user, $googlePersonId)
    {
        /** @var Googleauth_UserDao $guserDao */
        $guserDao = MidasLoader::newDao('UserDao', 'googleauth');
        $guserDao->setUserId($user->getKey());
        $guserDao->setGooglePersonId($googlePersonId);

        $this->save($guserDao);

        return $guserDao;
    }
}
