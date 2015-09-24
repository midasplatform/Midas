<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
 * Feed DAO.
 *
 * @method int getFeedId()
 * @method void setFeedId(int $feedId)
 * @method string getDate()
 * @method void setDate(string $date)
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method int getType()
 * @method void setType(int $type)
 * @method void setResource(string $resource)
 * @method array getCommunities()
 * @method void setCommunities(array $communities)
 * @method UserDao getUser()
 * @method void setUser(UserDao $user)
 * @method FeedpolicygroupDao getFeedpolicygroup()
 * @method void setFeedpolicygroup(FeedpolicygroupDao $feedPolicyGroup)
 * @method FeedpolicyuserDao getFeedpolicyuser()
 * @method void setFeedpolicyuser(FeedpolicyuserDao $feedPolicyUser)
 */
class FeedDao extends AppDao
{
    /** @var string */
    public $_model = 'Feed';

    /**
     * Return the resource.
     *
     * @return mixed
     * @throws Zend_Exception
     */
    public function getResource()
    {
        $type = $this->getType();

        switch ($type) {
            case MIDAS_FEED_CREATE_COMMUNITY:
            case MIDAS_FEED_UPDATE_COMMUNITY:
                /** @var CommunityModel $model */
                $model = MidasLoader::loadModel('Community');

                return $model->load($this->resource);
            case MIDAS_FEED_COMMUNITY_INVITATION:
                /** @var CommunityInvitationModel $model */
                $model = MidasLoader::loadModel('CommunityInvitation');

                return $model->load($this->resource);
            case MIDAS_FEED_CREATE_FOLDER:
                /** @var FolderModel $model */
                $model = MidasLoader::loadModel('Folder');

                return $model->load($this->resource);
            case MIDAS_FEED_CREATE_ITEM:
            case MIDAS_FEED_CREATE_LINK_ITEM:
                /** @var ItemModel $model */
                $model = MidasLoader::loadModel('Item');

                return $model->load($this->resource);
            case MIDAS_FEED_CREATE_REVISION:
                /** @var ItemRevisionModel $model */
                $model = MidasLoader::loadModel('ItemRevision');

                return $model->load($this->resource);
            case MIDAS_FEED_CREATE_USER:
                /** @var UserModel $model */
                $model = MidasLoader::loadModel('User');

                return $model->load($this->resource);
            case MIDAS_FEED_DELETE_COMMUNITY:
            case MIDAS_FEED_DELETE_FOLDER:
            case MIDAS_FEED_DELETE_ITEM:
                return $this->resource;
            default:
                return false;
        }
    }

    /**
     * Return the resource.
     *
     * @deprecated since 3.3.0
     * @return mixed
     */
    public function getRessource()
    {
        return $this->getResource();
    }

    /**
     * Set the resource.
     *
     * @deprecated since 3.3.0
     * @param string $resource resource
     */
    public function setRessource($resource)
    {
        $this->setResource($resource);
    }
}
