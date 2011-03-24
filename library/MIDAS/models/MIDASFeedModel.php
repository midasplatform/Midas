<?php
abstract class MIDASFeedModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();  
    $this->_name = 'feed';
    $this->_key = 'feed_id'; 
    $this->_components = array('Sortdao');
    $this->_mainData= array(
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
    $this->initialize(); // required
    } // end __construct() 
  
  protected abstract function _getFeeds($loggedUserDao,$userDao=null,$communityDao=null,$policy=0,$limit=20);
    
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
    
} // end class MIDASFeedModel
?>