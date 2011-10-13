<?php
/*=========================================================================
  MIDAS Server

  Copyright (c) Kitware Inc. All rights reserved.
  See Copyright.txt or http://www.Kitware.com/Copyright.htm for details.

     This software is distributed WITHOUT ANY WARRANTY; without even
     the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
     PURPOSE.  See the above copyright notices for more information.

=========================================================================*/

/**
 * The install script for the api module
 */
class Api_InstallScript extends MIDASModuleInstallScript
  {

  /**
   * Pre-install callback does nothing
   */
  public function preInstall()
    {
    }

  /**
   * Post-install callback creates default api keys
   * for all existing users
   */
  public function postInstall()
    {
    include_once BASE_PATH.'/modules/api/models/AppModel.php';
    $modelLoader = new MIDAS_ModelLoader();
    $userModel = $modelLoader->loadModel('User');
    $userapiModel = $modelLoader->loadModel('Userapi', 'api');

    //limit this to 100 users; there shouldn't be very many when api is installed
    $users = $userModel->getAll(false, 100, 'admin');
    foreach($users as $user)
      {
      $userapiModel->createDefaultApiKey($user);
      }
    }
  }

?>
