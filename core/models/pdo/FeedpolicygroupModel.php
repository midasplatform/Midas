<?php
/**
 * \class FeedpolicygroupModel
 * \brief Pdo Model
 */
class FeedpolicygroupModel extends AppModelPdo
  {
  public $_name='feedpolicygroup';

  public $_mainData=array(
    'feed_id'=>array(
    'type'=>MIDAS_DATA
  ),'group_id'=>array(
    'type'=>MIDAS_DATA
  ),'policy'=>array(
    'type'=>MIDAS_DATA
  ),'feed'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Feed','parent_column'=>'feed_id','child_column'=>'feed_id'
  ),'group'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Group','parent_column'=>'group_id','child_column'=>'group_id'
  )
  );

  /** create a policy
   * @return FeedpolicygroupDao*/
  public function createPolicy($group, $feed, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feedDao.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$feed->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group,$feed) !== false)
      {
      $this->delete($this->getPolicy($group,$feed));
      }
    $this->loadDaoClass('FeedpolicygroupDao');
    $policyGroupDao=new FeedpolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setFeedId($feed->getFeedId());
    $policyGroupDao->setPolicy($policy);
    $this->save($policyGroupDao);
    return $policyGroupDao;
    }

  /** getPolicy
   * @return FeedpolicygroupDao
   */
  public function getPolicy($group, $feed)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    return $this->initDao('Feedpolicygroup',$this->fetchRow($this->select()->where('feed_id = ?',$feed->getKey())->where('group_id = ?',$group->getKey())));
    }
  }
?>
