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
 * New User Invitation Model Base
 * This model represents the invitation of a user who has not yet registered
 * and has been invited by an existing user into a community group.
 */
abstract class NewUserInvitationModelBase extends AppModel
{
    /** Constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'newuserinvitation';
        $this->_daoName = 'NewUserInvitationDao';
        $this->_key = 'newuserinvitation_id';
        $this->_mainData = array(
            'newuserinvitation_id' => array('type' => MIDAS_DATA),
            'auth_key' => array('type' => MIDAS_DATA),
            'email' => array('type' => MIDAS_DATA),
            'inviter_id' => array('type' => MIDAS_DATA),
            'date_creation' => array('type' => MIDAS_DATA),
            'community_id' => array('type' => MIDAS_DATA),
            'group_id' => array('type' => MIDAS_DATA),
            'community' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Community',
                'parent_column' => 'community_id',
                'child_column' => 'community_id',
            ),
            'group' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Group',
                'parent_column' => 'group_id',
                'child_column' => 'group_id',
            ),
            'inviter' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'inviter_id',
            ),
        );
        $this->initialize(); // required
    }

    /** Get by params */
    abstract public function getByParams($params);

    /** Get all by params */
    abstract public function getAllByParams($params);

    /** Delete by group */
    abstract public function deleteByGroup($group);

    /** Delete by community */
    abstract public function deleteByCommunity($community);

    /**
     * Create the database record for inviting a user via email that is not registered yet.
     *
     * @param email The email of the user (should not exist in Midas already)
     * @param group The group to invite the user to
     * @param inviter The user issuing the invitation (typically the session user)
     * @return the created NewUserInvitationDao
     */
    public function createInvitation($email, $group, $inviter)
    {
        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $email = strtolower($email);

        /** @var NewUserInvitationDao $newUserInvitation */
        $newUserInvitation = MidasLoader::newDao('NewUserInvitationDao');
        $newUserInvitation->setEmail($email);
        $newUserInvitation->setAuthKey($randomComponent->generateString(64, '0123456789abcdef'));
        $newUserInvitation->setInviterId($inviter->getKey());
        $newUserInvitation->setGroupId($group->getKey());
        $newUserInvitation->setCommunityId($group->getCommunityId());
        $newUserInvitation->setDateCreation(date('Y-m-d H:i:s'));

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $existingUser = $userModel->getByEmail($email);
        if ($existingUser) {
            throw new Zend_Exception('User with that email already exists');
        }

        // If the user has already been sent an invitation to this community, delete existing record
        $existingInvitation = $this->getByParams(array('email' => $email, 'community_id' => $group->getCommunityId()));
        if ($existingInvitation) {
            $this->delete($existingInvitation);
        }

        $this->save($newUserInvitation);

        return $newUserInvitation;
    }
}
