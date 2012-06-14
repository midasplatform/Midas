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

require_once BASE_PATH.'/modules/ldap/models/base/UserModelBase.php';

/**
 * Ldap user pdo model
 */
class Ldap_UserModel extends Ldap_UserModelBase
{
  /**
   * Pass the user's login credentials and see if they are an ldap user
   * @param login The user's login name
   * @return The Ldap_UserDao if this corresponds to an ldap user, otherwise false
   */
  public function getLdapUser($login)
    {
    if($login === '')
      {
      return false;
      }
    $sql = $this->database->select()->where('login = ?', $login);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('User', $row, 'ldap');
    if($dao)
      {
      return $dao;
      }
    else
      {
      return false;
      }
    }

  /**
   * Delete an ldap_user corresponding to the core user.
   * @param userDao The core user
   */
  public function deleteByUser($userDao)
    {
    $this->database->getDB()->delete('ldap_user', 'user_id = '.$userDao->getKey());
    }
}
