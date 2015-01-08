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
 * This model represents the mapping of a temporary api token to the user's
 * real api token, which they will have access to once they enter their OTP correctly.
 */
abstract class Mfa_ApitokenModelBase extends Mfa_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'mfa_apitoken';
        $this->_key = 'apitoken_id';

        $this->_mainData = array(
            'apitoken_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'token_id' => array('type' => MIDAS_DATA),
            'creation_date' => array('type' => MIDAS_DATA),
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
     * Create a temporary token that will be used to fetch the user's real API token later
     *
     * @param UserDao $user user to create the token for
     * @param TokenDao $tokenDao token DAO
     * @return Mfa_ApitokenDao
     * @throws Zend_Exception
     */
    public function createTempToken($user, $tokenDao)
    {
        /** @var Mfa_ApitokenDao $newToken */
        $newToken = MidasLoader::newDao('ApitokenDao', 'mfa');
        $newToken->setUserId($user->getKey());
        $newToken->setTokenId($tokenDao->getKey());
        $newToken->setCreationDate(date('Y-m-d H:i:s'));
        $this->save($newToken);

        return $newToken;
    }
}
