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
abstract class UserapiModelBase extends AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'userapi';
    $this->_key = 'userapi_id';

    $this->_mainData = array(
        'userapi_id' => array('type' => MIDAS_DATA),
        'user_id' => array('type' => MIDAS_DATA),
        'apikey' => array('type' => MIDAS_DATA),
        'application_name' =>  array('type' => MIDAS_DATA),
        'token_expiration_time' =>  array('type' => MIDAS_DATA),
        'creation_date' =>  array('type' => MIDAS_DATA),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  abstract function getByAppAndEmail($appname, $email);
  abstract function getByAppAndUser($appname, $userDao);
  abstract function getToken($email, $apikey, $appname);
  abstract function getUserapiFromToken($token);
  abstract function getByUser($userDao);

  /**
   * Create the user's default API key (now just a random string)
   * @param userDao The user dao
   * @return success boolean
   */
  function createDefaultApiKey($userDao)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception('Error parameter: must be a userDao object when creating default api key.');
      }
    $key = UtilityComponent::generateRandomString(32);

    $rowset = $this->database->fetchAll($this->database->select()
                                                       ->where('user_id = ?', $userDao->getKey())
                                                       ->where('application_name = ?', 'Default'));

    if(count($rowset)) //update existing record if we have one already
      {
      $userApiDao = $this->initDao('Userapi', $rowset[0]);
      $userApiDao->setApikey($key);
      $this->save($userApiDao);
      return;
      }

    // Otherwise save new default key
    $userApiDao = MidasLoader::newDao('UserapiDao');
    $userApiDao->setUserId($userDao->getKey());
    $userApiDao->setApplicationName('Default');
    $userApiDao->setApikey($key);
    $userApiDao->setTokenExpirationTime(100);
    $userApiDao->setCreationDate(date("Y-m-d H:i:s"));
    $this->save($userApiDao);
    }

  /** Create a new API key */
  function createKey($userDao, $applicationname, $tokenexperiationtime)
    {
    if(!$userDao instanceof UserDao || !is_string($applicationname) || !is_string($tokenexperiationtime) || empty($applicationname))
      {
      throw new Zend_Exception("Error parameter when creating API key.");
      }

    // Check that the applicationname doesn't exist for this user
    $userapiDao = $this->getByAppAndUser($applicationname, $userDao);
    if(!empty($userapiDao))
      {
      return false;
      }
    $now = date("Y-m-d H:i:s");

    $key = UtilityComponent::generateRandomString(40);

    $userApiDao = MidasLoader::newDao('UserapiDao');
    $userApiDao->setUserId($userDao->getKey());
    $userApiDao->setApikey($key);
    $userApiDao->setApplicationName($applicationname);
    $userApiDao->setTokenExpirationTime($tokenexperiationtime);
    $userApiDao->setCreationDate($now);

    $this->save($userApiDao);
    return $userApiDao;
    }

}
?>
