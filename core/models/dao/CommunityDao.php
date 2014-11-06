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
 * Community DAO.
 *
 * @method int getCommunityId()
 * @method void setCommunityId(int $communityId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getCreation()
 * @method void setCreation(string $creation)
 * @method int getPrivacy()
 * @method void setPrivacy(int $privacy)
 * @method int getFolderId()
 * @method void setFolderId(int $folderId)
 * @method int getAdmingroupId()
 * @method void setAdmingroupId(int $adminGroupId)
 * @method int getModeratorgroupId()
 * @method void setModeratorgroupId(int $moderatorGroupId)
 * @method int getMembergroupId()
 * @method void setMembergroupId(int $memberGroupId)
 * @method int getCanJoin()
 * @method void setCanJoin(int $canJoin)
 * @method int getView()
 * @method void setView(int $view)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method FolderDao getFolder()
 * @method void setFolder(FolderDao $folder)
 * @method GroupDao getAdminGroup()
 * @method void setAdminGroup(GroupDao $adminGroup)
 * @method GroupDao getModeratorGroup()
 * @method void setModeratorGroup(GroupDao $moderatorGroup)
 * @method array getInvitations()
 * @method void setInvitations(array $invitations)
 * @method array getGroups()
 * @method void setGroups(array $groups)
 * @method GroupDao getMemberGroup()
 * @method void setMemberGroup(GroupDao $memberGroup)
 * @method array getFeeds()
 * @method void setFeeds(array $feeds)
 * @package Core\DAO
 */
class CommunityDao extends AppDao
{
    /** @var string */
    public $_model = 'Community';
}
