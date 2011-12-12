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
abstract class Api_UserapiModelBase extends Api_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'api_userapi';
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

  abstract function createKeyFromEmailPassword($appname, $email, $password);
  abstract function getByAppAndEmail($appname, $email);
  abstract function getByAppAndUser($appname, $userDao);
  abstract function getToken($email, $apikey, $appname);
  abstract function getUserapiFromToken($token);
  abstract function getByUser($userDao);

  /**
   * Create the user's default API key
   * @param string $userDao the user
   * @return success boolean
   */
  function createDefaultApiKey($userDao)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception('Error parameter: must be a userDao object');
      }
    $key = md5($userDao->getEmail().$userDao->getPassword().'Default');

    $rowset = $this->database->fetchAll($this->database->select()
                                                       ->where('user_id = ?', $userDao->getKey())
                                                       ->where('application_name = ?', 'Default'));
    $this->loadDaoClass('UserapiDao', 'api');

    if(count($rowset)) //update existing record if we have one already
      {
      $userApiDao = $this->initDao('Userapi', $rowset[0], 'api');
      $userApiDao->setApikey($key);
      $this->save($userApiDao);
      return;
      }

    // Otherwise save new default key
    $userApiDao = new Api_UserapiDao();
    $userApiDao->setUserId($userDao->getKey());
    $userApiDao->setApplicationName('Default');
    $userApiDao->setApikey($key);
    $userApiDao->setTokenExpirationTime(100);
    $userApiDao->setCreationDate(date('c'));
    $this->save($userApiDao);
    }

  /** Create a new API key */
  function createKey($userDao, $applicationname, $tokenexperiationtime)
    {
    if(!$userDao instanceof UserDao || !is_string($applicationname) || !is_string($tokenexperiationtime) || empty($applicationname))
      {
      throw new Zend_Exception("Error parameter");
      }

    // Check that the applicationname doesn't exist for this user
    $userapiDao = $this->getByAppAndUser($applicationname, $userDao);
    if(!empty($userapiDao))
      {
      return false;
      }
    $now = date("c");

    // We generate a challenge
    $keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $length = 40;

    // seed with microseconds
    list($usec, $sec) = explode(' ', microtime());
    srand((float) $sec + ((float) $usec * 100000));

    $key = "";
    $max = strlen($keychars) - 1;
    for($i = 0; $i < $length; $i++)
      {
      $key .= substr($keychars, rand(0, $max), 1);
      }

    $this->loadDaoClass('UserapiDao', 'api');
    $userApiDao = new Api_UserapiDao();
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
