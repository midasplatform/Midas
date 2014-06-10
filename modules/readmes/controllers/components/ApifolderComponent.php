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

/** Apifolder Component for api methods */
class Readmes_ApifolderComponent extends AppComponent
  {
  /**
   * Get the readme text for a folder
   * @path /readmes/folder/{id}
   * @http GET
   * @param id the id of the folder from which to get the readme
   * @return the text of the readme
   */
  function get($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $readmeComponent = MidasLoader::loadComponent('GetReadme', 'readmes');
    $apihelperComponent->validateParams($args, array('id'));

    $folderModel = MidasLoader::loadModel('Folder');

    $folderDao = $folderModel->load($args['id']);
    $readme = $readmeComponent->fromFolder($folderDao);

    return $readme;
    }
  }
