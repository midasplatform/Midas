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
 * Producer DAO for the tracker module.
 *
 * @method int getProducerId()
 * @method void setProducerId(int $producerId)
 * @method int getCommunityId()
 * @method void setCommunityId(int $communityId)
 * @method string getRepository()
 * @method void setRepository(string $repository)
 * @method string getRevisionUrl()
 * @method void setRevisionUrl(string $revisionUrl)
 * @method string getExecutableName()
 * @method void setExecutableName(string $executableName)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method CommunityDao getCommunity()
 * @method void setCommunity(CommunityDao $communityDao)
 * @method array getTrends()
 * @method void setTrends(array $trendDaos)
 * @package Modules\Tracker\DAO
 */
class Tracker_ProducerDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'Producer';

    /** @var string */
    public $_module = 'tracker';
}
