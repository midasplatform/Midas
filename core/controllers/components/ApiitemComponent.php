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

/** These are the implementations of the web api methods for item*/
class ApiitemComponent extends AppComponent
  {
  /**
   * Get the item's metadata
   * @path /item/metadata/{id}
   * @http GET
   * @param id The id of the item
   * @param revision (Optional) Revision of the item. Defaults to latest revision
   * @return the sought metadata array on success,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemGetmetadata($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemid = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($itemid);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $revisionDao = $apihelperComponent->getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);
    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    $metadata = $itemRevisionModel->getMetadata($revisionDao);
    $metadataArray = array();
    foreach($metadata as $m)
      {
      $metadataArray[] = $m->toArray();
      }
    return $metadataArray;
    }

  /**
   * Set a metadata field on an item
   * @path /item/setmetadata/{id}
   * @http PUT
   * @param id The id of the item
   * @param element The metadata element
   * @param value The metadata value for the field
   * @param qualifier (Optional) The metadata qualifier. Defaults to empty string.
   * @param type (Optional) The metadata type (integer constant). Defaults to MIDAS_METADATA_TEXT type (0).
   * @param revision (Optional) Revision of the item. Defaults to latest revision.
   * @return true on success,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemSetmetadata($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'element', 'value'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['id']);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $type = array_key_exists('type', $args) ? (int)$args['type'] : MIDAS_METADATA_TEXT;
    $qualifier = array_key_exists('qualifier', $args) ? $args['qualifier'] : '';
    $element = $args['element'];
    $value = $args['value'];

    $revisionDao = $apihelperComponent->getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);
    $apihelperComponent->setMetadata($item, $type, $element, $qualifier, $value, $revisionDao);
    return true;
    }

  /**
   * Set multiple metadata fields on an item, requires specifying the number of
     metadata tuples to add.
   * @path /item/setmultiplemetadata/{id}
   * @http PUT
   * @param id The id of the item
     @param revision (Optional) Item Revision number to set metadata on, defaults to latest revision.
   * @param count The number of metadata tuples that will be set.  For every one
     of these metadata tuples there will be the following set of params with counters
     at the end of each param name, from 1..<b>count</b>, following the example
     using the value <b>i</b> (i.e., replace <b>i</b> with values 1..<b>count</b>)
     (<b>element_i</b>, <b>value_i</b>, <b>qualifier_i</b>, <b>type_i</b>).

     @param element_i metadata element for tuple i
     @param value_i   metadata value for the field, for tuple i
     @param qualifier_i (Optional) metadata qualifier for tuple i. Defaults to empty string.
     @param type_i (Optional) metadata type (integer constant). Defaults to MIDAS_METADATA_TEXT type (0).
   * @return true on success,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemSetmultiplemetadata($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'count'));
    $metadataTuples = $apihelperComponent->parseMetadataTuples($args);
    $userDao = $apihelperComponent->getUser($args);

    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['id']);
    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $revisionNumber = array_key_exists('revision', $args) ? (int)$args['revision'] : null;
    $revision = $apihelperComponent->getItemRevision($item, $revisionNumber);

    foreach($metadataTuples as $tup)
      {
      $apihelperComponent->setMetadata($item, $tup['type'], $tup['element'], $tup['qualifier'], $tup['value'], $revision);
      }
    return true;
    }

  /**
     Delete a metadata tuple (element, qualifier, type) from a specific item revision,
     defaults to the latest revision of the item.
   * @path /item/deletemetadata/{id}
   * @http PUT
   * @param id The id of the item
   * @param element The metadata element
   * @param qualifier (Optional) The metadata qualifier. Defaults to empty string.
   * @param type (Optional) metadata type (integer constant).
     Defaults to MIDAS_METADATA_TEXT (0).
   * @param revision (Optional) Revision of the item. Defaults to latest revision.
   * @return true on success,
             false if the metadata was not found on the item revision,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemDeletemetadata($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'element'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['id']);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $element = $args['element'];
    $qualifier = array_key_exists('qualifier', $args) ? $args['qualifier'] : '';
    $type = array_key_exists('type', $args) ? (int)$args['type'] : MIDAS_METADATA_TEXT;

    $revisionDao = $apihelperComponent->getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);

    $metadataModel = MidasLoader::loadModel('Metadata');
    $metadata = $metadataModel->getMetadata($type, $element, $qualifier);
    if(!isset($metadata) || $metadata === false)
      {
      return false;
      }

    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    $itemRevisionModel->deleteMetadata($revisionDao, $metadata->getMetadataId());

    return true;
    }

  /**
     Deletes all metadata associated with a specific item revision;
     defaults to the latest revision of the item;
     pass <b>revision</b>=<b>all</b> to delete all metadata from all revisions.
   * @path /item/deletemetadataall/{id}
   * @http PUT
   * @param id The id of the item
   * @param revision (Optional)
     Revision of the item. Defaults to latest revision; pass <b>all</b> to delete all metadata from all revisions.
   * @return true on success,
     will fail if there are no revisions or the specified revision is not found.
   */
  function itemDeletemetadataAll($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['id']);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    if(array_key_exists('revision', $args) && $args['revision'] === 'all')
      {
      $revisions = $item->getRevisions();
      if(sizeof($revisions) === 0)
        {
        throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
        }
      foreach($revisions as $revisionDao)
        {
        $itemRevisionModel->deleteMetadata($revisionDao);
        }
      }
    else
      {
      $revisionDao = $apihelperComponent->getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);
      if(isset($revisionDao) && $revisionDao !== false)
        {
        $itemRevisionModel->deleteMetadata($revisionDao);
        }
      }

    return true;
    }

  /**
   * Check whether an item with the given name exists in the given folder
   * @path /item/exists
   * @http GET
   * @param parentid The id of the parent folder
   * @param name The name of the item
   * @return array('exists' => bool)
   */
  function itemExists($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('name', 'parentid'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);
    $folderModel = MidasLoader::loadModel('Folder');
    $itemModel = MidasLoader::loadModel('Item');
    $folder = $folderModel->load($args['parentid']);
    if(!$folder)
      {
      throw new Exception('Invalid parentid', MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception('Read permission required on folder', MIDAS_INVALID_POLICY);
      }
    $existingItem = $itemModel->existsInFolder($args['name'], $folder);
    if($existingItem instanceof ItemDao && $itemModel->policyCheck($existingItem, $userDao))
      {
      return array('exists' => true, 'item' => $existingItem->toArray());
      }
    else
      {
      return array('exists' => false);
      }
    }

  /**
   * Return all items given a name and (optionally) a parent folder name
   * @path /item/search
   * @http GET
   * @param name The name of the item to search by
   * @param folderName (Optional) The name of the parent folder to search by
   * @param folderId (Optional) The id of the parent folder to search by
   * @return A list of all items with the given name and parent folder name or id
   */
  function itemSearch($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('name'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);
    $itemModel = MidasLoader::loadModel('Item');

    if(array_key_exists('folderId', $args))
      {
      $itemDaos = $itemModel->getByNameAndFolderId($args['name'], $args['folderId']);
      }
    else if(array_key_exists('folderName', $args))
      {
      $itemDaos = $itemModel->getByNameAndFolderName($args['name'], $args['folderName']);
      }
    else
      {
      $itemDaos = $itemModel->getByName($args['name']);
      }

    $folderModel = MidasLoader::loadModel('Folder');
    $matchList = array();

    foreach($itemDaos as $itemDao)
      {
      if($itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_READ))
        {
        $itemArray = $itemDao->toArray();
        $folderDaos = $itemDao->getFolders();
        $folderId = -1;

        foreach($folderDaos as $folderDao)
          {
          if($folderModel->policyCheck($folderDao, $userDao, MIDAS_POLICY_READ))
            {
            $folderId = $folderDao->getKey();
            break;
            }
          }

        if($folderId != -1)
          {
          $itemArray['folder_id'] = $folderId;
          }

        $matchList[] = $itemArray;
        }
      }

    return array('items' => $matchList);
    }

  /**
   * Get an item's information
   * @path /item/{id}
   * @http GET
   * @param id The item id
   * @param head (Optional) only list the most recent revision
   * @return The item object
   */
  function itemGet($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemid = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($itemid);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $itemArray = $item->toArray();

    $owningFolders = $item->getFolders();
    if(count($owningFolders) > 0)
      {
      $itemArray['folder_id'] = $owningFolders[0]->getKey();
      }

    $revisionsArray = array();
    if(array_key_exists('head', $args))
      {
      $revisions = array($itemModel->getLastRevision($item));
      }
    else //get all revisions
      {
      $revisions = $item->getRevisions();
      }

    foreach($revisions as $revision)
      {
      if(!$revision)
        {
        continue;
        }
      $bitstreamArray = array();
      $bitstreams = $revision->getBitstreams();
      foreach($bitstreams as $b)
        {
        $bitstreamArray[] = $b->toArray();
        }
      $tmp = $revision->toArray();
      $tmp['bitstreams'] = $bitstreamArray;
      $revisionsArray[] = $tmp;
      }
    $itemArray['revisions'] = $revisionsArray;
    $itemArray['extraFields'] = $apihelperComponent->getItemExtraFields($item);

    return $itemArray;
    }

  /**
   * Function for grabbing revision id (used in itemGetWrapper)
   */
  function getRevisionId($revision)
    {
    return $revision['itemrevision_id'];
    }

  /**
   * Function for grabbing bitstream id (used in itemGetWrapper)
   */
  function getBitstreamId($bitstream)
    {
    return $bitstream['bitstream_id'];
    }

  /**
   * Wrapper for the item get that helps make our new API consistent.
   */
  function itemGetWrapper($args)
    {
    $in = $this->itemGet($args);
    $out = array();
    $out['id'] = $in['item_id'];
    $out['name'] = $in['name'];
    $out['description'] = $in['description'];
    $out['size'] = $in['sizebytes'];
    $out['date_created'] = $in['date_creation'];
    $out['date_updated'] = $in['date_update'];
    $out['uuid'] = $in['uuid'];
    $out['views'] = $in['view'];
    $out['downloads'] = $in['download'];
    $out['public'] = $in['privacy_status'] == 0;
    $out['revisions'] = array_map(array($this, 'getRevisionId'),
      $in['revisions']);
    $head_revision = end($in['revisions']);
    $out['bitstreams'] = array_map(array($this, 'getBitstreamId'),
      $head_revision['bitstreams']);
    return $out;
    }

  /**
   * List the permissions on an item, requires Admin access to the item.
   * @path /item/permission/{id}
   * @http GET
   * @param item_id The id of the item
   * @return A list with three keys: privacy, user, group; privacy will be the
     item's privacy string [Public|Private]; user will be a list of
     (user_id, policy, email); group will be a list of (group_id, policy, name).
     policy for user and group will be a policy string [Admin|Write|Read].
   */
  public function itemListPermissions($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');
    $itemModel = MidasLoader::loadModel('Item');
    $itemId = $args['id'];
    $item = $itemModel->load($itemId);

    if($item === false)
      {
      throw new Exception("This item doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the item to list permissions.", MIDAS_INVALID_POLICY);
      }

    return $apihelperComponent->listResourcePermissions($itempolicygroupModel->computePolicyStatus($item), $item->getItempolicyuser(), $item->getItempolicygroup());
    }

  /**
   * Create an item or update an existing one if one exists by the uuid passed.
     Note: In the case of an already existing item, any parameters passed whose name
     begins with an underscore are assumed to be metadata fields to set on the item.
   * @path /item
   * @http POST
   * @param parentid The id of the parent folder. Only required for creating a new item.
   * @param name The name of the item to create
   * @param description (Optional) The description of the item
   * @param uuid (Optional) Uuid of the item. If none is passed, will generate one.
   * @param privacy (Optional) [Public|Private], default will inherit from parent folder
   * @param updatebitstream (Optional) If set, the bitstream's name will be updated
      simultaneously with the item's name if and only if the item has already
      existed and its latest revision contains only one bitstream.
   * @return The item object that was created
   */
  function itemCreate($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('name'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot create item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $name = $args['name'];
    $description = isset($args['description']) ? $args['description'] : '';

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';
    $record = false;
    if(!empty($uuid))
      {
      $uuidComponent = MidasLoader::loadComponent('Uuid');
      $record = $uuidComponent->getByUid($uuid);
      }
    if($record != false && $record instanceof ItemDao)
      {
      if(!$itemModel->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
        }
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        if(!$itemModel->policyCheck($record, $userDao, MIDAS_POLICY_ADMIN))
          {
          throw new Exception('Item Admin privileges required to set privacy', MIDAS_INVALID_POLICY);
          }
        $privacyCode = $apihelperComponent->getValidPrivacyCode($args['privacy']);
        $apihelperComponent->setItemPrivacy($record, $privacyCode);
        }
      foreach($args as $key => $value)
        {
        // Params beginning with underscore are assumed to be metadata fields
        if(substr($key, 0, 1) == '_')
          {
          $apihelperComponent->setMetadata($record, MIDAS_METADATA_TEXT, substr($key, 1), '', $value);
          }
        }
      if(array_key_exists('updatebitstream', $args))
        {
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
        $bitstreamModel = MidasLoader::loadModel('Bitstream');
        $revision = $itemRevisionModel->getLatestRevision($record);
        $bitstreams = $revision->getBitstreams();
        if(count($bitstreams) == 1)
          {
          $bitstream = $bitstreams[0];
          $bitstream->setName($name);
          $bitstreamModel->save($bitstream);
          }
        }
      $itemModel->save($record, true);
      return $record->toArray();
      }
    else
      {
      if(!array_key_exists('parentid', $args))
        {
        throw new Exception('Parameter parentid is not defined', MIDAS_INVALID_PARAMETER);
        }
      $folderModel = MidasLoader::loadModel('Folder');
      $folder = $folderModel->load($args['parentid']);
      if($folder == false)
        {
        throw new Exception('Parent folder doesn\'t exist', MIDAS_INVALID_PARAMETER);
        }
      if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid permissions on parent folder', MIDAS_INVALID_POLICY);
        }
      $item = $itemModel->createItem($name, $description, $folder, $uuid);
      if($item === false)
        {
        throw new Exception('Create new item failed', MIDAS_INTERNAL_ERROR);
        }
      $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
      $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);

      // set privacy if desired
      if(isset($args['privacy']))
        {
        $privacyCode = $apihelperComponent->getValidPrivacyCode($args['privacy']);
        $apihelperComponent->setItemPrivacy($item, $privacyCode);
        }

      return $item->toArray();
      }
    }

  /**
   * Move an item from the source folder to the destination folder
   * @path /item/move/{id}
   * @http PUT
   * @param id The id of the item
   * @param srcfolderid The id of source folder where the item is located
   * @param dstfolderid The id of destination folder where the item is moved to
   * @return The item object
   */
  function itemMove($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'srcfolderid', 'dstfolderid'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot move item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $item = $itemModel->load($id);
    $srcFolderId = $args['srcfolderid'];
    $srcFolder = $folderModel->load($srcFolderId);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    if($srcFolder == false || $dstFolder == false)
      {
      throw new Exception("Unable to load source or destination folder.", MIDAS_INVALID_POLICY);
      }
    if($dstFolder->getKey() != $srcFolder->getKey())
      {
      $folderModel->addItem($dstFolder, $item);
      $itemModel->copyParentPolicies($item, $dstFolder);
      $folderModel->removeItem($srcFolder, $item);
      }

    $itemArray = $item->toArray();
    $owningFolderArray = array();
    foreach($item->getFolders() as $owningFolder)
      {
      $owningFolderArray[] = $owningFolder->toArray();
      }
    $itemArray['owningfolders'] = $owningFolderArray;
    return $itemArray;
    }

  /**
   * Share an item to the destination folder
   * @path /item/share/{id}
   * @http PUT
   * @param id The id of the item
   * @param dstfolderid The id of destination folder where the item is shared to
   * @return The item object
   */
  function itemShare($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'dstfolderid'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot share item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $item = $itemModel->load($id);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $itemArray = $item->toArray();
    $owningFolderIds = array();
    $owningFolderArray = array();
    foreach($item->getFolders() as $owningFolder)
      {
      $owningFolderIds[] = $owningFolder->getKey();
      $owningFolderArray[] = $owningFolder->toArray();
      }
    if(!in_array($dstFolder->getKey(), $owningFolderIds))
      {
      // Do not update item name in item share action
      $folderModel->addItem($dstFolder, $item, false);
      $itemModel->addReadonlyPolicy($item, $dstFolder);
      $owningFolderArray[] = $dstFolder->toArray();
      }

    $itemArray['owningfolders'] = $owningFolderArray;
    return $itemArray;
    }

  /**
   * Duplicate an item to the destination folder
   * @path /item/duplicate/{id}
   * @http PUT
   * @param id The id of the item
   * @param dstfolderid The id of destination folder where the item is duplicated to
   * @return The item object that was created
   */
  function itemDuplicate($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'dstfolderid'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot duplicate item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $item = $itemModel->load($id);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $duplicatedItem = $itemModel->duplicateItem($item, $userDao, $dstFolder);

    return $duplicatedItem->toArray();
    }

  /**
   * Add an itempolicygroup to an item with the passed in group and policy;
     if an itempolicygroup exists for that group and item, it will be replaced
     with the passed in policy.
   * @path /item/addpolicygroup/{id}
   * @http PUT
   * @param id The id of the item.
   * @param group_id The id of the group.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @return success = true on success.
   */
  function itemAddPolicygroup($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'group_id', 'policy'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $itemId = $args['id'];
    $item = $itemModel->load($itemId);
    if($item === false)
      {
      throw new Exception("This item doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the item.", MIDAS_INVALID_POLICY);
      }

    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($args['group_id']);
    if($group === false)
      {
      throw new Exception("This group doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $policyCode = $apihelperComponent->getValidPolicyCode($args['policy']);

    $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');
    $itempolicygroupModel->createPolicy($group, $item, $policyCode);

    return array('success' => 'true');
    }

  /**
   * Remove a itempolicygroup from a item with the passed in group if the
     itempolicygroup exists.
   * @path /item/removepolicygroup/{id}
   * @http PUT
   * @param id The id of the item.
   * @param group_id The id of the group.
   * @return success = true on success.
   */
  function itemRemovePolicygroup($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'group_id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $itemId = $args['id'];
    $item = $itemModel->load($itemId);
    if($item === false)
      {
      throw new Exception("This item doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the item.", MIDAS_INVALID_POLICY);
      }

    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($args['group_id']);
    if($group === false)
      {
      throw new Exception("This group doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');
    $itempolicygroup = $itempolicygroupModel->getPolicy($group, $item);
    if($itempolicygroup !== false)
      {
      $itempolicygroupModel->delete($itempolicygroup);
      }

    return array('success' => 'true');
    }

  /**
   * Add a itempolicyuser to an item with the passed in user and policy;
     if an itempolicyuser exists for that user and item, it will be replaced
     with the passed in policy.
   * @path /item/addpolicyuser/{id}
   * @http PUT
   * @param id The id of the item.
   * @param user_id The id of the targeted user to create the policy for.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @return success = true on success.
   */
  function itemAddPolicyuser($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'user_id', 'policy'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $adminUser = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $itemId = $args['id'];
    $item = $itemModel->load($itemId);
    if($item === false)
      {
      throw new Exception("This item doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$itemModel->policyCheck($item, $adminUser, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the item.", MIDAS_INVALID_POLICY);
      }

    $userModel = MidasLoader::loadModel('User');
    $targetUserId = $args['user_id'];
    $targetUser = $userModel->load($targetUserId);
    if($targetUser === false)
      {
      throw new Exception("This user doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $policyCode = $apihelperComponent->getValidPolicyCode($args['policy']);

    $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
    $itempolicyuserModel->createPolicy($targetUser, $item, $policyCode);

    return array('success' => 'true');
    }

  /**
   * Remove an itempolicyuser from an item with the passed in user if the
     itempolicyuser exists.
   * @path /item/removepolicyuser/{id}
   * @http PUT
   * @param id The id of the item.
   * @param user_id The id of the target user.
   * @return success = true on success.
   */
  function itemRemovePolicyuser($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'user_id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $itemId = $args['id'];
    $item = $itemModel->load($itemId);
    if($item === false)
      {
      throw new Exception("This item doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the item.", MIDAS_INVALID_POLICY);
      }

    $userModel = MidasLoader::loadModel('User');
    $user = $userModel->load($args['user_id']);
    if($user === false)
      {
      throw new Exception("This user doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
    $itempolicyuser = $itempolicyuserModel->getPolicy($user, $item);
    if($itempolicyuser !== false)
      {
      $itempolicyuserModel->delete($itempolicyuser);
      }

    return array('success' => 'true');
    }

  /**
   * Delete an item
   * @path /item/{id}
   * @http DELETE
   * @param id The id of the item
   */
  function itemDelete($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));

    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', MIDAS_INVALID_TOKEN);
      }
    $id = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($id);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $itemModel->delete($item);
    }

  /**
   * Download an item
   * @path /item/download/{id}
   * @http GET
   * @param id The id of the item
   * @param revision (Optional) Revision to download. Defaults to latest revision
   * @return The bitstream(s) in the item
   */
  function itemDownload($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $id = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($id);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $redirUrl = '/download/?items='.$item->getKey();
    if(isset($args['revision']))
      {
      $redirUrl .= ','.$args['revision'];
      }
    if($userDao && array_key_exists('token', $args))
      {
      $redirUrl .= '&authToken='.$args['token'];
      }
    $r = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
    $r->gotoUrl($redirUrl);
    }
  } // end class
