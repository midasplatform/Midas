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

/** These are the implementations of the web api methods for ItemRevision */
class ApiitemrevisionComponent extends AppComponent
  {

  /**
   * Function for grabbing bitstream id (used in itemrevisionGet)
   */
  function getBitstreamId($bitstream)
    {
    return $bitstream->getBitstreamId();
    }

  /**
   * Fetch the information about an ItemRevision
   * @path /itemrevision/{id}
   * @http GET
   * @param id The id of the ItemRevision
   * @return ItemRevision object
   */
  function itemrevisionGet($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemrevision_id = $args['id'];
    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    $itemRevision = $itemRevisionModel->load($itemrevision_id);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($itemRevision->getItemId());
    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $in = $itemRevision->toArray();
    $out = array();
    $out['id'] = $in['itemrevision_id'];
    $out['item_id'] = $in['item_id'];
    $out['date_created'] = $in['date'];
    $out['date_updated'] = $in['date']; // fix this
    $out['changes'] = $in['changes'];
    $out['user_id'] = $in['user_id'];
    $out['license_id'] = $in['license_id'];
    $out['uuid'] = $in['uuid'];
    $out['bitstreams'] = array_map(array($this, 'getBitstreamId'),
        $itemRevision->getBitstreams());

    return $out;
    }
  } // end of class
