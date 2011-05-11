<?php
require_once BASE_PATH.'/core/models/base/FeedpolicyuserModelBase.php';

/**
 * \class FolderpolicyuserModel
 * \brief Pdo Model
 */
class FeedpolicyuserModel extends FeedpolicyuserModelBase
{
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
    return $this->initDao('Feedpolicyuser', $this->database->fetchRow($this->database->select()->where('feed_id = ?', $feed->getKey())->where('user_id = ?', $user->getKey())));
    }  // end getPolicy
    
} // end class
?>
