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

/** Component for api methods */
class Dicomextractor_ApiComponent extends AppComponent
{

  /**
   * Helper function for verifying keys in an input array
   */
  private function _validateParams($values, $keys)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }

  /**
   * Extract the dicom metadata from a revision
   * @path /dicomextractor/item/{id}
   * @http PUT
   * @idparam item
   * @param item the id of the item to be extracted
   * @return the id of the revision
   */
  function extract($args)
  {
    $utilityComponent = MidasLoader::loadComponent('Utility');
    $utilityComponent->renameParamKey($args, 'item', 'id');
    $this->_validateParams($args, array('id'));

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
