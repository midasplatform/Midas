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

/** These are the implementations of the web api methods for user */
class ApiuserComponent extends AppComponent
{
    /**
     * Return a list of top level folders belonging to the user.
     *
     * @path /user/folders
     * @http GET
     * @return List of the user's top level folders
     */
    public function userFolders($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $apihelperComponent->getUser($args);
        if ($userDao == false) {
            return array();
        }

        $userRootFolder = $userDao->getFolder();

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        return $folderModel->getChildrenFoldersFiltered($userRootFolder, $userDao, MIDAS_POLICY_READ);
    }

    /**
     * Returns a portion or the entire set of public users based on the limit var.
     *
     * @path /user
     * @http GET
     * @param limit The maximum number of users to return
     * @return the list of users
     */
    public function userList($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('limit'));

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');

        return $userModel->getAll(true, $args['limit'], array('firstname','lastname','company','website'));
    }

    /**
     * Returns a user either by id.
     *
     * @path /user/{id}
     * @http GET
     * @param id The id of the user desired (ignores first name and last name)
     * @return The user corresponding to the user_id
     */
    public function userGet($args)
    {
        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        if (array_key_exists('id', $args)) {
            return $userModel->getByUser_id($args['id']);
        } else {
            throw new Exception('Please provide a user id', MIDAS_INVALID_PARAMETER);
        }
    }

    /**
     * Wrapper for correcting types on user get.
     *
     * @param array $args
     * @return array
     */
    public function userGetWrapper($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $in = $this->userGet($args);
        $in = $in->toArray();
        $out = array();
        $out['id'] = $in['user_id'];
        $out['firstname'] = $in['firstname'];
        $out['lastname'] = $in['lastname'];
        if ($apihelperComponent->isCallerAdmin($args)) {
            $out['email'] = $in['email'];
        }
        $out['thumbnail'] = $in['thumbnail'];
        $out['company'] = $in['company'];
        $out['date_created'] = $in['creation'];
        $out['date_updated'] = $in['creation']; // Fix this later
        $out['root_folder_id'] = $in['folder_id'];
        $out['admin'] = $in['admin'] == 1;
        $out['public'] = $in['privacy'] == 0;
        $out['views'] = $in['view'];
        $out['uuid'] = $in['uuid'];
        $out['city'] = $in['city'];
        $out['country'] = $in['country'];
        $out['website'] = $in['website'];
        $out['biography'] = $in['biography'];

        return $out;
    }

    /**
     * Returns a user by email or by first name and last name.
     *
     * @path /user/search
     * @http GET
     * @param email (Optional) The email of the user desired
     * @param firstname (Optional) The first name of the desired user (use with lastname)
     * @param lastname (Optional) The last name of the desired user (use with firstname)
     * @return The user corresponding to the email or first and last name
     */
    public function userSearch($args)
    {
        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        if (array_key_exists('email', $args)) {
            return $userModel->getByEmail($args['email']);
        } elseif (array_key_exists('firstname', $args) && array_key_exists('lastname', $args)
        ) {
            return $userModel->getByName($args['firstname'], $args['lastname']);
        } else {
            throw new Exception('Please provide a user email or both first and last name', MIDAS_INVALID_PARAMETER);
        }
    }
}
