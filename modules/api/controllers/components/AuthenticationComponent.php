<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
