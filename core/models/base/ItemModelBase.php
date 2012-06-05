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

/** ItemModelBase */
abstract class ItemModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'item';
    $this->_key = 'item_id';

    $this->_mainData = array(
      'item_id' => array('type' => MIDAS_DATA),
      'name' => array('type' => MIDAS_DATA),
      'description' => array('type' => MIDAS_DATA),
      'type' =>  array('type' => MIDAS_DATA),
      'sizebytes' => array('type' => MIDAS_DATA),
      'date_creation' => array('type' => MIDAS_DATA),
      'date_update' => array('type' => MIDAS_DATA),
      'thumbnail_id' => array('type' => MIDAS_DATA),
      'view' => array('type' => MIDAS_DATA),
      'download' => array('type' => MIDAS_DATA),
      'privacy_status' => array('type' => MIDAS_DATA),
      'uuid' => array('type' => MIDAS_DATA),
      'folders' => array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Folder', 'table' => 'item2folder', 'parent_column' => 'item_id', 'child_column' => 'folder_id'),
      'revisions' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'ItemRevision', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      'itempolicygroup' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Itempolicygroup', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      'itempolicyuser' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Itempolicyuser', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  abstract function getOwnedByUser($userDao, $limit = 20);
  abstract function getSharedToUser($userDao, $limit = 20);
  abstract function getSharedToCommunity($communityDao, $limit = 20);
  abstract function policyCheck($itemdao, $userDao = null, $policy = 0);
  abstract function getLastRevision($itemdao);
  abstract function getMostPopulars($userDao, $limit = 20);
  abstract function getRandomThumbnails($userDao = null, $policy = 0, $limit = 10, $thumbnailFilter = false);
  abstract function getByUuid($uuid);
  abstract function getAll();
  abstract function getItemsFromSearch($searchterm, $userDao, $limit = 14, $group = true, $order = 'view');
  abstract function getByName($name);
  abstract function iterateWithCallback($callback, $paramName = 'item');

  /** delete an item */
  public function delete($dao)
    {
    if(!$dao instanceof ItemDao)
      {
      throw new Zend_Exception('You must pass an item dao to ItemModel::delete');
      }
    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_ITEM_DELETED', array('item' => $dao));
    parent::delete($dao);
    }// delete

  /** save */
  public function save($dao, $updateSearchIndex = true)
    {
    if(!isset($dao->uuid) || empty($dao->uuid))
      {
      $dao->setUuid(uniqid() . md5(mt_rand()));
      }
    if(!isset($dao->date_creation) || empty($dao->date_creation))
      {
      $dao->setDateCreation(date('c'));
      }
    if(!isset($dao->type) || empty($dao->type))
      {
      $dao->setType(0);
      }
    $dao->setDateUpdate(date('c'));
    $dao->setDescription(UtilityComponent::filterHtmlTags($dao->getDescription()));
    parent::save($dao);

    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_ITEM_SAVED', array('item' => $dao));
    }

  /** copy parent folder policies*/
  function copyParentPolicies($itemdao, $folderdao, $feeddao = null)
    {
    if(!$itemdao instanceof ItemDao || !$folderdao instanceof FolderDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $groupPolicies = $folderdao->getFolderpolicygroup();
    $userPolicies = $folderdao->getFolderpolicyuser();

    $modelLoad = new MIDAS_ModelLoader();
    $ItempolicygroupModel = $modelLoad->loadModel('Itempolicygroup');
    foreach($groupPolicies as $key => $policy)
      {
      $ItempolicygroupModel->createPolicy($policy->getGroup(), $itemdao, $policy->getPolicy());
      }
    $ItempolicyuserModel = $modelLoad->loadModel('Itempolicyuser');
    foreach($userPolicies as $key => $policy)
      {
      $ItempolicyuserModel->createPolicy($policy->getUser(), $itemdao, $policy->getPolicy());
      }

    if($feeddao != null && $feeddao instanceof FeedDao)
      {
      $FeedpolicygroupModel = $modelLoad->loadModel('Feedpolicygroup');
      foreach($groupPolicies as $key => $policy)
        {
        $FeedpolicygroupModel->createPolicy($policy->getGroup(), $feeddao, $policy->getPolicy());
        }
      $FeedpolicyuserModel = $modelLoad->loadModel('Feedpolicyuser');
      foreach($userPolicies as $key => $policy)
        {
        $FeedpolicyuserModel->createPolicy($policy->getUser(), $feeddao, $policy->getPolicy());
        }
      }
    }//end copyParentPolicies

  /** Grant read only permission for an item in the target folder
   *
   * share an item to destination folder (grant read-only permission to users/groups who can
   * access the destination folder )
   * @method addReadonlyPolicy()
   * @param ItemDao $itemDao the item to be shared
   * @param FolderDao $folderDao destination folder
   * @throws Zend_Exception on invalid input parameters (itemDao and folderDao)
  */
  function addReadonlyPolicy($itemdao, $folderdao)
    {
    if(!$itemdao instanceof ItemDao || !$folderdao instanceof FolderDao)
      {
      throw new Zend_Exception("Error input parameter.");
      }
    $groupPolicies = $folderdao->getFolderpolicygroup();
    $existingGroupPolicies = $itemdao->getItempolicygroup();
    $existingGroups = array();
    foreach($existingGroupPolicies as $key => $policy)
      {
      $group = $policy->getGroup();
      if(in_array($group, $existingGroups))
        {
        array_push($existingGroups, $group);
        }
      }

    $modelLoad = new MIDAS_ModelLoader();
    $ItempolicygroupModel = $modelLoad->loadModel('Itempolicygroup');
    foreach($groupPolicies as $key => $policy)
      {
      $newGroup = $policy->getGroup();
      if(!in_array($group, $existingGroups))
        {
        $ItempolicygroupModel->createPolicy($newGroup, $itemdao, MIDAS_POLICY_READ);
        }
      }
    }//end addReadonlyPolicy


  /**
   * Duplicate an item in destination folder/community
   *
   * Create a new item (same as old one) in destination folder/community. The new item
   * have the same metadata and revisions with the old one, but its owner is set as the
   * input userDao parameter (who run this operation) and access policy is based on
   * the input folderDao paramer (destination folder)
   *
   * @method duplicateItem()
   * @param ItemDao $itemDao the item to be duplicated
   * @param UserDao $userDao the user who run this operation
   * @param FolderDao $folderDao destination folder
   * @throws Zend_Exception on invalid input parameters (itemDao, userDao and folderDao)
  */
  function duplicateItem($itemDao, $userDao, $folderDao)
    {
    if(!$itemDao instanceof ItemDao || !$folderDao instanceof FolderDao)
      {
      throw new Zend_Exception("Error ItemDao or FolderDao");
      }
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    $modelLoad = new MIDAS_ModelLoader();
    $ItemRevisionModel = $modelLoad->loadModel('ItemRevision');
    $BitstreamModel = $modelLoad->loadModel('Bitstream');
    $MetadataModel = $modelLoad->loadModel('Metadata');
    $ItemPolicyGroupModel = $modelLoad->loadModel('Itempolicygroup');

    $name = $itemDao->getName();
    $description = $itemDao->getDescription();
    $newItem = $this->createItem($name, $description, $folderDao);
    $newItem->setType($itemDao->getType());
    $newItem->setSizebytes($itemDao->getSizebytes());
    $newItem->setPrivacyStatus($itemDao->getPrivacyStatus());
    $newItem->setDateCreation(date('c'));
    $newItem->setDateUpdate(date('c'));

    $thumbnailId = $itemDao->getThumbnailId();
    if($thumbnailId !== null)
      {
      $oldThumb = $BitstreamModel->load($thumbnailId);
      $newThumb = new BitstreamDao;
      $newThumb->setItemrevisionId(-1);
      $newThumb->setName($oldThumb->getName());
      $newThumb->setMimetype($oldThumb->getMimetype());
      $newThumb->setSizebytes($oldThumb->getSizebytes());
      $newThumb->setChecksum($oldThumb->getChecksum());
      $newThumb->setPath($oldThumb->getPath());
      $newThumb->setAssetstoreId($oldThumb->getAssetstoreId());
      $newThumb->setDate($oldThumb->getDate());
      $BitstreamModel->save($newThumb);
      $newItem->setThumbnailId($newThumb->getKey());
      }

    $this->save($newItem);

    $ItemPolicyGroupModel->computePolicyStatus($newItem);

    foreach($itemDao->getRevisions() as $revision)
      {
      $dupItemRevision = new ItemRevisionDao;
      $dupItemRevision->setItemId($newItem->getItemId());
      $dupItemRevision->setRevision($revision->getRevision());
      $dupItemRevision->setDate($revision->getDate());
      $dupItemRevision->setChanges($revision->getChanges());
      $dupItemRevision->setUserId($userDao->getUserId());
      $dupItemRevision->setLicenseId($revision->getLicenseId());
      $ItemRevisionModel->save($dupItemRevision);
      // duplicate metadata value
      $metadatavalues = array();
      $metadatavalues = $ItemRevisionModel->getMetadata($revision);
      foreach($metadatavalues as $metadata)
        {
        $MetadataModel->addMetadataValue($dupItemRevision,
                                        $metadata->getMetadatatype(),
                                        $metadata->getElement(),
                                        $metadata->getQualifier(),
                                        $metadata->getValue());
        }
      // duplicate bitstream
      foreach($revision->getBitstreams() as $bitstream)
        {
        $dupBitstream = new BitstreamDao;
        $dupBitstream->setItemrevisionId($dupItemRevision->getItemrevisionId());
        $dupBitstream->setName($bitstream->getName());
        $dupBitstream->setMimetype($bitstream->getMimetype());
        $dupBitstream->setSizebytes($bitstream->getSizebytes());
        $dupBitstream->setChecksum($bitstream->getChecksum());
        $dupBitstream->setPath($bitstream->getPath());
        $dupBitstream->setAssetstoreId($bitstream->getAssetstoreId());
        $dupBitstream->setDate($bitstream->getDate());
        $BitstreamModel->save($dupBitstream);
        }
      }
    return $newItem;
    }//end duplicateItem

  /** plus one view*/
  function incrementViewCount($itemdao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $user = Zend_Registry::get('userSession');
    if(isset($user))
      {
      if(isset($user->viewedItems[$itemdao->getKey()]))
        {
        return;
        }
      else
        {
        $user->viewedItems[$itemdao->getKey()] = true;
        }
      }
    $itemdao->view++;
    parent::save($itemdao);
    }//end incrementViewCount

  /** plus one download*/
  function incrementDownloadCount($itemdao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $itemdao->download++;
    parent::save($itemdao);
    Zend_Registry::get('notifier')->callback("CALLBACK_CORE_PLUS_ONE_DOWNLOAD", array('item' => $itemdao));
    }//end incrementDownloadCount

  /** Add a revision to an item
   * @return void*/
  function addRevision($itemdao, $revisiondao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("First argument should be an item" );
      }
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Second argument should be an item revision" );
      }

    $modelLoad = new MIDAS_ModelLoader();
    $ItemRevisionModel = $modelLoad->loadModel('ItemRevision');

    // Should check the latest revision for this item
    $latestrevision = $ItemRevisionModel->getLatestRevision($itemdao);
    if(!$latestrevision) // no revision yet we assigne the value 1
      {
      $revisiondao->setRevision(1);
      }
    else
      {
      $revisiondao->setRevision($latestrevision->getRevision() + 1);
      }
    $revisiondao->setItemId($itemdao->getItemId());
    $ItemRevisionModel->save($revisiondao);
    $this->save($itemdao, false);//update date
    } // end addRevision

  /**
   * Delete an itemrevision from an item, will reduce all other
   * itemrevision revision numbers appropriately.
   *
   * @return void
   */
  function removeRevision($itemdao, $revisiondao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("First argument should be an item" );
      }
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Second argument should be an item revision" );
      }
    if($revisiondao->getItemId() != $itemdao->getItemId())
      {
      throw new Zend_Exception("Revision needs to be associated with Item");
      }

    $modelLoad = new MIDAS_ModelLoader();
    $itemRevisionModel = $modelLoad->loadModel('ItemRevision');

    $lastRevisionDao = $this->getLastRevision($itemdao);
    $numRevisions = $lastRevisionDao->getRevision();
    $deletedRevisionNum = $revisiondao->getRevision();
    $itemRevisionModel->delete($revisiondao);

    // compact the revision numbers if necessary
    if($deletedRevisionNum < $numRevisions)
      {
      // reach past the deleted revision, reduce each revision number by 1
      $revisions = range($deletedRevisionNum + 1, $numRevisions);
      foreach($revisions as $revision)
        {
        $itemRevisionDao = $this->getRevision($itemdao, $revision);
        $itemRevisionDao->setRevision($itemRevisionDao->getRevision() - 1);
        $itemRevisionModel->save($itemRevisionDao);
        }
      }

    // reload the item and last revision now that we have updated revisions
    $itemId = $itemdao->getItemId();
    $itemdao = $this->load($itemId);
    $lastRevisionDao = $this->getLastRevision($itemdao);

    // now that we have deleted a revision, recalculate size of item
    if(empty($lastRevisionDao))
      {
      $itemdao->setSizebytes(0);
      }
    else
      {
      $itemdao->setSizebytes($itemRevisionModel->getSize($lastRevisionDao));
      }
    $this->save($itemdao);
    }//end removeRevision







  /**
   * Update Item name to avoid two or more items have same name within their parent folder.
   *
   * Check if an item with the same name already exists in the parent folder. If it exists, add appendix to the original file name.
   * The following naming convention is used:
   * Assumption: if an item's name is like "aaa.txt (1)", the name should not be this item's real name, but its modified name in Midas when it is created.
   * This item's real name should be 'aaa.txt' which doesn't have / \(d+\)/ like appendix .
   * So when an item named "aaa.txt (1)" is duplicated, the newly created item will be called "aaa.txt (2)" instead of "aaa.txt (1) (1)"
   *
   * @method updateItemName()
   * @param string $name name of the item
   * @param FolderDao $parent parent folder of the item
   * @return string $updatedName new unique(within its parent folder) name assigned to the item
   */
  function updateItemName($name, $parent)
    {
    if(!$parent instanceof FolderDao && !is_numeric($parent))
      {
      throw new Zend_Exception('Parent should be a folder.');
      }

    if(empty($name) && $name !== '0')
      {
      throw new Zend_Exception('Name cannot be empty.');
      }

    if($parent instanceof FolderDao)
      {
      $parentId = $parent->getFolderId();
      }
    else
      {
      $parentId = $parent;
      $parent = $this->load($parentId);
      }

    $names = preg_split('/ \(\d+\)/', $name);
    $realName = $names[0];
    $escapedRealName = addcslashes($realName, '()');
    $siblings = $parent->getItems();
    $copyIndex = 0;
    foreach($siblings as $sibling)
      {
      $siblingName = $sibling->getName();
      if(!strcmp($siblingName, $realName) && ($copyIndex == 0))
        {
        $copyIndex = 1;
        }
      else if(preg_match('/^'.$escapedRealName.'( \(\d+\))$/', $siblingName))
        {
        // get copy index number from the item's name. e.g. get 1 from "aaa.txt (1)"
        $currentCopy = intval(substr(strrchr($siblingName, "("), 1, -1));
        if($currentCopy >= $copyIndex)
          {
          $copyIndex = $currentCopy + 1;
          }
        }
      }

    $updatedName = $realName;
    if($copyIndex > 0)
      {
      $updatedName = $realName.' ('.$copyIndex.')';
      }
    return $updatedName;


    }

  /** Create a new empty item */
  function createItem($name, $description, $parent, $uuid = '')
    {
    if(!$parent instanceof FolderDao && !is_numeric($parent))
      {
      throw new Zend_Exception('Parent should be a folder.');
      }

    if(!is_string($name))
      {
      throw new Zend_Exception('Name should be a string.');
      }

    if(empty($name) && $name !== '0')
      {
      throw new Zend_Exception('Name cannot be empty.');
      }

    if($parent instanceof FolderDao)
      {
      $parentId = $parent->getFolderId();
      }
    else
      {
      $parentId = $parent;
      $parent = $this->load($parentId);
      }

    $this->loadDaoClass('ItemDao');
    $item = new ItemDao();
    $item->setName($this->updateItemName($name, $parent));
    $item->setDescription($description);
    $item->setType(0);
    $item->setUuid($uuid);
    $this->save($item);

    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');
    $folderModel->addItem($parent, $item);
    $this->copyParentPolicies($item, $parent);
    return $item;
    }

  /**
   * Count the bitstreams under this item's head revision.
   * Returns array('size'=>size_in_bytes, 'count'=>total_number_of_bitstreams)
   */
  function countBitstreams($itemDao)
    {
    $totalSize = 0;
    $totalCount = 0;
    $rev = $this->getLastRevision($itemDao);

    if($rev)
      {
      $bitstreams = $rev->getBitstreams();
      foreach($bitstreams as $bitstream)
        {
        $totalCount++;
        $totalSize += $bitstream->getSizebytes();
        }
      }

    return array('size' => $totalSize, 'count' => $totalCount);
    } //end countBitstreams

  /**
   * Merge the items in the specified array into one item.
   */
  function mergeItems($itemIds, $name, $userSessionDao)
    {
    $items = array();
    foreach($itemIds as $item)
      {
      $itemDao = $this->load($item);
      if($item != false && $this->policyCheck($itemDao, $userSessionDao,
                                                MIDAS_POLICY_ADMIN))
        {
        if($itemDao)
          {
          $items[] = $itemDao;
          }
        else
          {
          $this->getLogger()->info(__METHOD__ . " User unable to merge item ".
                                   $itemDao->getKey() . " due to insufficient ".
                                   "permissions.");
          }
        }
      else
        {
        $this->getLogger()->info(__METHOD__ . " User unable to merge item ".
                                 $item . " because it does not exist.");
        }
      }

    if(empty($items))
      {
      throw new Zend_Exception('Insufficient permissions to merge these '.
                               'items.');
      }

    $mainItem = $items[0];
    $mainItemLastResision = $this->getLastRevision($mainItem);
    $modelLoad = new MIDAS_ModelLoader();
    $bitstreamModel = $modelLoad->loadModel('Bitstream');
    $revisionModel = $modelLoad->loadModel('ItemRevision');
    foreach($items as $key => $item)
      {
      if($key != 0)
        {
        $revision = $this->getLastRevision($item);
        $bitstreams = $revision->getBitstreams();
        foreach($bitstreams as $b)
          {
          $b->setItemrevisionId($mainItemLastResision->getKey());
          $bitstreamModel->save($b);
          }
        $this->delete($item);
        }
      }

    $mainItem->setSizebytes($revisionModel->getSize($mainItemLastResision));
    $mainItem->setName($name);
    $this->save($mainItem);
    Zend_Registry::get('notifier')->callback('CALLBACK_CORE_ITEM_MERGED',
                                             array('item' => $mainItem,
                                                   'itemIds' => $itemIds));
    return $mainItem;
    }

  /**
   * Delete the existing thumbnail on an item if there is one, and replace
   * it with the one at the provided temp path.  The temp file will be
   * moved into the assetstore.
   */
  public function replaceThumbnail($item, $tempThumbnailFile)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $assetstoreModel = $modelLoad->loadModel('Assetstore');
    $bitstreamModel = $modelLoad->loadModel('Bitstream');

    // Remove the existing thumbnail bitstream
    if($item->getThumbnailId() !== null)
      {
      $oldThumb = $bitstreamModel->load($item->getThumbnailId());
      $bitstreamModel->delete($oldThumb);
      }

    $thumb = $bitstreamModel->createThumbnail($assetstoreModel->getDefault(), $tempThumbnailFile);
    $item->setThumbnailId($thumb->getKey());
    $this->save($item);
    }
} // end class ItemModelBase
