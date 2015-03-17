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

/** Token base model for the oauth module */
abstract class Oauth_TokenModelBase extends Oauth_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'oauth_token';
        $this->_key = 'token_id';

        $this->_mainData = array(
            'token_id' => array('type' => MIDAS_DATA),
            'token' => array('type' => MIDAS_DATA),
            'scopes' => array('type' => MIDAS_DATA),
            'client_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'creation_date' => array('type' => MIDAS_DATA),
            'expiration_date' => array('type' => MIDAS_DATA),
            'type' => array('type' => MIDAS_DATA),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
            'client' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Client',
                'module' => $this->moduleName,
                'parent_column' => 'client_id',
                'child_column' => 'client_id',
            ),
        );
        $this->initialize(); // required
    }

    /**
     * Retrieve a token DAO based on the token value. Checking if it has expired
     * is left up to the caller.
     *
     * @param string $token
     * @return false|Oauth_TokenDao
     */
    abstract public function getByToken($token);

    /**
     * Return all token DAOs for the given user.
     *
     * @param UserDao $userDao
     * @param bool $onlyValid
     * @return array
     */
    abstract public function getByUser($userDao, $onlyValid = true);

    /**
     * Expire all existing tokens for the given user and client.
     *
     * @param UserDao $userDao user DAO
     * @param Oauth_ClientDao $clientDao client DAO
     */
    abstract public function expireTokens($userDao, $clientDao);

    /** Remove expired access tokens from the database. */
    abstract public function cleanExpired();

    /**
     * Use the provided codeDao to create and return an oauth access token.
     *
     * @param Oauth_CodeDao $codeDao code DAO that should be used to create the access token
     * @param string $expire argument to strtotime for the token expiration
     * @return Oauth_TokenDao
     */
    public function createAccessToken($codeDao, $expire)
    {
        return $this->_createToken($codeDao, MIDAS_OAUTH_TOKEN_TYPE_ACCESS, $expire);
    }

    /**
     * Use the provided code DAO to create and return an oauth access token.
     *
     * @param Oauth_CodeDao $codeDao code DAO that should be used to create the access token
     * @return Oauth_TokenDao
     */
    public function createRefreshToken($codeDao)
    {
        return $this->_createToken($codeDao, MIDAS_OAUTH_TOKEN_TYPE_REFRESH);
    }

    /**
     * Helper method to create the token DAO from a code DAO or refresh token DAO.
     *
     * @param Oauth_CodeDao|Oauth_TokenDao $fromDao authorization code DAO or refresh token DAO
     * @param int $type
     * @param null|string $expire
     * @return Oauth_TokenDao
     * @throws Zend_Exception
     */
    private function _createToken($fromDao, $type, $expire = null)
    {
        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');

        /** @var Oauth_TokenDao $tokenDao */
        $tokenDao = MidasLoader::newDao('TokenDao', $this->moduleName);
        $tokenDao->setToken($randomComponent->generateString(32));
        $tokenDao->setType($type);
        $tokenDao->setScopes($fromDao->getScopes());
        $tokenDao->setUserId($fromDao->getUserId());
        $tokenDao->setClientId($fromDao->getClientId());
        $tokenDao->setCreationDate(date('Y-m-d H:i:s'));
        if (is_string($expire)) {
            $tokenDao->setExpirationDate(date('Y-m-d H:i:s', strtotime($expire)));
        } else {
            $tokenDao->setExpirationDate(date('Y-m-d H:i:s'));
        }
        $this->save($tokenDao);

        return $tokenDao;
    }
}
