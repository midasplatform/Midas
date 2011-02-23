<?php
/**
 * \class FeedModel
 * \brief Pdo Model
 */
class FeedModel extends AppModelPdo
{
  public $_name = 'feed';
  public $_key = 'feed_id';

  public $_mainData= array(
    'feed_id'=> array('type'=>MIDAS_DATA),
    'date'=> array('type'=>MIDAS_DATA),
    'user_id'=> array('type'=>MIDAS_DATA),
    'type'=> array('type'=>MIDAS_DATA),
    'ressource'=> array('type'=>MIDAS_DATA),
    'communities' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Community', 'table' => 'feed2community', 'parent_column'=> 'feed_id', 'child_column' => 'community_id'),
    'user' => array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'User', 'parent_column'=> 'user_id', 'child_column' => 'user_id'),
    'feedpolicygroup' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Feedpolicygroup', 'parent_column'=> 'feed_id', 'child_column' => 'feed_id'),
    'feedpolicyuser' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Feedpolicyuser', 'parent_column'=> 'feed_id', 'child_column' => 'feed_id'),
    );

  /** get feeds (filtered by policies)
   * @return Array of FeedDao */
  function getGlobalFeeds($loggedUserDao,$policy=0)
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

    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feedpolicyuser'),
                                 array('feed_id'))
                          ->where('p.policy >= ?', $policy)
                          ->where('p.user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'feedpolicygroup'),
                           array('feed_id'))
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));
    $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from('feed')
                ->where('feed_id IN ('.$subqueryUser.')' )
                ->orWhere('feed_id IN ('.$subqueryGroup.')' );
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Feed', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    }
  /** get feeds by user (filtered by policies)
   * @return Array of FeedDao */
  function getFeedsByUser($loggedUserDao,$userDao,$policy=0)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
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

    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feedpolicyuser'),
                                 array('feed_id'))
                          ->join(array('f' => 'feed'),
                          $this->_db->quoteInto('f.user_id = ? ',$userDao->getKEy())
                          .' AND p.feed_id = f.feed_id'  ,array())
                          ->where('p.policy >= ?', $policy)
                          ->where('p.user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'feedpolicygroup'),
                           array('feed_id'))
                    ->join(array('f' => 'feed'),
                          $this->_db->quoteInto('f.user_id = ? ',$userDao->getKEy())
                          .' AND p.feed_id = f.feed_id'  ,array())
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));
    $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from('feed')
                ->where('feed_id IN ('.$subqueryUser.')' )
                ->orWhere('feed_id IN ('.$subqueryGroup.')' );
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Feed', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    }
  /** get feeds by community (filtered by policies)
     * @return Array of FeedDao */
  function getFeedsByCommunity($loggedUserDao,$communityDao,$policy=0)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Should be a community.");
      }
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

    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feedpolicyuser'),
                                 array('feed_id'))
                          ->join(array('f' => 'feed2community'),
                          $this->_db->quoteInto('f.community_id = ? ',$communityDao->getKEy())
                          .' AND p.feed_id = f.feed_id'  ,array())
                          ->where('p.policy >= ?', $policy)
                          ->where('p.user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'feedpolicygroup'),
                           array('feed_id'))
                    ->join(array('f' => 'feed2community'),
                          $this->_db->quoteInto('f.community_id = ? ',$communityDao->getKEy())
                          .' AND p.feed_id = f.feed_id'  ,array())
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));
    $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from('feed')
                ->where('feed_id IN ('.$subqueryUser.')' )
                ->orWhere('feed_id IN ('.$subqueryGroup.')' );
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Feed', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    }
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
    }


  /** Add an item to a community
   * @return void*/
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
    $this->link('communities',$feed,$community);
    } // end function addCommunity

} // end class
?>
