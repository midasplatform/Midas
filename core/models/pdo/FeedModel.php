<?php
require_once BASE_PATH.'/core/models/base/FeedModelBase.php';

/**
 * \class FeedModel
 * \brief Pdo Model
 */
class FeedModel extends FeedModelBase
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
  

    
  /** get feed
   * @param UserDao $loggedUserDao
   * @param UserDao $userDao
   * @param CommunityDao $communityDao
   * @param type $policy
   * @param type $limit
   * @return Array of FeedDao */
  protected function _getFeeds($loggedUserDao,$userDao=null,$communityDao=null,$policy=0,$limit=20)
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
    return parent::delete($feeDao);
    } // end delete
    
} // end class
?>
