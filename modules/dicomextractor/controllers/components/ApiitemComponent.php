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

/** Apiitem Component for api methods */
class Dicomextractor_ApiitemComponent extends AppComponent
{
  /**
   * Extract the dicom metadata from a revision
   * @path /dicomextractor/item/{id}
   * @http PUT
   * @param id the id of the item to be extracted
   * @return the id of the revision
   */
  function extract($args)
  {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));

    $itemModel = MidasLoader::loadModel("Item");
    $itemRevisionModel = MidasLoader::loadModel("ItemRevision");
    $authComponent = MidasLoader::loadComponent('Authentication');
    $itemDao = $itemModel->load($args['id']);
    $userDao = $authComponent->getUser($args,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception('You didn\'t log in or you don\'t have the write '.
        'permission for the given item.', MIDAS_INVALID_POLICY);
      }

    $revisionDao = $itemModel->getLastRevision($itemDao);

    $dicomComponent = MidasLoader::loadComponent('Extractor',
                                                      'dicomextractor');
    $dicomComponent->extract($revisionDao);
    $dicomComponent->thumbnail($itemDao);
    return json_encode($revisionDao);
  }

}

?>
