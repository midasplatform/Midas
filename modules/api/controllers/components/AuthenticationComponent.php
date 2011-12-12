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

/** Web API Authentication Component */
class Api_AuthenticationComponent extends AppComponent
{

  /** Constructor */
  function __construct()
    {
    }

  /**
   * Gets the user dao from either the session (if via ajax)
   * or using token-based authentication otherwise.
   * Returns false for anonymous users.
   */
  public function getUser($args, $sessionDao)
    {
    if(array_key_exists('useSession', $args))
      {
      return $sessionDao;
      }
    else
      {
      if(!array_key_exists('token', $args))
        {
        return 0;
        }
      $token = $args['token'];
      $modelLoad = new MIDAS_ModelLoader();
      $userApiModel = $modelLoad->loadModel('Userapi', 'api');
      $userapiDao = $userApiModel->getUserapiFromToken($token);
      if(!$userapiDao)
        {
        throw new Exception('Invalid token', MIDAS_INVALID_TOKEN);
        }
      $userid = $userapiDao->getUserId();
      if($userid == 0)
        {
        return false;
        }
      $userModel = $modelLoad->loadModel('User');
      $userDao = $userModel->load($userid);
      return $userDao;
      }
    }
}
