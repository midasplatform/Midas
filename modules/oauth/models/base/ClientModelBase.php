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
abstract class Oauth_ClientModelBase extends Oauth_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'oauth_client';
    $this->_key = 'client_id';

    $this->_mainData = array(
        'client_id' => array('type' => MIDAS_DATA),
        'name' => array('type' => MIDAS_DATA),
        'secret' => array('type' => MIDAS_DATA),
        'identifier' => array('type' => MIDAS_DATA),
        'creation_date' => array('type' => MIDAS_DATA),
        'owner_id' => array('type' => MIDAS_DATA),
        'owner' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'owner_id')
        );
    $this->initialize(); // required
    } // end __construct()

  /**
   * Create and return a new oauth client owned by the given user.
   * @param userDao The owner of the client
   * @param name The human readable name of the client
   */
  public function create($userDao, $name)
    {
    if(!($userDao instanceof UserDao))
      {
      throw new Zend_Exception('Invalid userDao');
      }
    if(empty($name))
      {
      throw new Zend_Exception('Client name must not be empty');
      }
    $clientDao = MidasLoader::newDao('ClientDao', $this->moduleName);
    $clientDao->setName($name);
    $clientDao->setOwnerId($userDao->getKey());
    $clientDao->setIdentifier(UtilityComponent::generateRandomString(32));
    $clientDao->setSecret(UtilityComponent::generateRandomString(64));
    $clientDao->setCreationDate(date('c'));
    $this->save($clientDao);

    return $clientDao;
    }
}
?>
