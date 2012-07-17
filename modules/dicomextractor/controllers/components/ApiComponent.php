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
   * @param item the id of the item to be extracted
   * @return the id of the revision
   */
  function extract($args)
  {
    $this->_validateParams($args, array('item'));

    $itemModel = MidasLoader::loadModel("Item");
    $itemRevisionModel = MidasLoader::loadModel("ItemRevision");
    $itemDao = $itemModel->load($args['item']);
    $revisionDao = $itemModel->getLastRevision($itemDao);

    $componentLoader = new MIDAS_ComponentLoader();
    $dicomComponent = $componentLoader->loadComponent('Extractor',
                                                      'dicomextractor');
    $dicomComponent->extract($revisionDao);
    $dicomComponent->thumbnail($itemDao);
    return json_encode($revisionDao);
  }

}

?>
