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
      'item_id' =>  array('type' => MIDAS_DATA),
      'name' =>  array('type' => MIDAS_DATA),
      'description' =>  array('type' => MIDAS_DATA),
      'type' =>  array('type' => MIDAS_DATA),
      'sizebytes' => array('type' => MIDAS_DATA),
      'date' => array('type' => MIDAS_DATA),
      'thumbnail' => array('type' => MIDAS_DATA),
      'view' => array('type' => MIDAS_DATA),
      'download' => array('type' => MIDAS_DATA),
      'privacy_status' => array('type' => MIDAS_DATA),
      'uuid' => array('type' => MIDAS_DATA),
      'folders' =>  array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Folder', 'table' => 'item2folder', 'parent_column' => 'item_id', 'child_column' => 'folder_id'),
      'revisions' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'ItemRevision', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      'keywords' => array('type' => MIDAS_MANY_TO_MANY, 'model' => 'ItemKeyword', 'table' => 'item2keyword', 'parent_column' => 'item_id', 'child_column' => 'keyword_id'),
      'itempolicygroup' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Itempolicygroup', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      'itempolicyuser' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Itempolicyuser', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      );
    $this->initialize(); // required
    } // end __construct()  
  
  abstract function getOwnedByUser($userDao, $limit = 20);
  abstract function getSharedToUser($userDao, $limit = 20);
  abstract function getSharedToCommunity($communityDao, $limit = 20);
  abstract function policyCheck($itemdao, $userDao = null, $policy = 0);
  abstract function getLastRevision($itemdao);
  abstract function getMostPopulars($userDao, $limit=20);
  abstract function getRandomThumbnails($userDao = null, $policy = 0, $limit = 10, $thumbnailFilter = false);
  
  /** save */
  public function save($dao)
    {
    if(!isset($dao->uuid) || empty($dao->uuid))
      {
      $dao->setUuid(uniqid() . md5(mt_rand()));
      }
    parent::save($dao);
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
    $this->save($itemdao);
    }//end incrementViewCount
    
  /** plus one download*/
  function incrementDownloadCount($itemdao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $itemdao->download++;
    $this->save($itemdao);
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
    } // end addRevision
  
} // end class ItemModelBase