<?php
/**
 * \class FolderpolicyuserModel
 * \brief Pdo Model
 */
class FeedpolicyuserModel extends MIDASFeedpolicyuserModel
{
  /** create a policy
   * @return FeedpolicyuserDao*/
  public function createPolicy($user, $feed, $policy)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$user->saved && !$feed->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($user,$feed) !== false)
      {
      $this->database->delete($this->getPolicy($user,$feed));
      }
    $this->loadDaoClass('FeedpolicyuserDao');
    $policyUser=new FeedpolicyuserDao();
    $policyUser->setUserId($user->getUserId());
    $policyUser->setFeedId($feed->getFeedId());
    $policyUser->setPolicy($policy);
    $this->save($policyUser);
    return $policyUser;
    }

  /** getPolicy
   * @return FeedpolicyuserDao
   */
  public function getPolicy($user, $feed)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    return $this->initDao('Feedpolicyuser',$this->database->fetchRow($this->database->select()->where('feed_id = ?',$feed->getKey())->where('user_id = ?',$user->getKey())));
    }
} // end class
?>
