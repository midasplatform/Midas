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
    }

} // end class
?>
