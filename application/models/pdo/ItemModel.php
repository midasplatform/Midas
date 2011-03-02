<?php
/**
 * \class ItemModel
 * \brief Pdo Model
 */
class ItemModel extends AppModelPdo
{
  public $_name = 'item';
  public $_key = 'item_id';

  public $_mainData= array(
      'item_id'=>  array('type'=>MIDAS_DATA),
      'name' =>  array('type'=>MIDAS_DATA),
      'description' =>  array('type'=>MIDAS_DATA),
      'type' =>  array('type'=>MIDAS_DATA),
      'sizebytes'=>array('type'=>MIDAS_DATA),
      'thumbnail'=>array('type'=>MIDAS_DATA),
      'folders' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Folder', 'table' => 'item2folder', 'parent_column'=> 'item_id', 'child_column' => 'folder_id'),
      'revisions' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'ItemRevision', 'parent_column'=> 'item_id', 'child_column' => 'item_id'),
      'keywords' => array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'ItemKeyword', 'table' => 'item2keyword', 'parent_column'=> 'item_id', 'child_column' => 'keyword_id'),
      );

  /** check if the policy is valid*/
  function policyCheck($itemdao,$userDao=null,$policy=0)
    {
    if(!$itemdao instanceof  ItemDao||!is_numeric($policy))
      {
      throw new Zend_Exception("Error param.");
      }
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
      
     $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'itempolicyuser'),
                                 array('item_id'))
                          ->where('policy >= ?', $policy)
                          ->where('p.item_id >= ?', $itemdao->getKey())
                          ->where('user_id = ? ',$userId);

     $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'itempolicygroup'),
                           array('item_id'))
                    ->where('policy >= ?', $policy)
                    ->where('p.item_id >= ?', $itemdao->getKey())
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));

    $sql = $this->select()
            ->union(array($subqueryUser, $subqueryGroup));

    $rowset = $this->fetchAll($sql);
    if(count($rowset)>0)
      {
      return true;
      }
    return false;
    }//end policyCheck
    
  /** get random items
   *
   * @param UserDao $userDao
   * @param type $policy
   * @param type $limit
   * @return array of ItemDao 
   */
  function getRandomItems($userDao=null,$policy=0,$limit=10,$thumbnailFilter=false)
    {
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
      
          
    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('f' => 'item'))
                          ->join(array('p' => 'itempolicyuser'),
                                'f.item_id=p.item_id',
                                 array('p.policy'))
                          ->where('p.policy >= ?', $policy)
                          ->where('p.user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'item'))
                    ->join(array('p' => 'itempolicygroup'),
                                'f.item_id=p.item_id',
                                 array('p.policy'))
                    ->where('p.policy >= ?', $policy)
                    ->where('( '.$this->_db->quoteInto('p.group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              p.group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ))
                     ->limit($limit*2)
                     ->order( new Zend_Db_Expr('RAND() ASC') );
    if($thumbnailFilter)
      {
      $subqueryUser->where('thumbnail != ?','');
      $subqueryGroup->where('thumbnail != ?','');
      }
    $sql = $this->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $rowset = $this->fetchAll($sql);
    $rowsetAnalysed=array();
    foreach ($rowset as $keyRow=>$row)
      {
      foreach($rowsetAnalysed as $keyRa=>$ra)
        {
        if($ra->getKey()==$row['item_id'])
          {
          if($ra->policy<$row['item_id'])
            {
            $rowsetAnalysed[$keyRa]->policy=$row['policy'];
            }
          unset($row);
          break;
          }
        }
      if(isset($row))
        {
        $tmpDao= $this->initDao('Item', $row);
        $tmpDao->policy=$row['policy'];
        $rowsetAnalysed[] = $tmpDao;
        unset($tmpDao);
        }
      }
    $i=1;
    foreach($rowsetAnalysed as $keyRa=>$r)
      {
      if($i>$limit)
        {
        unset($rowsetAnalysed[$keyRa]);
        }
      $i++;
      } 
    return $rowsetAnalysed;
    }//end get random
    
  /** Get the last revision
   * @return ItemRevisionDao*/
  function getLastRevision($itemdao)
    {
    if(!$itemdao instanceof  ItemDao||!$itemdao->saved)
      {
      throw new Zend_Exception("Error param.");
      }
    return $this->initDao('ItemRevision', $this->fetchRow($this->select()->from('itemrevision')
                                              ->where('item_id = ?', $itemdao->getItemId())
                                              ->order(array('revision DESC'))
                                              ->limit(1)
                                              ->setIntegrityCheck(false)
                                              ));
    }
    
    /** Get  revision
   * @return ItemRevisionDao*/
  function getRevision($itemdao,$number)
    {
    if(!$itemdao instanceof  ItemDao||!$itemdao->saved)
      {
      throw new Zend_Exception("Error param.");
      }
    return $this->initDao('ItemRevision', $this->fetchRow($this->select()->from('itemrevision')
                                              ->where('item_id = ?', $itemdao->getItemId())
                                              ->where('revision = ?', $number)
                                              ->limit(1)
                                              ->setIntegrityCheck(false)
                                              ));
    }

  /** Add a revision to an item
   * @return void*/
  function addRevision($itemdao,$revisiondao)
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
      $revisiondao->setRevision($latestrevision->getRevision()+1);
      }
    $revisiondao->setItemId($itemdao->getItemId());

    // TODO: Add the date but the database is doing it automatically so maybe not
    $ItemRevisionModel->save($revisiondao);
    } // end addRevision

  /** Add a keyword to an item
   * @return void*/
  function addKeyword($itemdao,$keyworddao)
    {
    if(!$itemdao instanceof ItemDao)
      {
      throw new Zend_Exception("First argument should be an item");
      }
    if(!$keyworddao instanceof ItemKeywordDao)
      {
      throw new Zend_Exception("Second argument should be a keyword");
      }
    $this->link('keywords',$itemdao,$keyworddao);
    } // end addKeyword

}  // end class
?>
