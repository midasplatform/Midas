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

/** These are the implementations of the web api methods for group */
class ApigroupComponent extends AppComponent
{
    /**
     * list the users for a group, requires admin privileges on the community
     * associated with the group.
     *
     * @path /group/users/{id}
     * @http GET
     * @param id id of group
     * @return array users => a list of user ids mapped to a two element list of
     *               user first name and last name
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function groupListUsers($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('You must be logged in to list users in a group', MIDAS_INVALID_POLICY);
        }

        $groupId = $args['id'];

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $group = $groupModel->load($groupId);
        if ($group == false) {
            throw new Exception('This group does not exist', MIDAS_INVALID_PARAMETER);
        }

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        if (!$communityModel->policyCheck($group->getCommunity(), $userDao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Community Admin permissions required.', MIDAS_INVALID_POLICY);
        }

        $users = $group->getUsers();
        $userIdsToEmail = array();
        foreach ($users as $user) {
            $userIdsToEmail[$user->getUserId()] = array(
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            );
        }

        return array('users' => $userIdsToEmail);
    }

    /**
     * Add a user to a group, returns 'success' => 'true' on success, requires
     * admin privileges on the community associated with the group.
     *
     * @path /group/adduser/{id}
     * @http PUT
     * @param id the group to add the user to
     * @param user_id the user to add to the group
     * @return success = true on success.
     *
     * @param array $args parameters
     */
    public function groupAddUser($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        list($groupModel, $group, $addedUser) = $apihelperComponent->validateGroupUserChangeParams($args);
        $groupModel->addUser($group, $addedUser);

        return array('success' => 'true');
    }

    /**
     * Remove a user from a group, returns 'success' => 'true' on success, requires
     * admin privileges on the community associated with the group.
     *
     * @path /group/removeuser/{id}
     * @http PUT
     * @param id the group to remove the user from
     * @param user_id the user to remove from the group
     * @return success = true on success.
     *
     * @param array $args parameters
     */
    public function groupRemoveUser($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        list($groupModel, $group, $removedUser) = $apihelperComponent->validateGroupUserChangeParams($args);
        $groupModel->removeUser($group, $removedUser);

        return array('success' => 'true');
    }

    /**
     * for use in array_map in groupGet.
     *
     * @param UserDao $user
     * @return mixed
     */
    public function getIdFromUser($user)
    {
        return $user->getUserId();
    }

    /**
     * Get information about the group.
     *
     * @path /group/{id}
     * @http GET
     * @param id the group id
     * @return the group object
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function groupGet($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));

        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('You must be logged in to list users in a group', MIDAS_INVALID_POLICY);
        }

        $groupId = $args['id'];

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $group = $groupModel->load($groupId);
        if ($group === false) {
            throw new Exception('This group does not exist.', MIDAS_NOT_FOUND);
        }
        $in = $group->toArray();
        $out = array();
        $out['id'] = $in['group_id'];
        $out['community_id'] = $in['community_id'];
        $out['name'] = $in['name'];
        $out['users'] = array_map(array($this, 'getIdFromUser'), $group->getUsers());

        return $out;
    }

    /**
     * add a group associated with a community, requires admin privileges on the
     * community.
     *
     * @path /group
     * @http POST
     * @param community_id the id of the community the group will associate with
     * @param name the name of the new group
     * @return group_id of the newly created group on success.
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function groupAdd($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('community_id', 'name'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('You must be logged in to add group', MIDAS_INVALID_POLICY);
        }

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        $communityId = $args['community_id'];
        $community = $communityModel->load($communityId);
        if ($community == false) {
            throw new Exception('This community does not exist', MIDAS_INVALID_PARAMETER);
        }
        if (!$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Exception('Community Admin permissions required.', MIDAS_INVALID_POLICY);
        }

        $name = $args['name'];

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $group = $groupModel->createGroup($community, $name);

        return array('group_id' => $group->getGroupId());
    }

    /**
     * remove a group associated with a community, requires admin privileges on the
     * community.
     *
     * @path /group/{id}
     * @http DELETE
     * @param id the id of the group to be removed
     * @return success = true on success.
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function groupRemove($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('You must be logged in to remove a group', MIDAS_INVALID_POLICY);
        }

        $groupId = $args['id'];

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $group = $groupModel->load($groupId);
        if ($group == false) {
            throw new Exception('This group does not exist', MIDAS_INVALID_PARAMETER);
        }

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        if (!$communityModel->policyCheck($group->getCommunity(), $userDao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Exception('Community Admin permissions required.', MIDAS_INVALID_POLICY);
        }

        $groupModel->delete($group);

        return array('success' => 'true');
    }
}
