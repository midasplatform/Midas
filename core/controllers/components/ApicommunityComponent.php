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

/** These are the implementations of the web api methods for community */
class ApicommunityComponent extends AppComponent
{
    /**
     * Create a new community or update an existing one using the uuid.
     *
     * @path /community
     * @http POST
     * @param name The community name
     * @param description (Optional) The community description
     * @param uuid (Optional) Uuid of the community. If none is passed, will generate one.
     * @param privacy (Optional) Default 'Public', possible values [Public|Private].
     * @param canjoin (Optional) Default 'Everyone', possible values [Everyone|Invitation].
     * @return The community dao that was created
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function communityCreate($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('name'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
        $userDao = $apihelperComponent->getUser($args);
        if ($userDao == false) {
            throw new Exception('Unable to find user', MIDAS_INVALID_POLICY);
        }

        $name = $args['name'];
        $uuid = isset($args['uuid']) ? $args['uuid'] : '';

        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        $record = false;
        if (!empty($uuid)) {
            $record = $uuidComponent->getByUid($uuid);
        }
        if ($record != false && $record instanceof CommunityDao) {
            if (!$communityModel->policyCheck($record, $userDao, MIDAS_POLICY_WRITE)
            ) {
                throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
            }
            $record->setName($name);
            if (isset($args['description'])) {
                $record->setDescription($args['description']);
            }
            if (isset($args['privacy'])) {
                if (!$communityModel->policyCheck($record, $userDao, MIDAS_POLICY_ADMIN)
                ) {
                    throw new Exception('Admin access required.', MIDAS_INVALID_POLICY);
                }
                $privacyCode = $apihelperComponent->getValidCommunityPrivacyCode($args['privacy']);
                $communityModel->setPrivacy($record, $privacyCode, $userDao);
            }
            if (isset($args['canjoin'])) {
                if (!$communityModel->policyCheck($record, $userDao, MIDAS_POLICY_ADMIN)
                ) {
                    throw new Exception('Admin access required.', MIDAS_INVALID_POLICY);
                }
                $canjoinCode = $apihelperComponent->getValidCommunityCanjoinCode($args['canjoin']);
                $record->setCanJoin($canjoinCode);
            }
            $communityModel->save($record);

            return $record->toArray();
        } else {
            if (!$userDao->isAdmin()) {
                throw new Exception('Only admins can create communities', MIDAS_INVALID_POLICY);
            }
            $description = '';
            $privacy = MIDAS_COMMUNITY_PUBLIC;
            $canJoin = MIDAS_COMMUNITY_CAN_JOIN;
            if (isset($args['description'])) {
                $description = $args['description'];
            }
            if (isset($args['privacy'])) {
                $privacy = $apihelperComponent->getValidCommunityPrivacyCode($args['privacy'], $userDao);
            }
            if (isset($args['canjoin'])) {
                $canJoin = $apihelperComponent->getValidCommunityCanjoinCode($args['canjoin']);
            }
            $communityDao = $communityModel->createCommunity($name, $description, $privacy, $userDao, $canJoin, $uuid);

            if ($communityDao === false) {
                throw new Exception('Create community failed', MIDAS_INTERNAL_ERROR);
            }

            return $communityDao->toArray();
        }
    }

    /**
     * Get a community's information based on id.
     *
     * @path /community/{id}
     * @http GET
     * @param id The id of the community
     * @return The community information
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function communityGet($args)
    {
        $hasId = array_key_exists('id', $args);
        $hasName = array_key_exists('name', $args);

        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $apihelperComponent->getUser($args);

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        if ($hasId) {
            $community = $communityModel->load($args['id']);
        } elseif ($hasName) {
            $community = $communityModel->getByName($args['name']);
        } else {
            throw new Exception('Parameter id or name is not defined', MIDAS_INVALID_PARAMETER);
        }

        if ($community === false || !$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_READ)
        ) {
            throw new Exception(
                "This community doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY
            );
        }

        return $community->toArray();
    }

    /**
     * Wrapper for the community get to correct the return values.
     *
     * @param array $args
     * @return array
     */
    public function communityGetWrapper($args)
    {
        $in = $this->communityGet($args);
        $out = array();
        $out['id'] = $in['community_id'];
        $out['name'] = $in['name'];
        $out['description'] = $in['description'];
        $out['date_created'] = $in['creation'];
        $out['date_updated'] = $in['creation']; // Fix this later
        $out['public'] = $in['privacy'] == 0;
        $out['root_folder_id'] = $in['folder_id'];
        $out['admin_group_id'] = $in['admingroup_id'];
        $out['moderator_group_id'] = $in['moderatorgroup_id'];
        $out['member_group_id'] = $in['membergroup_id'];
        $out['open_membership'] = $in['can_join'] == 1;
        $out['views'] = $in['view'];
        $out['uuid'] = $in['uuid'];

        return $out;
    }

    /**
     * Get a community's information based on name.
     *
     * @path /community/search
     * @http GET
     * @param name the name of the community
     * @return The community information
     *
     * @param array $args parameters
     */
    public function communitySearch($args)
    {
        return $this->communityGet($args);
    }

    /**
     * Get the immediate children of a community (non-recursive).
     *
     * @path /community/children/{id}
     * @http GET
     * @param id The id of the community
     * @return The folders in the community
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function communityChildren($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));
        $userDao = $apihelperComponent->getUser($args);

        $id = $args['id'];

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');
        $community = $communityModel->load($id);
        if (!$community) {
            throw new Exception('Invalid community id', MIDAS_INVALID_PARAMETER);
        }
        $folder = $folderModel->load($community->getFolderId());

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        try {
            $folders = $folderModel->getChildrenFoldersFiltered($folder, $userDao);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), MIDAS_INTERNAL_ERROR);
        }

        return array('folders' => $folders);
    }

    /**
     * Return a list of all communities visible to a user.
     *
     * @path /community
     * @http GET
     * @return A list of all communities
     *
     * @param array $args parameters
     */
    public function communityList($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $userDao = $apihelperComponent->getUser($args);

        if ($userDao && $userDao->isAdmin()) {
            $communities = $communityModel->getAll();
        } else {
            $communities = $communityModel->getPublicCommunities();
            if ($userDao) {
                $communities = array_merge($communities, $userModel->getUserCommunities($userDao));
            }
        }

        /** @var SortdaoComponent $sortDaoComponent */
        $sortDaoComponent = MidasLoader::loadComponent('Sortdao');
        $sortDaoComponent->field = 'name';
        $sortDaoComponent->order = 'asc';
        usort($communities, array($sortDaoComponent, 'sortByName'));

        return $sortDaoComponent->arrayUniqueDao($communities);
    }

    /**
     * Delete a community. Requires admin privileges on the community.
     *
     * @path /community/{id}
     * @http DELETE
     * @param id The id of the community
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function communityDelete($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
        $userDao = $apihelperComponent->getUser($args);
        if ($userDao == false) {
            throw new Exception('Unable to find user', MIDAS_INVALID_TOKEN);
        }
        $id = $args['id'];

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        $community = $communityModel->load($id);

        if ($community === false || !$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Exception(
                "This community doesn't exist  or you don't have the permissions.", MIDAS_INVALID_POLICY
            );
        }
        Zend_Registry::get('notifier')->callback('CALLBACK_CORE_COMMUNITY_DELETED', array('community' => $community));
        $communityModel->delete($community);
    }

    /**
     * list the groups for a community, requires admin privileges on the community.
     *
     * @path /community/group/{id}
     * @http GET
     * @param id id of community
     * @return array groups => a list of group ids mapped to group names
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function communityListGroups($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_GROUPS));
        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('You must be logged in to list groups in a community', MIDAS_INVALID_POLICY);
        }

        $communityId = $args['id'];

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');
        $community = $communityModel->load($communityId);
        if (!$community) {
            throw new Exception('Invalid id', MIDAS_INVALID_PARAMETER);
        }
        if (!$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Exception('Community Admin permissions required.', MIDAS_INVALID_POLICY);
        }

        $groups = $community->getGroups();
        $groupIdsToName = array();
        foreach ($groups as $group) {
            $groupIdsToName[$group->getGroupId()] = $group->getName();
        }

        return array('groups' => $groupIdsToName);
    }
}
