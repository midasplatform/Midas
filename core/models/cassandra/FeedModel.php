<?php
require_once BASE_PATH.'/core/models/base/FeedModelBase.php';

/**
 * \class FeedModel
 * \brief Cassandra Model
 */
class FeedModel extends FeedModelBase
{
  
  protected function _getFeeds($loggedUserDao,$userDao=null,$communityDao=null,$policy=0,$limit=20)
    {
    if($loggedUserDao==null)
      {
      $userId=0; // anonymous
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
    
    // Select from the UserFeedPolicy
    $userfeedpolicyarray = $this->database->getCassandra('userfeedpolicy',$userId);
    
    // Get the groups from the user
    $usergrouparray = $this->database->getCassandra('user',$userId,null,"group_","group_"); // need to get only the group_* columns
    
    //print_r($usergrouparray);
    
    $groupids[] = MIDAS_GROUP_ANONYMOUS_KEY;
    
    // Select from the GroupFeedPolicy for the groups
    // multiget
    $groupfeedpolicyarray = $this->database->multigetCassandra('groupfeedpolicy',$groupids); // need to get only the group_* columns
    
    //print_r($groupfeedpolicyarray);
    
    $rowsetAnalysed=array();
    // Start with the users
    foreach($userfeedpolicyarray as $key=>$row)
      {
      $feed_id = substr($key,5);
      $feedrow = $this->database->getCassandra('feed',$feed_id);  
      $feedrow['feed_id'] = $feed_id;
      $dao =  $this->initDao('Feed', $feedrow);
      $dao->policy = $row;
      $rowsetAnalysed[$feed_id] = $dao;
      unset($dao);
      }

    // Then the groups  
    foreach($groupfeedpolicyarray as $key=>$grouprow)
      { 
      foreach($grouprow as $feedid=>$policy)
        {     
        $feedrow = $this->database->getCassandra('feed',$feed_id);         
        $feedrow['feed_id'] = $feed_id;
        
        if(isset($rowsetAnalysed[$feed_id]))
          {
          $dao = $rowsetAnalysed[$feed_id];
          if($dao->policy<$policy)
            {
            $rowsetAnalysed[$feed_id]->policy = $policy; 
            }   
          }
        else 
          { 
          $dao = $this->initDao('Feed', $feedrow);
          $dao->policy = $policy;
          $rowsetAnalysed[$feedid] = $dao;
          unset($dao);
          }
        }
      }
      
    $this->Component->Sortdao->field='date';
    $this->Component->Sortdao->order='asc';
    usort($rowsetAnalysed, array($this->Component->Sortdao,'sortByDate'));
    return $rowsetAnalysed;
    }
    
  /** Add a community to a feed 
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
    
    $feedid = $feed->getFeedId();
    $communityid = $community->getCommunityId();
    $column = 'community_'.$communityid;    

    $dataarray = array();
    $dataarray[$column] = date('c');
      
    $column_family = new ColumnFamily($this->database->getDB(),'feed');
    $column_family->insert($feedid,$dataarray);  

    } // end addCommunity
    
    
} // end class
?>
