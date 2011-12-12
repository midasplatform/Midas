<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

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
