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

/** UuidComponent componenet */
class UuidComponent extends AppComponent
{

  /** Get using id*/
  public function getByUid($uuid)
    {
    $loader = new MIDAS_ModelLoader();
    $model = $loader->loadModel('Community');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_COMMUNITY;
      return $dao;
      }

    $model = $loader->loadModel('Folder');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_FOLDER;
      return $dao;
      }

    $model = $loader->loadModel('Item');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_ITEM;
      return $dao;
      }

    $model = $loader->loadModel('ItemRevision');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_REVISION;
      return $dao;
      }

    $model = $loader->loadModel('User');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_USER;
      return $dao;
      }
    return false;
    }
} // end class
