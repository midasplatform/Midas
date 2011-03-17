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
      'date'=>array('type'=>MIDAS_DATA),
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
      
    if(Zend_Registry::get('configDatabase')->database->adapter=='PDO_MYSQL')
      {
      $rand='RAND()';
      }
    else
      {
      $rand='random()';
      }
    $sql=$this->select()
        ->setIntegrityCheck(false)
        ->from(array('i' => 'item'))
        ->join(array('tt'=> $this->select()
                    ->from(array('i' => 'item'),array('maxid'=>'MAX(item_id)'))
                    ),        
            ' i.item_id >= FLOOR(tt.maxid*'.$rand.')')
        ->joinLeft(array('ip' => 'itempolicyuser'),'
                  i.item_id = ip.item_id AND '.$this->_db->quoteInto('ip.policy >= ?', $policy).'
                     AND '.$this->_db->quoteInto('user_id = ? ',$userId).' ',array('userpolicy'=>'ip.policy'))
        ->joinLeft(array('ipg' => 'itempolicygroup'),'
                        i.item_id = ipg.item_id AND '.$this->_db->quoteInto('ipg.policy >= ?', $policy).'
                           AND ( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                group_id IN (' .new Zend_Db_Expr(
                                $this->select()
                                     ->setIntegrityCheck(false)
                                     ->from(array('u2g' => 'user2group'),
                                            array('group_id'))
                                     ->where('u2g.user_id = ?' , $userId)
                                     ) .'))' ,array('grouppolicy'=>'ipg.policy'))
        ->where(
         '(
          ip.item_id is not null or
          ipg.item_id is not null)'
          )
        ->limit($limit*2)
        ;
    if($thumbnailFilter)
      {
      $sql->where('thumbnail != ?','');
      }

    $rowset = $this->fetchAll($sql);
    $rowsetAnalysed=array();
    foreach ($rowset as $keyRow=>$row)
      {
      if($row['userpolicy']==null)$row['userpolicy']=0;
      if($row['grouppolicy']==null)$row['grouppolicy']=0;
      if(!isset($rowsetAnalysed[$row['item_id']])||($rowsetAnalysed[$row['item_id']]->policy<$row['userpolicy']&&$rowsetAnalysed[$row['item_id']]->policy<$row['grouppolicy']))
        {
        $tmpDao= $this->initDao('Item', $row);
        if($row['userpolicy']>=$row['grouppolicy'])
          {
          $tmpDao->policy=$row['userpolicy'];
          }
        else
          {
          $tmpDao->policy=$row['grouppolicy'];
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
