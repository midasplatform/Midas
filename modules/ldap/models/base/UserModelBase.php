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

/** Base class for the ldap user model */
abstract class Ldap_UserModelBase extends Ldap_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'ldap_user';
    $this->_daoName = 'UserDao';
    $this->_key = 'ldap_user_id';

    $this->_mainData = array(
      'ldap_user_id' => array('type' => MIDAS_DATA),
      'user_id' => array('type' => MIDAS_DATA),
      'login' => array('type' => MIDAS_DATA),
      'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id')
      );
    $this->initialize();
    }

  public abstract function getLdapUser($login);
  public abstract function deleteByUser($userDao);
  public abstract function getByUser($userDao);

  /**
   * Create a new ldap user and an underlying core user entry.
   * @param ldapLogin What the user uses to actually login to the ldap
   * @param email The user's email (because it might be different from ldap login)
   * @param password The user's password (not stored, just used to generate initial default api key)
   * @param firstName User's first name
   * @param lastName User's last name
   * @return The ldap user dao that was created
   */
  public function createLdapUser($ldapLogin, $email, $password, $firstName, $lastName)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $userModel = $modelLoader->loadModel('User');
    $userDao = $userModel->createUser($email, $password, $firstName, $lastName);

    $userDao->setPassword(''); //remove password so that normal password based auth won't work
    $userModel->save($userDao);

    $this->loadDaoClass('UserDao', 'ldap');
    $ldapUserDao = new Ldap_UserDao();
    $ldapUserDao->setUserId($userDao->getKey());
    $ldapUserDao->setLogin($ldapLogin);
    $this->save($ldapUserDao);

    return $ldapUserDao;
    }
}
?>
