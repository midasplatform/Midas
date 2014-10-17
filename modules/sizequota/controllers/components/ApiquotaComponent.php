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

// Web API error codes
define('MIDAS_SIZEQUOTA_INVALID_POLICY', -151);
define('MIDAS_SIZEQUOTA_INVALID_PARAMETER', -150);

/** Sizequot Component for api methods */
class Sizequota_ApiquotaComponent extends AppComponent
{
    /**
     * Get the size quota for a user.
     *
     * @path /sizequota/quota/user
     * @http GET
     * @param user Id of the user to check
     * @return array('quota' => The size quota in bytes for the user, or empty string if unlimited,
     *                       'used' => Size in bytes currently used)
     */
    public function userGet($args)
    {
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('user'));
        $requestUser = $apihelperComponent->getUser($args);

        $folderModel = MidasLoader::loadModel('Folder');
        $userModel = MidasLoader::loadModel('User');

        $user = $userModel->load($args['user']);
        if (!$user) {
            throw new Exception('Invalid user id', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
        }

        if (!$folderModel->policyCheck($user->getFolder(), $requestUser, MIDAS_POLICY_READ)
        ) {
            throw new Exception('Read permission required', MIDAS_SIZEQUOTA_INVALID_POLICY);
        }
        $quotaModel = MidasLoader::loadModel('FolderQuota', 'sizequota');
        $quota = $quotaModel->getUserQuota($user);
        $used = $folderModel->getSize($user->getFolder());

        return array('quota' => $quota, 'used' => $used);
    }

    /**
     * Get the size quota for a community.
     *
     * @path /sizequota/quota/community
     * @http GET
     * @param community Id of the community to check
     * @return array('quota' => The size quota in bytes for the community, or empty string if unlimited,
     *                       'used' => Size in bytes currently used)
     */
    public function communityGet($args)
    {
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('community'));
        $requestUser = $apihelperComponent->getUser($args);

        $folderModel = MidasLoader::loadModel('Folder');
        $commModel = MidasLoader::loadModel('Community');

        $comm = $commModel->load($args['community']);
        if (!$comm) {
            throw new Exception('Invalid community id', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
        }

        if (!$folderModel->policyCheck($comm->getFolder(), $requestUser, MIDAS_POLICY_READ)
        ) {
            throw new Exception('Read permission required', MIDAS_SIZEQUOTA_INVALID_POLICY);
        }
        $quotaModel = MidasLoader::loadModel('FolderQuota', 'sizequota');
        $quota = $quotaModel->getCommunityQuota($comm);
        $used = $folderModel->getSize($comm->getFolder());

        return array('quota' => $quota, 'used' => $used);
    }

    /**
     * Set a quota for a folder. For MIDAS admin use only.
     *
     * @path /sizequota/quota
     * @http POST
     * @param folder The folder id
     * @param quota (Optional) The quota. Pass a number of bytes or the empty string for unlimited.
     * If this parameter isn't specified, deletes the current quota entry if one exists.
     */
    public function set($args)
    {
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('folder'));
        $user = $apihelperComponent->getUser($args);

        if (!$user || !$user->isAdmin()) {
            throw new Exception('Must be super-admin', MIDAS_SIZEQUOTA_INVALID_POLICY);
        }
        $folderModel = MidasLoader::loadModel('Folder');
        $folder = $folderModel->load($args['folder']);
        if (!$folder) {
            throw new Exception('Invalid folder id', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
        }
        if ($folder->getParentId() > 0) {
            throw new Exception('Must be a root folder', MIDAS_SIZEQUOTA_INVALID_PARAMETER);
        }
        $quota = array_key_exists('quota', $args) ? $args['quota'] : null;
        if ($quota !== null && !preg_match('/^[0-9]*$/', $quota)) {
            throw new Exception(
                'Quota must be empty string or an integer if specified',
                MIDAS_SIZEQUOTA_INVALID_PARAMETER
            );
        }
        $quotaModel = MidasLoader::loadModel('FolderQuota', 'sizequota');

        return $quotaModel->setQuota($folder, $quota);
    }
}
