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
abstract class Oauth_CodeModelBase extends Oauth_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'oauth_code';
    $this->_key = 'code_id';

    $this->_mainData = array(
        'code_id' => array('type' => MIDAS_DATA),
        'code' => array('type' => MIDAS_DATA),
        'scopes' => array('type' => MIDAS_DATA),
        'client_id' => array('type' => MIDAS_DATA),
        'user_id' => array('type' => MIDAS_DATA),
        'creation_date' => array('type' => MIDAS_DATA),
        'expiration_date' => array('type' => MIDAS_DATA),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User',
                        'parent_column' => 'user_id', 'child_column' => 'user_id'),
        'client' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Client', 'module' => $this->moduleName,
                          'parent_column' => 'client_id', 'child_column' => 'client_id')
        );
    $this->initialize(); // required
    } // end __construct()

  public abstract function getByUser($userDao);
  public abstract function getByCode($code);
  public abstract function cleanExpired();

  /**
   * Create and return a new oauth authorization code for the given client and user. Expires after 10 minutes
   * in accordance with the recommendation in the IETF draft v31
   * @param userDao The resource owner (end user to authenticate via the client)
   * @param clientDao The client that will be receiving the code
   * @param scopes The array of permission scopes (see api module constants)
   */
  public function create($userDao, $clientDao, $scopes)
    {
    if(!($userDao instanceof UserDao))
      {
      throw new Zend_Exception('Invalid userDao');
      }
    if(!($clientDao instanceof Oauth_ClientDao))
      {
      throw new Zend_Exception('Invalid userDao');
      }
    if(!is_array($scopes))
      {
      throw new Zend_Exception('Scopes must be an array');
      }
    $codeDao = MidasLoader::newDao('CodeDao', $this->moduleName);
    $codeDao->setCode(UtilityComponent::generateRandomString(32));
    $codeDao->setScopes(JsonComponent::encode($scopes));
    $codeDao->setUserId($userDao->getKey());
    $codeDao->setClientId($clientDao->getKey());
    $codeDao->setCreationDate(date("Y-m-d H:i:s"));
    $codeDao->setExpirationDate(date("Y-m-d H:i:s", strtotime('+10 minutes')));
    $this->save($codeDao);

    return $codeDao;
    }
}
?>
