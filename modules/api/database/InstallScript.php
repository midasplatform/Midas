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
