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

/** Apicommunity Component for api methods */
class Readmes_ApicommunityComponent extends AppComponent
  {
  /**
   * Get the readme text for a community
   * @path /readmes/community/{id}
   * @http GET
   * @param id the id of the community from which to get the readme
   * @return the text of the readme
   */
  function get($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $readmeComponent = MidasLoader::loadComponent('GetReadme', 'readmes');
    $apihelperComponent->validateParams($args, array('id'));

    $communityModel = MidasLoader::loadModel('Community');

    $communityDao = $communityModel->load($args['id']);
    $readme = $readmeComponent->fromCommunity($communityDao);

    return $readme;
    }
  }
