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

require_once BASE_PATH.'/core/models/base/ItemModelBase.php';

/**
 * \class ItemModel
 * \brief Pdo Model
 */
class ItemModel extends ItemModelBase
{

  /** Get the keyword from the search.
   * @return Array of ItemDao */
  function getItemsFromSearch($searchterm, $userDao, $limit = 14, $group = true, $order = 'view')
    {
    if(Zend_Registry::get('configDatabase')->database->adapter == 'PDO_PGSQL')
      {
      $group = false; //Postgresql don't like the sql request with group by
      }
    $isAdmin = false;
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      if($userDao->isAdmin())
        {
        $isAdmin = true;
        }
      }

    require_once BASE_PATH.'/core/controllers/components/SearchComponent.php';
    $component = new SearchComponent();
    $index = $component->getLuceneItemIndex();
    Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
    Zend_Search_Lucene::setResultSetLimit($limit * 3);
    if($group && strpos($searchterm, ':') === false)
      {
      $rowset = $index->find('title:'.$searchterm);
      }
    else
      {
      $rowset = $index->find($searchterm);
      }


    $return = array();
    $itemIdsCount = array();
    $itemIds = array();
    foreach($rowset as $row)
      {
      if(isset($itemIdsCount[$row->item_id]))
        {
        $itemIdsCount[$row->item_id]++;
        }
      else
        {
        $itemIdsCount[$row->item_id] = 1;
        }
      }
    foreach($itemIdsCount as $key => $n)
      {
      $itemIds[] = $key;
      }

    if(empty($itemIds))
      {
      return array();
      }
    $sql = $this->database->select();
    if($group)
      {
      $sql->from(array('i' => 'item'), array('item_id', 'name', 'count(*)'));
      }
    else
      {
      $sql->from(array('i' => 'item'));
      }

    if(!$isAdmin)
      {
      $sql ->joinLeft(array('ipu' => 'itempolicyuser'), '
                    i.item_id = ipu.item_id AND '.$this->database->getDB()->quoteInto('ipu.policy >= ?', MIDAS_POLICY_READ).'
                       AND '.$this->database->getDB()->quoteInto('ipu.user_id = ? ', $userId).' ', array())
          ->joinLeft(array('ipg' => 'itempolicygroup'), '
                         i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto('ipg.policy >= ?', MIDAS_POLICY_READ).'
                             AND ( '.$this->database->getDB()->quoteInto('ipg.group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                  ipg.group_id IN (' .new Zend_Db_Expr(
                                  $this->database->select()
                                       ->setIntegrityCheck(false)
                                       ->from(array('u2g' => 'user2group'),
                                              array('group_id'))
                                       ->where('u2g.user_id = ?', $userId)
                                       ) .'))', array())
          ->where(
           '(
            ipu.item_id is not null or
            ipg.item_id is not null)'
            );
      }
    $sql->setIntegrityCheck(false)
          ->where('i.item_id IN   (?)', $itemIds)
          ->limit($limit);

    if($group)
      {
      $sql->group('i.name');
      }

    switch($order)
      {
      case 'name':
        $sql->order(array('i.name ASC'));
        break;
      case 'date':
        $sql->order(array('i.date_update ASC'));
        break;
      case 'view':
      default:
        $sql->order(array('i.view DESC'));
        break;
      }
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Item', $row);
      if(isset($row['count(*)']))
        {
        $tmpDao->count = $row['count(*)'];
        }
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getItemsFromSearch()

  /** get All*/
  function getAll()
    {
    $rowset = $this->database->fetchAll($this->database->select()->order(array('item_id DESC')));
    $results = array();
    foreach($rowset as $row)
      {
      $results[] = $this->initDao('Item', $row);
      }
    return $results;
    }

  /** get by uuid*/
  function getByUuid($uuid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    }
  /**
   * Get Items where user policy exists and is != admin
   * @param type $userDao
   * @param type $limit
   * @return Array
   */
  function getSharedToCommunity($communityDao, $limit = 20)
    {
    $groupId = $communityDao->getMemberGroup()->getKey();
    if(!is_numeric($groupId))
      {
      throw new Zend_Exception('Error parameter');
      }
    $sql = $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('i' => 'item'))
                  ->join(array('p' => 'itempolicygroup'),
                        'i.item_id = p.item_id', array('p.policy', 'policy_date' => 'p.date'))
                  ->where('group_id = ? ', $groupId)
                  ->order(array('p.date DESC'))
                  ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $row)
      {
      $tmp = $this->initDao('Item', $row);
      $tmp->policy = $row['policy'];
      $tmp->policy_date = $row['policy_date'];
      $results[] = $tmp;
      }
    return $results;
    }//end getSharedToCommunity

  /**
   * Get most popular items
   * @param type $limit
   * @return Array
   */
  function getMostPopulars($userDao, $limit = 20)
    {
    $sql = $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('i' => 'item'))
                  ->where('privacy_status = ?', MIDAS_PRIVACY_PUBLIC)
                  ->where('download != ?', 0)
                  ->where('view != ?', 0)
                  ->order(array('i.view DESC'))
                  ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $row)
      {
      $tmp = $this->initDao('Item', $row);
      $results[] = $tmp;
      }
    return $results;
    }


  /**
   * Get Items where user policy = Admin
   * @param type $userDao
   * @param type $limit
   * @return Array
   */
  function getOwnedByUser($userDao, $limit = 20)
    {
    $userId = $userDao->getKey();
    if(!is_numeric($userId))
      {
      throw new Zend_Exception('Error parameter');
      }
    $sql = $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('i' => 'item'))
                  ->join(array('p' => 'itempolicyuser'),
                        'i.item_id = p.item_id', array('p.policy', 'policy_date' => 'p.date'))
                  ->where('policy = ?', MIDAS_POLICY_ADMIN)
                  ->where('user_id = ? ', $userId)
                  ->order(array('p.date DESC'))
                  ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $row)
      {
      $tmp = $this->initDao('Item', $row);
      $tmp->policy = $row['policy'];
      $tmp->policy_date = $row['policy_date'];
      $results[] = $tmp;
      }
    return $results;
    }//end getOwnedByUser

  /**
   * Get Items where user policy exists and is != admin
   * @param type $userDao
   * @param type $limit
   * @return Array
   */
  function getSharedToUser($userDao, $limit = 20)
    {
    $userId = $userDao->getKey();
    if(!is_numeric($userId))
      {
      throw new Zend_Exception('Error parameter');
      }
    $sql = $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('i' => 'item'))
                  ->join(array('p' => 'itempolicyuser'),
                        'i.item_id = p.item_id', array('p.policy', 'policy_date' => 'p.date'))
                  ->where('policy != ?', MIDAS_POLICY_ADMIN)
                  ->where('user_id = ? ', $userId)
                  ->order(array('p.date DESC'))
                  ->limit($limit);
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $row)
      {
      $tmp = $this->initDao('Item', $row);
      $tmp->policy = $row['policy'];
      $tmp->policy_date = $row['policy_date'];
      $results[] = $tmp;
      }
    return $results;
    }//end getSharedToUser

  /** Delete an item */
  function delete($itemdao)
    {
    if(!$itemdao instanceof  ItemDao)
      {
      throw new Zend_Exception("Error param.");
      }

    $deleteType = array(MIDAS_FEED_CREATE_ITEM, MIDAS_FEED_CREATE_LINK_ITEM);
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feed'))
                          ->where('ressource = ?', $itemdao->getKey());

    $rowset = $this->database->fetchAll($sql);
    $this->ModelLoader = new MIDAS_ModelLoader();
    $feed_model = $this->ModelLoader->loadModel('Feed');
    $revision_model = $this->ModelLoader->loadModel('ItemRevision');
    foreach($rowset as $row)
      {
      $feed = $this->initDao('Feed', $row);
      if(in_array($feed->getType(), $deleteType))
        {
        $feed_model->delete($feed);
        }
      }

    $folder_model = $this->ModelLoader->loadModel('Folder');
    $folders = $itemdao->getFolders();
    foreach($folders as $folder)
      {
      $folder_model->removeItem($folder, $itemdao);
      }

    $revisions = $itemdao->getRevisions();
    foreach($revisions as $revision)
      {
      $revision_model->delete($revision);
      }

    $policy_group_model = $this->ModelLoader->loadModel('Itempolicygroup');
    $policiesGroup = $itemdao->getItempolicygroup();
    foreach($policiesGroup as $policy)
      {
      $policy_group_model->delete($policy);
      }

    $policy_user_model = $this->ModelLoader->loadModel('Itempolicyuser');
    $policiesUser = $itemdao->getItempolicyuser();
    foreach($policiesUser as $policy)
      {
      $policy_user_model->delete($policy);
      }


    require_once BASE_PATH.'/core/controllers/components/SearchComponent.php';
    $component = new SearchComponent();
    $index = $component->getLuceneItemIndex();

    $hits = $index->find("item_id:".$itemdao->getKey());
    foreach($hits as $hit)
      {
      $index->delete($hit->id);
      }

    parent::delete($itemdao);
    unset($itemdao->item_id);
    $itemdao->saved = false;
    }//end delete

  /** check if the policy is valid*/
  function policyCheck($itemdao, $userDao = null, $policy = 0)
    {
    if(!$itemdao instanceof  ItemDao || !is_numeric($policy))
      {
      throw new Zend_Exception("Error param.");
      }
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      if($userDao->isAdmin())
        {
        return true;
        }
      }

    $subqueryUser = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'itempolicyuser'),
                                 array('item_id'))
                          ->where('policy >= ?', $policy)
                          ->where('p.item_id = ?', $itemdao->getKey())
                          ->where('user_id = ? ', $userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'itempolicygroup'),
                           array('item_id'))
                    ->where('policy >= ?', $policy)
                    ->where('p.item_id = ?', $itemdao->getKey())
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'), array('group_id'))
                                   ->where('u2g.user_id = ?', $userId)
                                   .'))' ));

    $sql = $this->database->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $row = $this->database->fetchRow($sql);
    if($row == null)
      {
      return false;
      }
    return true;
    }//end policyCheck

  /** get random items
   *
   * @param UserDao $userDao
   * @param type $policy
   * @param type $limit
   * @return array of ItemDao
   */
  function getRandomThumbnails($userDao = null, $policy = 0, $limit = 10, $thumbnailFilter = false)
    {
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    if(Zend_Registry::get('configDatabase')->database->adapter == 'PDO_MYSQL')
      {
      $rand = 'RAND()';
      }
    else
      {
      $rand = 'random()';
      }
    $sql = $this->database->select()
        ->setIntegrityCheck(false)
        ->from(array('i' => 'item'))
        ->join(array('tt' => $this->database->select()
                    ->from(array('i' => 'item'), array('maxid' => 'MAX(item_id)'))),
                      ' i.item_id >= FLOOR(tt.maxid*'.$rand.')')
        ->joinLeft(array('ip' => 'itempolicyuser'), '
                  i.item_id = ip.item_id AND '.$this->database->getDB()->quoteInto('ip.policy >= ?', $policy).'
                     AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ', array('userpolicy' => 'ip.policy'))
        ->joinLeft(array('ipg' => 'itempolicygroup'), '
                        i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto('ipg.policy >= ?', $policy).'
                           AND ( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                group_id IN (' .new Zend_Db_Expr(
                                $this->database->select()
                                     ->setIntegrityCheck(false)
                                     ->from(array('u2g' => 'user2group'), array('group_id'))
                                     ->where('u2g.user_id = ?', $userId)
                                     ) .'))', array('grouppolicy' => 'ipg.policy'))
        ->where(
         '(
          ip.item_id is not null or
          ipg.item_id is not null)'
          )
        ->limit($limit);
    if($thumbnailFilter)
      {
      $sql->where('thumbnail != ?', '');
      }

    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      if($row['userpolicy'] == null)
        {
        $row['userpolicy'] = 0;
        }
      if($row['grouppolicy'] == null)
        {
        $row['grouppolicy'] = 0;
        }
      if(!isset($rowsetAnalysed[$row['item_id']]) || ($rowsetAnalysed[$row['item_id']]->policy
        < $row['userpolicy'] && $rowsetAnalysed[$row['item_id']]->policy < $row['grouppolicy']))
        {
        $tmpDao = $this->initDao('Item', $row);
        if($row['userpolicy'] >= $row['grouppolicy'])
          {
          $tmpDao->policy = $row['userpolicy'];
          }
        else
          {
          $tmpDao->policy = $row['grouppolicy'];
          }
        $rowsetAnalysed[$row['item_id']] = $tmpDao;
        unset($tmpDao);
        }
      }
    return $rowsetAnalysed;
    }//end get random

  /** Get the last revision
   * @return ItemRevisionDao*/
  function getLastRevision($itemdao)
    {
    if(!$itemdao instanceof  ItemDao || !$itemdao->saved)
      {
      throw new Zend_Exception("Error param.");
      }
    return $this->initDao('ItemRevision', $this->database->fetchRow($this->database->select()->from('itemrevision')
                                              ->where('item_id = ?', $itemdao->getItemId())
                                              ->order(array('revision DESC'))
                                              ->limit(1)
                                              ->setIntegrityCheck(false)
                                              ));
    }

  /** Get  revision
   * @return ItemRevisionDao*/
  function getRevision($itemdao, $number)
    {
    if(!$itemdao instanceof  ItemDao || !$itemdao->saved)
      {
      throw new Zend_Exception("Error param.");
      }
    return $this->initDao('ItemRevision', $this->database->fetchRow($this->database->select()->from('itemrevision')
                                              ->where('item_id = ?', $itemdao->getItemId())
                                              ->where('revision = ?', $number)
                                              ->limit(1)
                                              ->setIntegrityCheck(false)
                                              ));
    }
}  // end class
?>
