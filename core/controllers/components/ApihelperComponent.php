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

/** These are the implementations of helper functions for the core web apis */
class ApihelperComponent extends AppComponent
{
    /**
     * This should be called before _getUser to define what policy scopes (see module.php constants)
     * are required for the current API endpoint. If this is not called and _getUser is called,
     * the default behavior is to require PERMISSION_SCOPE_ALL.
     *
     * @param array $scopes A list of scope constants that are required for the operation
     */
    public function requirePolicyScopes($scopes)
    {
        Zend_Registry::get('notifier')->callback('CALLBACK_API_REQUIRE_PERMISSIONS', array('scopes' => $scopes));
    }

    /**
     * Return the user DAO
     *
     * @param array $args
     * @return false|UserDao
     */
    public function getUser($args)
    {
        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');

        return $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
    }

    /**
     * Pass the args and a list of required parameters.
     * Will throw an exception if a required one is missing.
     *
     * @param array $args
     * @param array $requiredList
     * @throws Exception
     */
    public function validateParams($args, $requiredList)
    {
        foreach ($requiredList as $param) {
            if (!array_key_exists($param, $args)) {
                throw new Exception('Parameter '.$param.' is not defined', MIDAS_INVALID_PARAMETER);
            }
        }
    }

    /**
     * Get the global configuration in order to set up the api.
     *
     * @return array
     */
    public function getApiSetup()
    {
        $apiSetup = array();
        $apiSetup['testing'] = Zend_Registry::get('configGlobal')->environment == 'testing';
        $apiSetup['tmpDirectory'] = UtilityComponent::getTempDirectory();

        return $apiSetup;
    }

    /**
     * Helper function to return any extra fields that should be passed with an item
     *
     * @param ItemDao $item item DAO
     * @return array
     */
    public function getItemExtraFields($item)
    {
        $extraFields = array();
        // Add any extra fields that modules want to attach to the item
        $modules = Zend_Registry::get('notifier')->callback('CALLBACK_API_EXTRA_ITEM_FIELDS', array('item' => $item));
        foreach ($modules as $module => $fields) {
            foreach ($fields as $name => $value) {
                $extraFields[$module.'_'.$name] = $value;
            }
        }

        return $extraFields;
    }

    /**
     * helper function to get a revision of a certain number from an item,
     * if revisionNumber is null will get the last revision of the item; used
     * by the metadata calls and so has exception handling built in for them.
     *
     * will return a valid ItemRevision or else throw an exception.
     *
     *
     * @param ItemDao $item
     * @param null|int $revisionNumber
     * @return ItemRevisionDao
     * @throws Exception
     */
    public function getItemRevision($item, $revisionNumber = null)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        if (!isset($revisionNumber)) {
            $revisionDao = $itemModel->getLastRevision($item);
            if ($revisionDao) {
                return $revisionDao;
            } else {
                throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
            }
        }

        $revisionNumber = (int) $revisionNumber;
        if (!is_int($revisionNumber) || $revisionNumber < 1) {
            throw new Exception(
                "Revision Numbers must be integers greater than 0.".$revisionNumber,
                MIDAS_INVALID_PARAMETER
            );
        }
        $revisions = $item->getRevisions();
        if (count($revisions) === 0) {
            throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
        }
        // check revisions exist
        foreach ($revisions as $revision) {
            if ($revisionNumber == $revision->getRevision()) {
                $revisionDao = $revision;
                break;
            }
        }
        if (isset($revisionDao)) {
            return $revisionDao;
        } else {
            throw new Exception("This revision number is invalid for this item.", MIDAS_INVALID_PARAMETER);
        }
    }

    /**
     * helper method to validate passed in privacy status params and
     * map them to valid privacy codes.
     *
     * @param string $privacyStatus privacy status, should be 'Private' or 'Public'
     * @return int privacy code
     * @throws Exception
     */
    public function getValidPrivacyCode($privacyStatus)
    {
        if ($privacyStatus !== 'Public' && $privacyStatus !== 'Private') {
            throw new Exception('privacy should be one of [Public|Private]', MIDAS_INVALID_PARAMETER);
        }
        if ($privacyStatus === 'Public') {
            $privacyCode = MIDAS_PRIVACY_PUBLIC;
        } else {
            $privacyCode = MIDAS_PRIVACY_PRIVATE;
        }

        return $privacyCode;
    }

    /**
     * helper function to set the privacy code on a passed in item.
     *
     * @param ItemDao $item
     * @param int $privacyCode
     */
    public function setItemPrivacy($item, $privacyCode)
    {
        /** @var ItempolicygroupModel $itempolicygroupModel */
        $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $itempolicygroupDao = $itempolicygroupModel->getPolicy($anonymousGroup, $item);
        if ($privacyCode == MIDAS_PRIVACY_PRIVATE && $itempolicygroupDao !== false) {
            $itempolicygroupModel->delete($itempolicygroupDao);
        } elseif ($privacyCode == MIDAS_PRIVACY_PUBLIC && $itempolicygroupDao == false) {
            $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
        } else {
            // ensure the cached privacy status value is up to date
            $itempolicygroupModel->computePolicyStatus($item);
        }
    }

    /**
     * Helper function to set metadata on an item.
     * Does not perform permission checks; these should be done in advance.
     *
     * @param ItemDao $item
     * @param int $type
     * @param string $element
     * @param string $qualifier
     * @param mixed $value
     * @param null|ItemRevisionDao $revisionDao
     * @throws Exception
     */
    public function setMetadata($item, $type, $element, $qualifier, $value, $revisionDao = null)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        if ($revisionDao === null) {
            $revisionDao = $itemModel->getLastRevision($item);
        }
        $modules = Zend_Registry::get('notifier')->callback(
            'CALLBACK_API_METADATA_SET',
            array(
                'item' => $item,
                'revision' => $revisionDao,
                'type' => $type,
                'element' => $element,
                'qualifier' => $qualifier,
                'value' => $value,
            )
        );
        foreach ($modules as $retval) {
            if ($retval['status'] === true) { // module has handled the event, so we don't have to

                return;
            }
        }

        // If no module handles this metadata, we add it as normal metadata on the item revision
        if (!$revisionDao) {
            throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
        }

        /** @var MetadataModel $metadataModel */
        $metadataModel = MidasLoader::loadModel('Metadata');
        $metadataDao = $metadataModel->getMetadata($type, $element, $qualifier);
        if ($metadataDao == false) {
            $metadataModel->addMetadata($type, $element, $qualifier, '');
        }
        $metadataModel->addMetadataValue($revisionDao, $type, $element, $qualifier, $value);
    }

    /**
     * helper function to parse out the metadata tuples from the params for a
     * call to setmultiplemetadata, will validate matching tuples to count.
     *
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function parseMetadataTuples($args)
    {
        $count = (int) $args['count'];
        if (!is_int($count) || $count < 1) {
            throw new Exception("Count must be an integer greater than 0.", MIDAS_INVALID_PARAMETER);
        }
        $metadataTuples = array();
        for ($i = 0; $i < $count; $i = $i + 1) {
            // counters are 1 indexed
            $counter = $i + 1;
            $element_i_key = 'element_'.$counter;
            $value_i_key = 'value_'.$counter;
            $qualifier_i_key = 'qualifier_'.$counter;
            $type_i_key = 'type_'.$counter;
            if (!array_key_exists($element_i_key, $args)) {
                throw new Exception(
                    "Count was ".$i." but param ".$element_i_key." is missing.",
                    MIDAS_INVALID_PARAMETER
                );
            }
            if (!array_key_exists($value_i_key, $args)) {
                throw new Exception(
                    "Count was ".$i." but param ".$value_i_key." is missing.",
                    MIDAS_INVALID_PARAMETER
                );
            }
            $element = $args[$element_i_key];
            $value = $args[$value_i_key];
            $qualifier = array_key_exists($qualifier_i_key, $args) ? $args[$qualifier_i_key] : '';
            $type = array_key_exists($type_i_key, $args) ? $args[$qualifier_i_key] : MIDAS_METADATA_TEXT;
            if (!is_int($type) || $type < 0 || $type > 6) {
                throw new Exception(
                    "param ".$type_i_key." must be an integer between 0 and 6.",
                    MIDAS_INVALID_PARAMETER
                );
            }
            $metadataTuples[] = array(
                'element' => $element,
                'qualifier' => $qualifier,
                'type' => $type,
                'value' => $value,
            );
        }

        return $metadataTuples;
    }

    /**
     * helper function to set the privacy code on a passed in folder.
     */
    public function setFolderPrivacy($folder, $privacyCode)
    {
        /** @var FolderpolicygroupModel $folderpolicygroupModel */
        $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $folderpolicygroupDao = $folderpolicygroupModel->getPolicy($anonymousGroup, $folder);

        if ($privacyCode == MIDAS_PRIVACY_PRIVATE && $folderpolicygroupDao !== false) {
            $folderpolicygroupModel->delete($folderpolicygroupDao);
        } elseif ($privacyCode == MIDAS_PRIVACY_PUBLIC && $folderpolicygroupDao == false) {
            $folderpolicygroupModel->createPolicy($anonymousGroup, $folder, MIDAS_POLICY_READ);
        } else {
            // ensure the cached privacy status value is up to date
            $folderpolicygroupModel->computePolicyStatus($folder);
        }
    }

    /**
     * helper method to validate passed in policy params and
     * map them to valid policy codes.
     *
     * @param  string $policy , should be [Admin|Write|Read]
     * @return valid  policy code
     */
    public function getValidPolicyCode($policy)
    {
        $policyCodes = array('Admin' => MIDAS_POLICY_ADMIN, 'Write' => MIDAS_POLICY_WRITE, 'Read' => MIDAS_POLICY_READ);
        if (!array_key_exists($policy, $policyCodes)) {
            $validCodes = '['.implode('|', array_keys($policyCodes)).']';
            throw new Exception('policy should be one of '.$validCodes, MIDAS_INVALID_PARAMETER);
        }

        return $policyCodes[$policy];
    }

    /**
     * helper function to return listing of permissions for a resource.
     *
     * @return A list with three keys: privacy, user, group; privacy will be the
     *           resource's privacy string [Public|Private]; user will be a list of
     *           (user_id, policy, email); group will be a list of (group_id, policy, name).
     *           policy for user and group will be a policy string [Admin|Write|Read].
     */
    public function listResourcePermissions($policyStatus, $userPolicies, $groupPolicies)
    {
        $privacyStrings = array(MIDAS_PRIVACY_PUBLIC => "Public", MIDAS_PRIVACY_PRIVATE => "Private");
        $privilegeStrings = array(
            MIDAS_POLICY_ADMIN => "Admin",
            MIDAS_POLICY_WRITE => "Write",
            MIDAS_POLICY_READ => "Read",
        );

        $return = array('privacy' => $privacyStrings[$policyStatus]);

        $userPoliciesOutput = array();
        foreach ($userPolicies as $userPolicy) {
            $user = $userPolicy->getUser();
            $userPoliciesOutput[] = array(
                'user_id' => $user->getUserId(),
                'policy' => $privilegeStrings[$userPolicy->getPolicy()],
                'email' => $user->getEmail(),
            );
        }
        $return['user'] = $userPoliciesOutput;

        $groupPoliciesOutput = array();
        foreach ($groupPolicies as $groupPolicy) {
            $group = $groupPolicy->getGroup();
            $groupPoliciesOutput[] = array(
                'group_id' => $group->getGroupId(),
                'policy' => $privilegeStrings[$groupPolicy->getPolicy()],
                'name' => $group->getName(),
            );
        }
        $return['group'] = $groupPoliciesOutput;

        return $return;
    }

    /**
     * helper function to validate args of methods for adding or removing
     * users from groups.
     *
     * @param id the group to add the user to
     * @param user_id the user to add to the group
     * @return an array of (groupModel, groupDao, groupUserDao)
     */
    public function validateGroupUserChangeParams($args)
    {
        $this->validateParams($args, array('id', 'user_id'));

        $userDao = $this->getUser($args);
        if (!$userDao) {
            throw new Exception('You must be logged in to add a user to a group', MIDAS_INVALID_POLICY);
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
            throw new Zend_Exception("Community Admin permissions required.", MIDAS_INVALID_POLICY);
        }

        $groupUserId = $args['user_id'];

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $groupUser = $userModel->load($groupUserId);
        if ($groupUser == false) {
            throw new Exception('This user does not exist', MIDAS_INVALID_PARAMETER);
        }

        return array($groupModel, $group, $groupUser);
    }

    /**
     * Helper function for checking for a metadata type index or name and
     * handling the error conditions.
     */
    public function checkMetadataTypeOrName(&$args, &$metadataModel)
    {
        if (array_key_exists('typename', $args)) {
            return $metadataModel->mapNameToType($args['typename']);
        } elseif (array_key_exists('type', $args)) {
            return $args['type'];
        } else {
            throw new Exception('Parameter type is not defined', MIDAS_INVALID_PARAMETER);
        }
    }

    /**
     * helper method to validate passed in community privacy status params and
     * map them to valid community privacy codes.
     *
     * @param  string $privacyStatus , should be 'Private' or 'Public'
     * @return valid  community privacy code
     */
    public function getValidCommunityPrivacyCode($privacyStatus)
    {
        if ($privacyStatus !== 'Public' && $privacyStatus !== 'Private') {
            throw new Exception('privacy should be one of [Public|Private]', MIDAS_INVALID_PARAMETER);
        }
        if ($privacyStatus === 'Public') {
            $privacyCode = MIDAS_COMMUNITY_PUBLIC;
        } else {
            $privacyCode = MIDAS_COMMUNITY_PRIVATE;
        }

        return $privacyCode;
    }

    /**
     * helper method to validate passed in community can join status params and
     * map them to valid community can join codes.
     *
     * @param  string $canjoinStatus , should be 'Everyone' or 'Invitation'
     * @return valid  community canjoin code
     */
    public function getValidCommunityCanjoinCode($canjoinStatus)
    {
        if ($canjoinStatus !== 'Everyone' && $canjoinStatus !== 'Invitation') {
            throw new Exception('privacy should be one of [Everyone|Invitation]', MIDAS_INVALID_PARAMETER);
        }
        if ($canjoinStatus === 'Everyone') {
            $canjoinCode = MIDAS_COMMUNITY_CAN_JOIN;
        } else {
            $canjoinCode = MIDAS_COMMUNITY_INVITATION_ONLY;
        }

        return $canjoinCode;
    }

    /**
     * Rename a request parameter's key to provide backward compatibility for existing web APIs.
     */
    public function renameParamKey(&$args, $oldKey, $newKey, $oldKeyRequired = true)
    {
        if ($oldKeyRequired) {
            $this->validateParams($args, array($oldKey));
        }
        if (isset($args[$oldKey])) {
            $args[$newKey] = $args[$oldKey];
            unset($args[$oldKey]);
        }
    }

    /**
     * Check if the caller is an administrator based on the api call arguments.
     */
    public function isCallerAdmin($args)
    {
        $userDao = $this->getUser($args);

        if (!$userDao || !$userDao->isAdmin()) {
            return false;
        } else {
            return true;
        }
    }
}
