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
 * New user invitation DAO.
 *
 * @method int getNewuserinvitationId()
 * @method void setNewuserinvitationId(int $newUserInvitationId)
 * @method string getAuthKey()
 * @method void setAuthKey(string $authKey)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method int getInviterId()
 * @method void setInviterId(int $inviterId)
 * @method string getDateCreation()
 * @method void setDateCreation(string $dateCreation)
 * @method int getCommunityId()
 * @method void setCommunityId(int $communityId)
 * @method int getGroupId()
 * @method void setGroupId(int $groupId)
 * @method CommunityDao getCommunity()
 * @method void setCommunity(CommunityDao $community)
 * @method GroupDao getGroup()
 * @method void setGroup(GroupDao $group)
 * @method UserDao getInviter()
 * @method void setInviter(UserDao $inviter)
 */
class NewUserInvitationDao extends AppDao
{
    /** @var string */
    public $_model = 'NewUserInvitation';
}
