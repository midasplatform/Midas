<?php
/**
 * \class FeedDao
 * \brief DAO Item (table Feed)
 */
class FeedDao extends AppDao
{
  public $_model = 'Feed';

  /** overwite get Ressource method */
  public function getRessource()
    {
    $type = $this->getType();
    require_once BASE_PATH.'/library/MIDAS/models/ModelLoader.php';
    $modelLoader = new MIDAS_ModelLoader();
    switch($type)
      {
      case MIDAS_FEED_CREATE_COMMUNITY:
      case MIDAS_FEED_UPDATE_COMMUNITY:
        $model = $modelLoader->loadModel("Community");
        return $model->load($this->ressource);
      case MIDAS_FEED_COMMUNITY_INVITATION:
        $model = $modelLoader->loadModel("CommunityInvitation");
        return $model->load($this->ressource);
      case MIDAS_FEED_CREATE_FOLDER:
        $model = $modelLoader->loadModel("Folder");
        return $model->load($this->ressource);
      case MIDAS_FEED_CREATE_ITEM:
      case MIDAS_FEED_CREATE_LINK_ITEM:
        $model = $modelLoader->loadModel("Item");
        return $model->load($this->ressource);
      case MIDAS_FEED_CREATE_REVISION:
        $model = $modelLoader->loadModel("ItemRevision");
        return $model->load($this->ressource);
      case MIDAS_FEED_CREATE_USER:
        $model = $modelLoader->loadModel("User");
        return $model->load($this->ressource);
      case MIDAS_FEED_DELETE_COMMUNITY:
      case MIDAS_FEED_DELETE_FOLDER:
      case MIDAS_FEED_DELETE_ITEM:
        return $this->ressource;
      default:
        throw new Zend_Exception("Unable to defined the type of feed");
        break;
      }
    }
} // end class
?>
