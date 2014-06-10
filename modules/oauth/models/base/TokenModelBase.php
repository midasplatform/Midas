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

abstract class Oauth_TokenModelBase extends Oauth_AppModel
  {
  /** constructor */
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
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User',
                        'parent_column' => 'user_id', 'child_column' => 'user_id'),
        'client' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Client', 'module' => $this->moduleName,
                          'parent_column' => 'client_id', 'child_column' => 'client_id')
        );
    $this->initialize(); // required
    } // end __construct()

  public abstract function getByToken($token);
  public abstract function getByUser($userDao, $onlyValid = true);
  public abstract function expireTokens($userDao, $clientDao);
  public abstract function cleanExpired();

  /**
   * Use the provided codeDao to create and return an oauth access token.
   * @param codeDao The code dao that should be used to create the access token
   * @param expire Argument to strtotime for the token expiration
   */
  public function createAccessToken($codeDao, $expire)
    {
    return $this->_createToken($codeDao, MIDAS_OAUTH_TOKEN_TYPE_ACCESS, $expire);
    }

  /**
   * Use the provided codeDao to create and return an oauth access token.
   * @param codeDao The code dao that should be used to create the access token
   */
  public function createRefreshToken($codeDao)
    {
    return $this->_createToken($codeDao, MIDAS_OAUTH_TOKEN_TYPE_REFRESH);
    }

  /**
   * Helper method to create the token dao from a code dao or refresh token dao
   * @param fromDao The authorization code dao or refresh token dao
   */
  private function _createToken($fromDao, $type, $expire = null)
    {
    $tokenDao = MidasLoader::newDao('TokenDao', $this->moduleName);
    $tokenDao->setToken(UtilityComponent::generateRandomString(32));
    $tokenDao->setType($type);
    $tokenDao->setScopes($fromDao->getScopes());
    $tokenDao->setUserId($fromDao->getUserId());
    $tokenDao->setClientId($fromDao->getClientId());
    $tokenDao->setCreationDate(date("Y-m-d H:i:s"));
    if(is_string($expire))
      {
      $tokenDao->setExpirationDate(date("Y-m-d H:i:s", strtotime($expire)));
      }
    else
      {
      $tokenDao->setExpirationDate(date("Y-m-d H:i:s"));
      }
    $this->save($tokenDao);

    return $tokenDao;
    }
  }
