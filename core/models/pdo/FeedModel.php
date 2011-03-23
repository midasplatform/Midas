<?php
/**
 * \class FeedModel
 * \brief Pdo Model
 */
class FeedModel extends MIDASFeedModel
{ 
 
  /** check if the policy is valid
   *
   * @param FolderDao $folderDao
   * @param UserDao $userDao
   * @param type $policy
   * @return  boolean 
   */
  function policyCheck($feedDao,$userDao=null,$policy=0)
    {
    if(!$feedDao instanceof FeedDao||!is_numeric($policy))
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
      
     $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feedpolicyuser'),
                                 array('feed_id'))
                          ->where('policy >= ?', $policy)
                          ->where('p.feed_id >= ?', $feedDao->getKey())
                          ->where('user_id = ? ',$userId);

     $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'feedpolicygroup'),
                           array('feed_id'))
                    ->where('policy >= ?', $policy)
                    ->where('p.feed_id >= ?', $feedDao->getKey())
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));

    $sql = $this->database->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $rowset = $this->database->fetchAll($sql);
    if(count($rowset)>0)
      {
      return true;
      }
    return false;
    } //end policyCheck
  
  /** get feeds (filtered by policies)
   * @return Array of FeedDao */
  function getGlobalFeeds($loggedUserDao,$policy=0,$limit=20)
    {
    return $this->_getFeeds($loggedUserDao,null,null,$policy,$limit);
    } //end getGlobalFeeds
    
  /** get feeds by user (filtered by policies)
   * @return Array of FeedDao */
  function getFeedsByUser($loggedUserDao,$userDao,$policy=0,$limit=20)
    {
    return $this->_getFeeds($loggedUserDao,$userDao,null,$policy,$limit);
    } //end getFeedsByUser
    
  /** get feeds by community (filtered by policies)
     * @return Array of FeedDao */
  function getFeedsByCommunity($loggedUserDao,$communityDao,$policy=0,$limit=20)
    {
    return $this->_getFeeds($loggedUserDao,null,$communityDao,$policy,$limit);
    } //end getFeedsByCommunity
    
  /** get feed
   * @param UserDao $loggedUserDao
   * @param UserDao $userDao
   * @param CommunityDao $communityDao
   * @param type $policy
   * @param type $limit
   * @return Array of FeedDao */
  private function _getFeeds($loggedUserDao,$userDao=null,$communityDao=null,$policy=0,$limit=20)
    {
    if($loggedUserDao==null)
      {
      $userId= -1;
      }
    else if(!$loggedUserDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $loggedUserDao->getUserId();
      }
    
    if($userDao!=null&&!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }

    if($communityDao!=null&&!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Should be a community.");
      }
      
    
    $sql=$this->database->select()
          ->setIntegrityCheck(false)
          ->from(array('f' => 'feed'))
          ->joinLeft(array('fpu' => 'feedpolicyuser'),'
                    f.feed_id = fpu.feed_id AND '.$this->database->getDB()->quoteInto('fpu.policy >= ?', $policy).'
                       AND '.$this->database->getDB()->quoteInto('fpu.user_id = ? ',$userId).' ',array('userpolicy'=>'fpu.policy'))
          ->joinLeft(array('fpg' => 'feedpolicygroup'),'
                          f.feed_id = fpg.feed_id AND '.$this->database->getDB()->quoteInto('fpg.policy >= ?', $policy).'
                             AND ( '.$this->database->getDB()->quoteInto('fpg.group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                  fpg.group_id IN (' .new Zend_Db_Expr(
                                  $this->database->select()
                                       ->setIntegrityCheck(false)
                                       ->from(array('u2g' => 'user2group'),
                                              array('group_id'))
                                       ->where('u2g.user_id = ?' , $userId)
                                       ) .'))' ,array('grouppolicy'=>'fpg.policy'))
          ->where(
           '(
            fpu.feed_id is not null or
            fpg.feed_id is not null)'
            )
          ->limit($limit)
          ;
    
    if($userDao!=null)
      {
      $sql->where('f.user_id = ? ',$userDao->getKey());
      }
      
    if($communityDao!=null)
      {
      $sql->join(array('f2c' => 'feed2community'),
                          $this->database->getDB()->quoteInto('f2c.community_id = ? ',$communityDao->getKey())
                          .' AND f.feed_id = f2c.feed_id'  ,array());
      }
    
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed=array();
    foreach ($rowset as $keyRow=>$row)
      {
      if($row['userpolicy']==null)$row['userpolicy']=0;
      if($row['grouppolicy']==null)$row['grouppolicy']=0;
      if(!isset($rowsetAnalysed[$row['feed_id']])||($rowsetAnalysed[$row['feed_id']]->policy<$row['userpolicy']&&$rowsetAnalysed[$row['feed_id']]->policy<$row['grouppolicy']))
        {
        $tmpDao= $this->initDao('Feed', $row);
        if($row['userpolicy']>=$row['grouppolicy'])
          {
          $tmpDao->policy=$row['userpolicy'];
          }
        else
          {
          $tmpDao->policy=$row['grouppolicy'];
          }
        $rowsetAnalysed[$row['feed_id']] = $tmpDao;
        unset($tmpDao);
        }
      }
    $this->Component->Sortdao->field='date';
    $this->Component->Sortdao->order='asc';
    usort($rowsetAnalysed, array($this->Component->Sortdao,'sortByDate'));
    return $rowsetAnalysed;    
    } // end _getFeeds
    
  /** Create a feed
   * @return FeedDao */
  function createFeed($userDao,$type,$ressource,$communityDao=null)
    {
    if(!$userDao instanceof UserDao&&!is_numeric($type)&&!is_object($ressource))
      {
      throw new Zend_Exception("Error parameters.");
      }
    $this->loadDaoClass('FeedDao');
    $feed=new FeedDao();
    $feed->setUserId($userDao->getKey());
    $feed->setType($type);
    $feed->setDate(date('c'));
    switch($type)
      {
      case MIDAS_FEED_CREATE_COMMUNITY:
      case MIDAS_FEED_UPDATE_COMMUNITY:
        if(!$ressource instanceof CommunityDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_FOLDER:
        if(!$ressource instanceof FolderDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_LINK_ITEM:
      case MIDAS_FEED_CREATE_ITEM:
         if(!$ressource instanceof ItemDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_REVISION:
        if(!$ressource instanceof ItemRevisionDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_CREATE_USER:
        if(!$ressource instanceof UserDao)
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource->getKey());
        break;
      case MIDAS_FEED_DELETE_COMMUNITY:
      case MIDAS_FEED_DELETE_FOLDER:
      case MIDAS_FEED_DELETE_ITEM:
        if(!is_string($ressource))
          {
          throw new Zend_Exception("Error parameter ressource, type:".$type);
          }
        $feed->setRessource($ressource);
        break;
      default:
        throw new Zend_Exception("Unable to defined the type of feed");
        break;
      }
    $this->save($feed);

    if($communityDao instanceof CommunityDao)
      {
      $this->addCommunity($feed, $communityDao);
      }
    return $feed;
    } // end createFeed()


  /** Add an item to a community
   * @return void */
  function addCommunity($feed,$community)
    {
    if(!$community instanceof CommunityDao)
      {
      throw new Zend_Exception("Should be a community.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be an feed.");
      }
    $this->database->link('communities',$feed,$community);
    } // end addCommunity

  /** Delete Dao
   * @param FeedDao $feeDao 
   */
  function delete($feeDao)
    {
    $this->ModelLoader = new MIDAS_ModelLoader();
    $feedpolicygroups=$feeDao->getFeedpolicygroup();    
    $feedpolicygroupModel=$this->ModelLoader->loadModel('Feedpolicygroup');
    foreach($feedpolicygroups as $f)
      {
      $feedpolicygroupModel->delete($f);
      }
      
    $feedpolicyuser=$feeDao->getFeedpolicyuser();    
    $feedpolicyuserModel=$this->ModelLoader->loadModel('Feedpolicyuser');
    foreach($feedpolicyuser as $f)
      {
      $feedpolicyuserModel->delete($f);
      }
      
    $communities=$feeDao->getCommunities();
    foreach($communities as $c)
      {
      $this->database->removeLink('communities', $feeDao, $c);
      }
    return $this->delete($feeDao);
    } // end delete
    
} // end class
?>
