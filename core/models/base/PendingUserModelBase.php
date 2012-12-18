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
 * Pending User Model Base
 * This model represents a user who has completed the registration information
 * but has yet to verify their email address.
 */
abstract class PendingUserModelBase extends AppModel
{
  /** Contructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'pendinguser';
    $this->_daoName = 'PendingUserDao';
    $this->_key = 'pendinguser_id';
    $this->_mainData = array(
      'pendinguser_id' => array('type' => MIDAS_DATA),
      'auth_key' => array('type' => MIDAS_DATA),
      'email' => array('type' => MIDAS_DATA),
      'firstname' => array('type' => MIDAS_DATA),
      'lastname' => array('type' => MIDAS_DATA),
      'password' => array('type' => MIDAS_DATA),
      'date_creation' => array('type' => MIDAS_DATA)
      );
    $this->initialize(); // required
    } // end __construct()

  /** abstract functions */
  public abstract function getByParams($params);
  public abstract function getAllByParams($params);

  /**
   * Create the database record for inviting a user via email that is not registered yet
   * @param email The email of the user (should not exist in Midas already)
   * @param group The group to invite the user to
   * @param inviter The user issuing the invitation (typically the session user)
   * @return the created NewUserInvitationDao
   */
  public function createPendingUser($email, $firstName, $lastName, $password)
    {
    $email = strtolower($email);
    $passwordPrefix = Zend_Registry::get('configGlobal')->password->prefix;
    if(isset($passwordPrefix) && !empty($passwordPrefix))
      {
      $password = $passwordPrefix.$password;
      }
    $pendingUser = MidasLoader::newDao('PendingUserDao');
    $pendingUser->setEmail($email);
    $pendingUser->setAuthKey(UtilityComponent::generateRandomString(64, '0123456789abcdef'));
    $pendingUser->setFirstname($firstName);
    $pendingUser->setLastname($lastName);
    $pendingUser->setPassword(md5($password));
    $pendingUser->setDateCreation(date('c'));

    $userModel = MidasLoader::loadModel('User');
    $existingUser = $userModel->getByEmail($email);
    if($existingUser)
      {
      throw new Zend_Exception('User with that email already exists');
      }
    $existingPendingUser = $this->getByParams(array('email' => $email));
    if($existingPendingUser)
      {
      $this->delete($existingPendingUser);
      }

    $this->save($pendingUser);
    return $pendingUser;
    }
}
