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
 * @method array getCommunities()
 * @method void setCommunities(array $communities)
 * @method UserDao getUser()
 * @method void setUser(UserDao $user)
 * @method FeedpolicygroupDao getFeedpolicygroup()
 * @method void setFeedpolicygroup(FeedpolicygroupDao $feedPolicyGroup)
 * @method FeedpolicyuserDao getFeedpolicyuser()
 * @method void setFeedpolicyuser(FeedpolicyuserDao $feedPolicyUser)
 * @package Core\DAO
 */
class FeedDao extends AppDao
{
    /** @var string */
    public $_model = 'Feed';

    /**
     * Return the resource.
     *
     * @return string
     * @throws Zend_Exception
     */
    public function getResource()
    {
        $type = $this->getType();

        switch ($type) {
            case MIDAS_FEED_CREATE_COMMUNITY:
            case MIDAS_FEED_UPDATE_COMMUNITY:
                $model = MidasLoader::loadModel('Community');

                return $model->load($this->ressource);
            case MIDAS_FEED_COMMUNITY_INVITATION:
                $model = MidasLoader::loadModel('CommunityInvitation');

                return $model->load($this->ressource);
            case MIDAS_FEED_CREATE_FOLDER:
                $model = MidasLoader::loadModel('Folder');

                return $model->load($this->ressource);
            case MIDAS_FEED_CREATE_ITEM:
            case MIDAS_FEED_CREATE_LINK_ITEM:
                $model = MidasLoader::loadModel('Item');

                return $model->load($this->ressource);
            case MIDAS_FEED_CREATE_REVISION:
                $model = MidasLoader::loadModel('ItemRevision');

                return $model->load($this->ressource);
            case MIDAS_FEED_CREATE_USER:
                $model = MidasLoader::loadModel('User');

                return $model->load($this->ressource);
            case MIDAS_FEED_DELETE_COMMUNITY:
            case MIDAS_FEED_DELETE_FOLDER:
            case MIDAS_FEED_DELETE_ITEM:
                return $this->ressource;
            default:
                throw new Zend_Exception('Unable to define the type of feed.');
        }
    }

    /**
     * Return the resource.
     *
     * @deprecated
     * @return string
     */
    public function getRessource()
    {
        return $this->getResource();
    }

    /**
     * Set the resource.
     *
     * @param $resource resource
     */
    public function setResource($resource)
    {
        $this->ressource = $resource;
    }

    /**
     * Set the resource.
     *
     * @deprecated
     * @param $resource resource
     */
    public function setRessource($resource)
    {
        $this->setResource($resource);
    }
}
