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

/** Apifolder Component for api methods */
class Dicomextractor_ApifolderComponent extends AppComponent
{
  /**
   * Extract the dicom metadata from items in a folder and merge them
   * @path /dicomextractor/folder/{id}
   * @http PUT
   * @param id the id of the folder containing items to be extracted
   * @return the id of the folder
   */
  function extract($args)
  {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));

    $folderModel = MidasLoader::loadModel("Folder");
    $authComponent = MidasLoader::loadComponent('Authentication');
    $folderDao = $folderModel->load($args['id']);
    $userDao = $authComponent->getUser($args,
                                       Zend_Registry::get('userSession')->Dao);

    $dicomComponent = MidasLoader::loadComponent('Extractor',
                                                 'dicomextractor');

    $count = $dicomComponent->extractAndMergeFromFolder($folderDao, $userDao);

    return json_encode($count);
  }

}
