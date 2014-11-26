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

/** These are the implementations of the core web api methods */
class Api_ApiComponent extends AppComponent
{
    public $controller;
    public $apiSetup;
    public $userSession;

    /**
     * This should be called before _getUser to define what policy scopes (see module.php constants)
     * are required for the current API endpoint. If this is not called and _getUser is called,
     * the default behavior is to require PERMISSION_SCOPE_ALL.
     *
     * @param scopes A list of scope constants that are required for the operation
     */
    private function _requirePolicyScopes($scopes)
    {
        Zend_Registry::get('notifier')->callback('CALLBACK_API_REQUIRE_PERMISSIONS', array('scopes' => $scopes));
    }

    /**
     * Pass the args and a list of required parameters.
     * Will throw an exception if a required one is missing.
     */
    private function _validateParams($args, $requiredList)
    {
        foreach ($requiredList as $param) {
            if (!array_key_exists($param, $args)) {
                throw new Exception('Parameter '.$param.' is not defined', MIDAS_INVALID_PARAMETER);
            }
        }
    }

    /** Return the user dao */
    private function _getUser($args)
    {
        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');

        return $authComponent->getUser($args, $this->userSession->Dao);
    }

    /** Return the user dao */
    private function _callCoreApiMethod($args, $coreApiMethod, $resource = null, $hasReturn = true)
    {
        $ApiComponent = MidasLoader::loadComponent('Api'.$resource);
        $rtn = $ApiComponent->$coreApiMethod($args);
        if ($hasReturn) {
            return $rtn;
        }

        return null;
    }

    /**
     * Get the server version
     *
     * @return Server version in the form <major>.<minor>.<patch>
     */
    public function version($args)
    {
        return $this->_callCoreApiMethod($args, 'version', 'system');
    }

    /**
     * Get the enabled modules on the server
     *
     * @return List of enabled modules on the server
     */
    public function modulesList($args)
    {
        return $this->_callCoreApiMethod($args, 'modulesList', 'system');
    }

    /**
     * List all available web api methods on the server
     *
     * @return List of api method names and their corresponding documentation
     */
    public function methodsList($args)
    {
        $data = array();
        $data['methods'] = array();

        $apiMethods = Zend_Registry::get('notifier')->callback('CALLBACK_API_HELP', array());
        foreach ($apiMethods as $module => $methods) {
            foreach ($methods as $method) {
                $apiMethodName = $module != 'api' ? $module.'.' : '';
                $apiMethodName .= $method['name'];
                $data['methods'][] = array('name' => $apiMethodName, 'help' => $method['help']);
            }
        }

        return $data;
    }

    /**
     * Get the server information including version, modules enabled,
     * and available web api methods (names do not include the global prefix)
     *
     * @return Server information
     */
    public function info($args)
    {
        return $this->_callCoreApiMethod($args, 'info', 'system');
    }

    /**
     * Login as a user using a web api key
     *
     * @param appname The application name
     * @param email The user email
     * @param apikey The api key corresponding to the given application name
     * @return A web api token that will be valid for a set duration
     */
    public function login($args)
    {
        return $this->_callCoreApiMethod($args, 'login', 'system');
    }

    /**
     * Get a resource by its UUID
     *
     * @param uuid Universal identifier for the resource
     * @param folder (Optional) If set, will return the folder instead of the community record
     * @return The resource's dao
     */
    public function resourceGet($args)
    {
        $this->_validateParams($args, array('uuid'));

        $uuid = $args['uuid'];

        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');
        $resource = $uuidComponent->getByUid($uuid);

        if ($resource == false) {
            throw new Exception('No resource for the given UUID.', MIDAS_INVALID_PARAMETER);
        }

        if ($resource->resourceType == MIDAS_RESOURCE_COMMUNITY && array_key_exists('folder', $args)
        ) {
            return array('type' => MIDAS_RESOURCE_FOLDER, 'id' => $resource->getFolderId());
        }

        return array('type' => $resource->resourceType, 'id' => $resource->getKey());
    }

    /**
     * Returns a path of uuids from the root folder to the given node
     *
     * @param uuid Unique identifier of the resource
     * @return An ordered list of uuids representing a path from the root node
     */
    public function pathFromRoot($args)
    {
        return array_reverse($this->pathToRoot($args));
    }

    /**
     * Returns a path of uuids from the given node to the root node
     *
     * @param uuid Unique identifier of the resource
     * @return An ordered list of uuids representing a path to the root node
     */
    public function pathToRoot($args)
    {
        $this->_validateParams($args, array('uuid'));

        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');
        $element = $uuidComponent->getByUid($args['uuid']);

        $return = array();
        $return[] = $element->toArray();

        if ($element == false) {
            throw new Exception('No resource for the given UUID.', MIDAS_INVALID_PARAMETER);
        }

        if ($element instanceof FolderDao) {
            $parent = $element->getParent();
            while ($parent !== false) {
                $return[] = $parent->toArray();
                $parent = $parent->getParent();
            }
        } elseif ($element instanceof ItemDao) {
            $owningFolders = $element->getFolders();
            // randomly pick one parent folder
            $parent = $owningFolders[0];
            while ($parent !== false) {
                $return[] = $parent->toArray();
                $parent = $parent->getParent();
            }
        } elseif (!$element instanceof CommunityDao) {
            // community element itself is the root
            throw new Exception('Should be a folder, an item or a community.', MIDAS_INVALID_PARAMETER);
        }

        return $return;
    }

    /**
     * Search items for the given words
     *
     * @param token (Optional) Authentication token
     * @param search The search query
     * @param folder Parent uuid folder
     * @return An array of matching resources
     */
    public function itemSearch($args)
    {
        $this->_validateParams($args, array('search'));
        $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $this->_getUser($args);

        $order = 'view';
        if (isset($args['order'])) {
            $order = $args['order'];
        }
        $folder = false;
        if (isset($args['folder'])) {
            $folder = $args['folder'];
        }

        /** @var SearchComponent $searchComponent */
        $searchComponent = MidasLoader::loadComponent('Search');

        return $searchComponent->searchItems($userDao, $args['search'], $folder, $order);
    }

    /**
     * Search resources for the given words
     *
     * @param token (Optional) Authentication token
     * @param search The search query
     * @return An array of matching resources
     */
    public function resourceSearch($args)
    {
        $this->_validateParams($args, array('search'));
        $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $this->_getUser($args);

        $order = 'view';
        if (isset($args['order'])) {
            $order = $args['order'];
        }

        /** @var SearchComponent $searchComponent */
        $searchComponent = MidasLoader::loadComponent('Search');

        return $searchComponent->searchAll($userDao, $args['search'], $order);
    }

    /**
     * Create a link bitstream.
     *
     * @param token Authentication token.
     * @param folderid
    The id of the folder in which to create a new item that will
     * contain the link. The new item will have the same name as
     * <b>url</b> unless <b>itemname</b> is supplied.
     * @param url The URL of the link you will create, will be used as the name
     * of the bitstream and of the item (unless <b>itemname</b> is
     * supplied).
     * @param itemname (Optional)
     * The name of the newly created item, if not supplied, the item will
     * have the same name as <b>url</b>.
     * @param length (Optional)
     * The length in bytes of the file to which the link points.
     * @param checksum (Optional)
     * The md5 checksum of the file to which the link points.
     * @return The item information of the item created.
     */
    public function linkCreate($args)
    {
        return $this->_callCoreApiMethod($args, 'linkCreate', 'system');
    }

    /**
     * Generate a unique upload token.  Either <b>itemid</b> or <b>folderid</b> is required,
     * but both are not allowed.
     *
     * @param token Authentication token.
     * @param itemid
    The id of the item to upload into.
     * @param folderid
    The id of the folder to create a new item in and then upload to.
     * The new item will have the same name as <b>filename</b> unless <b>itemname</b>
     * is supplied.
     * @param filename The filename of the file you will upload, will be used as the
     * bitstream's name and the item's name (unless <b>itemname</b> is supplied).
     * @param itemdescription (Optional)
     * When passing the <b>folderid</b> param, the description of the item,
     * if not supplied the item's description will be blank.
     * @param itemname (Optional)
     * When passing the <b>folderid</b> param, the name of the newly created item,
     * if not supplied, the item will have the same name as <b>filename</b>.
     * @param checksum (Optional) The md5 checksum of the file to be uploaded.
     * @return An upload token that can be used to upload a file.
     *            If <b>folderid</b> is passed instead of <b>itemid</b>, a new item will be created
     *            in that folder, but the id of the newly created item will not be
     *            returned.  If the id of the newly created item is needed,
     *            then call the <b>midas.item.create</b> method instead.
     *            If <b>checksum</b> is passed and the token returned is blank, the
     *            server already has this file and there is no need to follow this
     *            call with a call to <b>midas.upload.perform</b>, as the passed in
     *            file will have been added as a bitstream to the item's latest
     *            revision, creating a new revision if one doesn't exist.
     */
    public function uploadGeneratetoken($args)
    {
        return $this->_callCoreApiMethod($args, 'uploadGeneratetoken', 'system');
    }

    /**
     * Get the size of a partially completed upload
     *
     * @param uploadtoken The upload token for the file
     * @return [offset] The size of the file currently on the server
     */
    public function uploadGetoffset($args)
    {
        return $this->_callCoreApiMethod($args, 'uploadGetoffset', 'system');
    }

    /**
     * Upload a file to the server. PUT or POST is required.
     * Will add the file as a bitstream to the item that was specified when
     * generating the upload token in a new revision to that item, unless
     * <b>revision</b> param is set.
     *
     * @param uploadtoken The upload token (see <b>midas.upload.generatetoken</b>).
     * @param filename The name of the bitstream that will be added to the item.
     * @param length The length in bytes of the file being uploaded.
     * @param mode (Optional) Stream or multipart. Default is stream.
     * @param revision (Optional)
     * If set, will add a new file into the existing passed in revision number.
     * If set to "head", will add a new file into the most recent revision,
     * and will create a new revision in this case if none exists.
     * @param changes (Optional)
     * The changes field on the affected item revision,
     * e.g. for recording what has changed since the previous revision.
     * @return The item information of the item created or changed.
     */
    public function uploadPerform($args)
    {
        return $this->_callCoreApiMethod($args, 'uploadPerform', 'system');
    }

    /**
     * Create a new community or update an existing one using the uuid
     *
     * @param token Authentication token
     * @param name The community name
     * @param description (Optional) The community description
     * @param uuid (Optional) Uuid of the community. If none is passed, will generate one.
     * @param privacy (Optional) Default 'Public', possible values [Public|Private].
     * @param canjoin (Optional) Default 'Everyone', possible values [Everyone|Invitation].
     * @return The community dao that was created
     */
    public function communityCreate($args)
    {
        return $this->_callCoreApiMethod($args, 'communityCreate', 'community');
    }

    /**
     * Get a community's information based on the id OR name
     *
     * @param token (Optional) Authentication token
     * @param id The id of the community
     * @param name the name of the community
     * @return The community information
     */
    public function communityGet($args)
    {
        return $this->_callCoreApiMethod($args, 'communityGet', 'community');
    }

    /**
     * Get the immediate children of a community (non-recursive)
     *
     * @param token (Optional) Authentication token
     * @param id The id of the community
     * @return The folders in the community
     */
    public function communityChildren($args)
    {
        return $this->_callCoreApiMethod($args, 'communityChildren', 'community');
    }

    /**
     * Return a list of all communities visible to a user
     *
     * @param token (Optional) Authentication token
     * @return A list of all communities
     */
    public function communityList($args)
    {
        return $this->_callCoreApiMethod($args, 'communityList', 'community');
    }

    /**
     * Delete a community. Requires admin privileges on the community
     *
     * @param token Authentication token
     * @param id The id of the community
     */
    public function communityDelete($args)
    {
        $this->_callCoreApiMethod($args, 'communityDelete', 'community', false);
    }

    /**
     * Create a folder or update an existing one if one exists by the uuid passed.
     * If a folder is requested to be created with the same parentid and name as
     * an existing folder, an exception will be thrown and no new folder will
     * be created.
     *
     * @param token Authentication token
     * @param name The name of the folder to create
     * @param description (Optional) The description of the folder
     * @param uuid (Optional) Uuid of the folder. If none is passed, will generate one.
     * @param privacy (Optional) Possible values [Public|Private]. Default behavior is to inherit from parent folder.
     * @param reuseExisting (Optional) If this parameter is set, will just return the existing folder if there is one with the name provided
     * @param parentid The id of the parent folder. Set this to -1 to create a top level user folder.
     * @return The folder object that was created
     */
    public function folderCreate($args)
    {
        return $this->_callCoreApiMethod($args, 'folderCreate', 'folder');
    }

    /**
     * Get information about the folder
     *
     * @param token (Optional) Authentication token
     * @param id The id of the folder
     * @return The folder object, including its parent object
     */
    public function folderGet($args)
    {
        return $this->_callCoreApiMethod($args, 'folderGet', 'folder');
    }

    /**
     * Get the immediate children of a folder (non-recursive)
     *
     * @param token (Optional) Authentication token
     * @param id The id of the folder
     * @return The items and folders in the given folder
     */
    public function folderChildren($args)
    {
        return $this->_callCoreApiMethod($args, 'folderChildren', 'folder');
    }

    /**
     * Delete a folder. Requires admin privileges on the folder
     *
     * @param token Authentication token
     * @param id The id of the folder
     */
    public function folderDelete($args)
    {
        $this->_callCoreApiMethod($args, 'folderDelete', 'folder', false);
    }

    /**
     * Download a folder
     *
     * @param token (Optional) Authentication token
     * @param id The id of the folder
     * @return A zip archive of the folder's contents
     */
    public function folderDownload($args)
    {
        return $this->_callCoreApiMethod($args, 'folderDownload', 'folder');
    }

    /**
     * Move a folder to the destination folder
     *
     * @param token Authentication token
     * @param id The id of the folder
     * @param dstfolderid The id of destination folder (new parent folder) where the folder is moved to
     * @return The folder object
     */
    public function folderMove($args)
    {
        return $this->_callCoreApiMethod($args, 'folderMove', 'folder');
    }

    /**
     * List the permissions on a folder, requires Admin access to the folder.
     *
     * @param folder_id The id of the folder
     * @return A list with three keys: privacy, user, group; privacy will be the
     *           folder's privacy string [Public|Private]; user will be a list of
     *           (user_id, policy, email); group will be a list of (group_id, policy, name).
     *           policy for user and group will be a policy string [Admin|Write|Read].
     */
    public function folderListPermissions($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'folder_id', 'id');

        return $this->_callCoreApiMethod($args, 'folderListPermissions', 'folder');
    }

    /**
     * Set the privacy status on a folder, and push this value down recursively
     * to all children folders and items, requires Admin access to the folder.
     *
     * @param folder_id The id of the folder.
     * @param privacy Desired privacy status, one of [Public|Private].
     * @return An array with keys 'success' and 'failure' indicating a count
     *            of children resources that succeeded or failed the permission change.
     */
    public function folderSetPrivacyRecursive($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'folder_id', 'id');

        return $this->_callCoreApiMethod($args, 'folderSetPrivacyRecursive', 'folder');
    }

    /**
     * Add a folderpolicygroup to a folder with the passed in group and policy;
     * if a folderpolicygroup exists for that group and folder, it will be replaced
     * with the passed in policy.
     *
     * @param folder_id The id of the folder.
     * @param group_id The id of the group.
     * @param policy Desired policy status, one of [Admin|Write|Read].
     * @param recursive If included will push all policies from
     * the passed in folder down to its child folders and items, default is non-recursive.
     * @return An array with keys 'success' and 'failure' indicating a count of
     *            resources affected by the addition.
     */
    public function folderAddPolicygroup($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'folder_id', 'id');

        return $this->_callCoreApiMethod($args, 'folderAddPolicygroup', 'folder');
    }

    /**
     * Remove a folderpolicygroup from a folder with the passed in group if the
     * folderpolicygroup exists.
     *
     * @param folder_id The id of the folder.
     * @param group_id The id of the group.
     * @param recursive If included will push all policies after the removal from
     * the passed in folder down to its child folders and items, default is non-recursive.
     * @return An array with keys 'success' and 'failure' indicating a count of
     *            resources affected by the removal.
     */
    public function folderRemovePolicygroup($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'folder_id', 'id');

        return $this->_callCoreApiMethod($args, 'folderRemovePolicygroup', 'folder');
    }

    /**
     * Add a folderpolicyuser to a folder with the passed in user and policy;
     * if a folderpolicyuser exists for that user and folder, it will be replaced
     * with the passed in policy.
     *
     * @param folder_id The id of the folder.
     * @param user_id The id of the targeted user to create the policy for.
     * @param policy Desired policy status, one of [Admin|Write|Read].
     * @param recursive If included will push all policies from
     * the passed in folder down to its child folders and items, default is non-recursive.
     * @return An array with keys 'success' and 'failure' indicating a count of
     *            resources affected by the addition.
     */
    public function folderAddPolicyuser($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'folder_id', 'id');

        return $this->_callCoreApiMethod($args, 'folderAddPolicyuser', 'folder');
    }

    /**
     * Remove a folderpolicyuser from a folder with the passed in user if the
     * folderpolicyuser exists.
     *
     * @param folder_id The id of the folder.
     * @param user_id The id of the target user.
     * @param recursive If included will push all policies after the removal from
     * the passed in folder down to its child folders and items, default is non-recursive.
     * @return An array with keys 'success' and 'failure' indicating a count of
     *            resources affected by the removal.
     */
    public function folderRemovePolicyuser($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'folder_id', 'id');

        return $this->_callCoreApiMethod($args, 'folderRemovePolicyuser', 'folder');
    }

    /**
     * Check whether an item with the given name exists in the given folder
     *
     * @param parentid The id of the parent folder
     * @param name The name of the item
     * @return array('exists' => bool)
     */
    public function itemExists($args)
    {
        return $this->_callCoreApiMethod($args, 'itemExists', 'item');
    }

    /**
     * Create an item or update an existing one if one exists by the uuid passed.
     * Note: In the case of an already existing item, any parameters passed whose name
     * begins with an underscore are assumed to be metadata fields to set on the item.
     *
     * @param token Authentication token
     * @param parentid The id of the parent folder. Only required for creating a new item.
     * @param name The name of the item to create
     * @param description (Optional) The description of the item
     * @param uuid (Optional) Uuid of the item. If none is passed, will generate one.
     * @param privacy (Optional) [Public|Private], default will inherit from parent folder
     * @param updatebitstream (Optional) If set, the bitstream's name will be updated
     * simultaneously with the item's name if and only if the item has already
     * existed and its latest revision contains only one bitstream.
     * @return The item object that was created
     */
    public function itemCreate($args)
    {
        return $this->_callCoreApiMethod($args, 'itemCreate', 'item');
    }

    /**
     * Get an item's information
     *
     * @param token (Optional) Authentication token
     * @param id The item id
     * @param head (Optional) only list the most recent revision
     * @return The item object
     */
    public function itemGet($args)
    {
        return $this->_callCoreApiMethod($args, 'itemGet', 'item');
    }

    /**
     * Download an item
     *
     * @param token (Optional) Authentication token
     * @param id The id of the item
     * @param revision (Optional) Revision to download. Defaults to latest revision
     * @return The bitstream(s) in the item
     */
    public function itemDownload($args)
    {
        return $this->_callCoreApiMethod($args, 'itemDownload', 'item');
    }

    /**
     * Delete an item
     *
     * @param token Authentication token
     * @param id The id of the item
     */
    public function itemDelete($args)
    {
        $this->_callCoreApiMethod($args, 'itemDelete', 'item', false);
    }

    /**
     * Get the item's metadata
     *
     * @param token (Optional) Authentication token
     * @param id The id of the item
     * @param revision (Optional) Revision of the item. Defaults to latest revision
     * @return the sought metadata array on success,
     *             will fail if there are no revisions or the specified revision is not found.
     */
    public function itemGetmetadata($args)
    {
        return $this->_callCoreApiMethod($args, 'itemGetmetadata', 'item');
    }

    /**
     * Set a metadata field on an item
     *
     * @param token Authentication token
     * @param itemId The id of the item
     * @param element The metadata element
     * @param value The metadata value for the field
     * @param qualifier (Optional) The metadata qualifier. Defaults to empty string.
     * @param type (Optional) The metadata type (integer constant). Defaults to MIDAS_METADATA_TEXT type (0).
     * @param revision (Optional) Revision of the item. Defaults to latest revision.
     * @return true on success,
     *              will fail if there are no revisions or the specified revision is not found.
     */
    public function itemSetmetadata($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'itemId', 'id');

        return $this->_callCoreApiMethod($args, 'itemSetmetadata', 'item');
    }

    /**
     * Set multiple metadata fields on an item, requires specifying the number of
     * metadata tuples to add.
     *
     * @param token Authentication token
     * @param itemid The id of the item
     * @param revision (Optional) Item Revision number to set metadata on, defaults to latest revision.
     * @param count The number of metadata tuples that will be set.  For every one
     * of these metadata tuples there will be the following set of params with counters
     * at the end of each param name, from 1..<b>count</b>, following the example
     * using the value <b>i</b> (i.e., replace <b>i</b> with values 1..<b>count</b>)
     * (<b>element_i</b>, <b>value_i</b>, <b>qualifier_i</b>, <b>type_i</b>).
     *
     * @param element_i metadata element for tuple i
     * @param value_i   metadata value for the field, for tuple i
     * @param qualifier_i (Optional) metadata qualifier for tuple i. Defaults to empty string.
     * @param type_i (Optional) metadata type (integer constant). Defaults to MIDAS_METADATA_TEXT type (0).
     * @return true on success,
     *              will fail if there are no revisions or the specified revision is not found.
     */
    public function itemSetmultiplemetadata($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'itemid', 'id');

        return $this->_callCoreApiMethod($args, 'itemSetmultiplemetadata', 'item');
    }

    /**
     * Delete a metadata tuple (element, qualifier, type) from a specific item revision,
     * defaults to the latest revision of the item.
     *
     * @param token Authentication token
     * @param itemid The id of the item
     * @param element The metadata element
     * @param qualifier (Optional) The metadata qualifier. Defaults to empty string.
     * @param type (Optional) metadata type (integer constant).
     * Defaults to MIDAS_METADATA_TEXT (0).
     * @param revision (Optional) Revision of the item. Defaults to latest revision.
     * @return true on success,
     *              false if the metadata was not found on the item revision,
     *              will fail if there are no revisions or the specified revision is not found.
     */
    public function itemDeletemetadata($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'itemid', 'id');

        return $this->_callCoreApiMethod($args, 'itemDeletemetadata', 'item');
    }

    /**
     * Deletes all metadata associated with a specific item revision;
     * defaults to the latest revision of the item;
     * pass <b>revision</b>=<b>all</b> to delete all metadata from all revisions.
     *
     * @param token Authentication token
     * @param itemid The id of the item
     * @param revision (Optional)
     * Revision of the item. Defaults to latest revision; pass <b>all</b> to delete all metadata from all revisions.
     * @return true on success,
     *              will fail if there are no revisions or the specified revision is not found.
     */
    public function itemDeletemetadataAll($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'itemid', 'id');

        return $this->_callCoreApiMethod($args, 'itemDeletemetadataAll', 'item');
    }

    /**
     * Duplicate an item to the destination folder
     *
     * @param token Authentication token
     * @param id The id of the item
     * @param dstfolderid The id of destination folder where the item is duplicated to
     * @return The item object that was created
     */
    public function itemDuplicate($args)
    {
        return $this->_callCoreApiMethod($args, 'itemDuplicate', 'item');
    }

    /**
     * Share an item to the destination folder
     *
     * @param token Authentication token
     * @param id The id of the item
     * @param dstfolderid The id of destination folder where the item is shared to
     * @return The item object
     */
    public function itemShare($args)
    {
        return $this->_callCoreApiMethod($args, 'itemShare', 'item');
    }

    /**
     * List the permissions on an item, requires Admin access to the item.
     *
     * @param item_id The id of the item
     * @return A list with three keys: privacy, user, group; privacy will be the
     *           item's privacy string [Public|Private]; user will be a list of
     *           (user_id, policy, email); group will be a list of (group_id, policy, name).
     *           policy for user and group will be a policy string [Admin|Write|Read].
     */
    public function itemListPermissions($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'item_id', 'id');

        return $this->_callCoreApiMethod($args, 'itemListPermissions', 'item');
    }

    /**
     * Move an item from the source folder to the desination folder
     *
     * @param token Authentication token
     * @param id The id of the item
     * @param srcfolderid The id of source folder where the item is located
     * @param dstfolderid The id of destination folder where the item is moved to
     * @return The item object
     */
    public function itemMove($args)
    {
        return $this->_callCoreApiMethod($args, 'itemMove', 'item');
    }

    /**
     * Return all items
     *
     * @param token (Optional) Authentication token
     * @param name The name of the item to search by
     * @return A list of all items with the given name
     */
    public function itemSearchbyname($args)
    {
        return $this->_callCoreApiMethod($args, 'itemSearch', 'item');
    }

    /**
     * Return all items with a given name and parent folder id
     *
     * @param token (Optional) Authentication token
     * @param name The name of the item to search by
     * @param folderId The id of the parent folder to search by
     * @return A list of all items with the given name and parent folder id
     */
    public function itemSearchbynameandfolder($args)
    {
        return $this->_callCoreApiMethod($args, 'itemSearch', 'item');
    }

    /**
     * Return all items with a given name and parent folder name
     *
     * @param token (Optional) Authentication token
     * @param name The name of the item to search by
     * @param folderName The name of the parent folder to search by
     * @return A list of all items with the given name and parent folder name
     */
    public function itemSearchbynameandfoldername($args)
    {
        return $this->_callCoreApiMethod($args, 'itemSearch', 'item');
    }

    /**
     * Add an itempolicygroup to an item with the passed in group and policy;
     * if an itempolicygroup exists for that group and item, it will be replaced
     * with the passed in policy.
     *
     * @param item_id The id of the item.
     * @param group_id The id of the group.
     * @param policy Desired policy status, one of [Admin|Write|Read].
     * @return success = true on success.
     */
    public function itemAddPolicygroup($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'item_id', 'id');

        return $this->_callCoreApiMethod($args, 'itemAddPolicygroup', 'item');
    }

    /**
     * Remove a itempolicygroup from a item with the passed in group if the
     * itempolicygroup exists.
     *
     * @param item_id The id of the item.
     * @param group_id The id of the group.
     * @return success = true on success.
     */
    public function itemRemovePolicygroup($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'item_id', 'id');

        return $this->_callCoreApiMethod($args, 'itemRemovePolicygroup', 'item');
    }

    /**
     * Add a itempolicyuser to an item with the passed in user and policy;
     * if an itempolicyuser exists for that user and item, it will be replaced
     * with the passed in policy.
     *
     * @param item_id The id of the item.
     * @param user_id The id of the targeted user to create the policy for.
     * @param policy Desired policy status, one of [Admin|Write|Read].
     * @return success = true on success.
     */
    public function itemAddPolicyuser($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'item_id', 'id');

        return $this->_callCoreApiMethod($args, 'itemAddPolicyuser', 'item');
    }

    /**
     * Remove an itempolicyuser from an item with the passed in user if the
     * itempolicyuser exists.
     *
     * @param item_id The id of the item.
     * @param user_id The id of the target user.
     * @return success = true on success.
     */
    public function itemRemovePolicyuser($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'item_id', 'id');

        return $this->_callCoreApiMethod($args, 'itemRemovePolicyuser', 'item');
    }

    /**
     * Return a list of top level folders belonging to the user
     *
     * @param token Authentication token
     * @return List of the user's top level folders
     */
    public function userFolders($args)
    {
        return $this->_callCoreApiMethod($args, 'userFolders', 'user');
    }

    /**
     * Returns the user's default API key given their username and password.
     * POST method required.
     *
     * @param email The user's email
     * @param password The user's password
     * @return Array with a single key, 'apikey', whose value is the user's default api key
     */
    public function userApikeyDefault($args)
    {
        return $this->_callCoreApiMethod($args, 'userApikeyDefault', 'system');
    }

    /**
     * Returns a portion or the entire set of public users based on the limit var.
     *
     * @param limit The maximum number of users to return
     * @return the list of users
     */
    public function userList($args)
    {
        return $this->_callCoreApiMethod($args, 'userList', 'user');
    }

    /**
     * Returns a user either by id or by email or by first name and last name.
     *
     * @param user_id The id of the user desired (ignores firstname and lastname)
     * @param email The email of the user desired
     * @param firstname The first name of the desired user (use with last name)
     * @param lastname The last name of the desired user (use with first name)
     * @return The user corresponding to the user_id or first and last name
     */
    public function userGet($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'user_id', 'id', false);
        if (array_key_exists('id', $args)) {
            return $this->_callCoreApiMethod($args, 'userGet', 'user');
        } else {
            return $this->_callCoreApiMethod($args, 'userSearch', 'user');
        }
    }

    /**
     * Fetch the information about a bitstream
     *
     * @param token (Optional) Authentication token
     * @param id The id of the bitstream
     * @return Bitstream dao
     */
    public function bitstreamGet($args)
    {
        return $this->_callCoreApiMethod($args, 'bitstreamGet', 'bitstream');
    }

    /**
     * Download a bitstream either by its id or by a checksum.
     * Either an id or checksum parameter is required.
     *
     * @param token (Optional) Authentication token
     * @param id (Optional) The id of the bitstream
     * @param checksum (Optional) The checksum of the bitstream
     * @param name (Optional) Alternate filename to download as
     * @param offset (Optional) The download offset in bytes (used for resume)
     */
    public function bitstreamDownload($args)
    {
        return $this->_callCoreApiMethod($args, 'bitstreamDownload', 'bitstream');
    }

    /**
     * Count the bitstreams under a containing resource. Uses latest revision of each item.
     *
     * @param token (Optional) Authentication token
     * @param uuid The uuid of the containing resource
     * @return array(size=>total_size_in_bytes, count=>total_number_of_files)
     */
    public function bitstreamCount($args)
    {
        return $this->_callCoreApiMethod($args, 'bitstreamCount', 'bitstream');
    }

    /**
     * Change the properties of a bitstream. Requires write access to the containing item.
     *
     * @param token Authentication token
     * @param id The id of the bitstream to edit
     * @param name (optional) New name for the bitstream
     * @param mimetype (optional) New MIME type for the bitstream
     * @return The bitstream dao
     */
    public function bitstreamEdit($args)
    {
        return $this->_callCoreApiMethod($args, 'bitstreamEdit', 'bitstream');
    }

    /**
     * Remove orphaned resources in the database.  Must be admin to use.
     */
    public function adminDatabaseCleanup($args)
    {
        return $this->_callCoreApiMethod($args, 'adminDatabaseCleanup', 'system');
    }

    /**
     * Delete a bitstream. Requires admin privileges on the containing item.
     *
     * @param token Authentication token
     * @param id The id of the bitstream to delete
     */
    public function bitstreamDelete($args)
    {
        $this->_callCoreApiMethod($args, 'bitstreamDelete', 'bitstream', false);
    }

    /**
     * Get the metadata types stored in the system
     */
    public function metadataTypesList()
    {
        return $this->_callCoreApiMethod(array(), 'metadataTypesList', 'system');
    }

    /**
     * Get the metadata elements stored in the system for a given metadata type.
     * If the type name is specified, it will be used instead of the index.
     *
     * @param type the metadata type index
     * @param typename (optional) the metadata type name
     */
    public function metadataElementsList($args)
    {
        return $this->_callCoreApiMethod($args, 'metadataElementsList', 'system');
    }

    /**
     * Get the metadata qualifiers stored in the system for a given metadata type
     * and element. If the type name is specified, it will be used instead of the
     * type.
     *
     * @param type the metadata type index
     * @param element the metadata element under which the qualifier is collated
     * @param typename (optional) the metadata type name
     */
    public function metadataQualifiersList($args)
    {
        return $this->_callCoreApiMethod($args, 'metadataQualifiersList', 'system');
    }

    /**
     * Add a user to a group, returns 'success' => 'true' on success, requires
     * admin privileges on the community associated with the group.
     *
     * @param group_id the group to add the user to
     * @param user_id the user to add to the group
     * @return success = true on success.
     */
    public function groupAddUser($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'group_id', 'id');

        return $this->_callCoreApiMethod($args, 'groupAddUser', 'group');
    }

    /**
     * Remove a user from a group, returns 'success' => 'true' on success, requires
     * admin privileges on the community associated with the group.
     *
     * @param group_id the group to remove the user from
     * @param user_id the user to remove from the group
     * @return success = true on success.
     */
    public function groupRemoveUser($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'group_id', 'id');

        return $this->_callCoreApiMethod($args, 'groupRemoveUser', 'group');
    }

    /**
     * add a group associated with a community, requires admin privileges on the
     * community.
     *
     * @param community_id the id of the community the group will associate with
     * @param name the name of the new group
     * @return group_id of the newly created group on success.
     */
    public function groupAdd($args)
    {
        return $this->_callCoreApiMethod($args, 'groupAdd', 'group');
    }

    /**
     * remove a group associated with a community, requires admin privileges on the
     * community.
     *
     * @param group_id the id of the group to be removed
     * @return success = true on success.
     */
    public function groupRemove($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'group_id', 'id');

        return $this->_callCoreApiMethod($args, 'groupRemove', 'group');
    }

    /**
     * list the users for a group, requires admin privileges on the community
     * associated with the group
     *
     * @param group_id id of group
     * @return array users => a list of user ids mapped to a two element list of
     *               user firstname and lastname
     */
    public function groupListUsers($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'group_id', 'id');

        return $this->_callCoreApiMethod($args, 'groupListUsers', 'group');
    }

    /**
     * list the groups for a community, requires admin privileges on the community
     *
     * @param community_id id of community
     * @return array groups => a list of group ids mapped to group names
     */
    public function communityListGroups($args)
    {
        /** @var ApihelperComponent $ApihelperComponent */
        $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
        $ApihelperComponent->renameParamKey($args, 'community_id', 'id');

        return $this->_callCoreApiMethod($args, 'communityListGroups', 'community');
    }
}
