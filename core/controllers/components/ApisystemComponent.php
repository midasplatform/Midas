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

/** These are the implementations of the web api methods for system */
class ApisystemComponent extends AppComponent
{
    /**
     * Get the server version.
     *
     * @path /system/version
     * @http GET
     * @return Server version in the form {major}.{minor}.{patch}
     *
     * @param array $args parameters
     */
    public function version($args)
    {
        $version = UtilityComponent::getCurrentModuleVersion('core');
        if ($version === false) {
            throw new Zend_Exception('Core version is undefined.');
        }

        return array('version' => $version);
    }

    /**
     * Get the enabled modules on the server.
     *
     * @path /system/module
     * @http GET
     * @return List of enabled modules on the server
     *
     * @param array $args parameters
     */
    public function modulesList($args)
    {
        return array('modules' => Zend_Registry::get('modulesEnable'));
    }

    /**
     * List all available web api resources on the server.
     *
     * @path /system/resource
     * @http GET
     * @return List of api resources names and their corresponding url
     *
     * @param array $args parameters
     */
    public function resourcesList($args)
    {
        $data = array();
        /** @var ApidocsComponent $docsComponent */
        $docsComponent = MidasLoader::loadComponent('Apidocs');
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $baseUrl = $request->getScheme().'://'.$request->getHttpHost().$request->getBaseUrl();
        $apiroot = $baseUrl.'/rest';
        $resources = $docsComponent->getEnabledResources();
        foreach ($resources as $resource) {
            if (strpos($resource, '/') > 0) {
                $resource = '/'.$resource;
            }
            $data[$resource] = $apiroot.$resource;
        }

        return array('resources' => $data);
    }

    /**
     * Get the server information including version, modules enabled,
     * and available resources.
     *
     * @path /system/info
     * @http GET
     * @return Server information
     *
     * @param array $args parameters
     */
    public function info($args)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $serverFields = array(
            'name' => $settingModel->getValueByName('title'),
            'description' => $settingModel->getValueByName('description'),
        );

        return array_merge(
            $this->version($args),
            $serverFields,
            $this->modulesList($args),
            $this->resourcesList($args)
        );
    }

    /**
     * Login as a user using a web API key.
     *
     * @path /system/login
     * @http GET
     * @param appname The application name
     * @param email The user email
     * @param apikey The API key corresponding to the given application name
     * @return A web api token that will be valid for a set duration
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function login($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('email', 'appname', 'apikey'));

        $data['token'] = '';
        $email = $args['email'];
        $appname = $args['appname'];
        $apikey = $args['apikey'];

        /** @var UserapiModel $userapiModel */
        $userapiModel = MidasLoader::loadModel('Userapi');
        $tokenDao = $userapiModel->getToken($email, $apikey, $appname);
        if (empty($tokenDao)) {
            throw new Exception('Unable to authenticate. Please check credentials.', MIDAS_INVALID_PARAMETER);
        }
        $userDao = $tokenDao->getUserapi()->getUser();
        $notifications = Zend_Registry::get('notifier')->callback(
            'CALLBACK_API_AUTH_INTERCEPT',
            array('user' => $userDao, 'tokenDao' => $tokenDao)
        );
        foreach ($notifications as $value) {
            if ($value['response']) {
                return $value['response'];
            }
        }
        $data['token'] = $tokenDao->getToken();

        return $data;
    }

    /**
     * Returns the user's default API key given their username and password.
     *
     * @path /system/defaultapikey
     * @http POST
     * @param email The user's email
     * @param password The user's password
     * @return Array with a single key, 'apikey', whose value is the user's default api key
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function userApikeyDefault($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('email', 'password'));
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request->isPost()) {
            throw new Exception('POST method required', MIDAS_HTTP_ERROR);
        }
        $email = $args['email'];
        $password = $args['password'];

        try {
            $notifications = Zend_Registry::get('notifier')->callback(
                'CALLBACK_CORE_AUTHENTICATION',
                array('email' => $email, 'password' => $password)
            );
        } catch (Zend_Exception $exc) {
            throw new Exception('Login failed', MIDAS_INVALID_PARAMETER);
        }
        $userDao = false;
        foreach ($notifications as $notification) {
            if ($notification) {
                $userDao = $notification;
                break;
            }
        }
        $hasAuthenticationModule = $userDao !== false;

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');

        /** @var UserapiModel $userApiModel */
        $userApiModel = MidasLoader::loadModel('Userapi');
        if ($userDao === false) {
            $userDao = $userModel->getByEmail($email);
            if ($userDao === false) {
                throw new Exception('Login failed', MIDAS_INVALID_PARAMETER);
            }
        }

        $prefix = Zend_Registry::get('configGlobal')->get('password_prefix');
        if ($hasAuthenticationModule || $userModel->hashExists(
                hash($userDao->getHashAlg(), $prefix.$userDao->getSalt().$password)
            )
        ) {
            if ($userDao->getSalt() == '') {
                $userModel->convertLegacyPasswordHash($userDao, $password);
            }
            $userApiDao = $userApiModel->getByAppAndEmail('Default', $email);
            if ($userApiDao === false) {
                throw new Exception('User has no default API key', MIDAS_INVALID_PARAMETER);
            }

            return array('apikey' => $userApiDao->getApikey());
        }

        throw new Exception('Login failed', MIDAS_INVALID_PARAMETER);
    }

    /**
     * Create a link bitstream. POST is required.
     *
     * @path /system/link
     * @http POST
     * @param useSession (Optional) Authenticate using the current Midas session
     * @param token (Optional) Authentication token
     * @param folderid The id of the folder in which to create a new item that
     * will contain the link. The new item will have the same name as
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
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function linkCreate($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request->isPost()) {
            throw new Exception('POST method required', MIDAS_HTTP_ERROR);
        }

        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('folderid', 'url'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('Anonymous users may not create a link', MIDAS_INVALID_POLICY);
        }

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');
        $folder = $folderModel->load($args['folderid']);
        if ($folder === false) {
            throw new Exception('Folder corresponding to folderid does not exist', MIDAS_INVALID_PARAMETER);
        }
        if (!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception('Invalid policy or folderid', MIDAS_INVALID_POLICY);
        }
        if (!filter_var($args['url'], FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL', MIDAS_INVALID_PARAMETER);
        }
        $itemname = isset($args['itemname']) ? $args['itemname'] : $args['url'];
        if (isset($args['length']) && filter_var(
                $args['length'],
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 0))
            ) === false
        ) {
            throw new Exception('Invalid length', MIDAS_INVALID_PARAMETER);
        }
        $length = isset($args['length']) ? $args['length'] : 0;
        $checksum = isset($args['checksum']) ? $args['checksum'] : ' ';

        /** @var UploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Upload');
        $item = $uploadComponent->createLinkItem($userDao, $itemname, $args['url'], $folder, $length, $checksum);
        if (!$item) {
            throw new Exception('Link creation failed', MIDAS_INTERNAL_ERROR);
        }

        return $item->toArray();
    }

    /**
     * Generate a unique upload token.  Either <b>itemid</b> or <b>folderid</b> is required,
     * but both are not allowed.
     *
     * @path /system/uploadtoken
     * @http GET
     * @param useSession (Optional) Authenticate using the current Midas session
     * @param token (Optional) Authentication token
     * @param itemid (Optional)
     * The id of the item to upload into.
     * @param folderid (Optional)
     * The id of the folder to create a new item in and then upload to.
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
     * @param create_additional_revision (Optional) When a <b>checksum</b> is passed and
     * the server already has the file, by default a reference to the existing
     * bitstream will be added to the latest revision. By setting
     * <b>create_additional_revision</b> to true, a new revision will be created.
     * @return An upload token that can be used to upload a file.
     *            If <b>folderid</b> is passed instead of <b>itemid</b>, a new item will be created
     *            in that folder, but the id of the newly created item will not be
     *            returned.  If the id of the newly created item is needed,
     *            then call the <b>/item (POST)</b> api instead.
     *            If <b>checksum</b> is passed and the token returned is blank, the
     *            server already has this file and there is no need to follow this
     *            call with a call to <b>/system/upload</b>, as the passed in
     *            file will have been added as a bitstream to the item's latest
     *            revision, creating a new revision if one doesn't exist.
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function uploadGeneratetoken($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('filename'));
        if (!array_key_exists('itemid', $args) && !array_key_exists('folderid', $args)
        ) {
            throw new Exception('Parameter itemid or folderid must be defined', MIDAS_INVALID_PARAMETER);
        }
        if (array_key_exists('itemid', $args) && array_key_exists('folderid', $args)
        ) {
            throw new Exception('Parameter itemid or folderid must be defined, but not both', MIDAS_INVALID_PARAMETER);
        }

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
        $userDao = $apihelperComponent->getUser($args);
        if (!$userDao) {
            throw new Exception('Anonymous users may not upload', MIDAS_INVALID_POLICY);
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        if (array_key_exists('itemid', $args)) {
            $item = $itemModel->load($args['itemid']);
            if (!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE)) {
                throw new Exception('Invalid policy or itemid', MIDAS_INVALID_POLICY);
            }
        } elseif (array_key_exists('folderid', $args)) {
            /** @var FolderModel $folderModel */
            $folderModel = MidasLoader::loadModel('Folder');
            $folder = $folderModel->load($args['folderid']);
            if ($folder == false) {
                throw new Exception('Parent folder corresponding to folderid doesn\'t exist', MIDAS_INVALID_PARAMETER);
            }
            if (!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE)
            ) {
                throw new Exception('Invalid policy or folderid', MIDAS_INVALID_POLICY);
            }
            // create a new item in this folder
            $itemname = isset($args['itemname']) ? $args['itemname'] : $args['filename'];
            $description = isset($args['itemdescription']) ? $args['itemdescription'] : '';
            $item = $itemModel->createItem($itemname, $description, $folder);
            if ($item === false) {
                throw new Exception('Create new item failed', MIDAS_INTERNAL_ERROR);
            }
            $itemModel->copyParentPolicies($item, $folder);

            /** @var ItempolicyuserModel $itempolicyuserModel */
            $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
            $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);
        }

        if (array_key_exists('checksum', $args)) {
            // If we already have a bitstream with this checksum, create a reference and return blank token
            /** @var BitstreamModel $bitstreamModel */
            $bitstreamModel = MidasLoader::loadModel('Bitstream');
            $existingBitstream = $bitstreamModel->getByChecksum($args['checksum']);
            if ($existingBitstream) {
                // User must have read access to the existing bitstream if they are circumventing the upload.
                // Otherwise an attacker could spoof the checksum and read a private bitstream with a known checksum.
                if ($itemModel->policyCheck(
                    $existingBitstream->getItemrevision()->getItem(),
                    $userDao,
                    MIDAS_POLICY_READ
                )
                ) {
                    $createAdditionalRevision = isset($args['create_additional_revision']) ? $args['create_additional_revision'] : false;
                    $revision = $itemModel->getLastRevision($item);

                    // Do not create an additional revision if last revision has one bitstream and checksum matches.
                    if ($createAdditionalRevision && $revision) {
                        $bitstreams = $revision->getBitstreams();
                        if (count($bitstreams) === 1 && $args['checksum'] == $bitstreams[0]->getChecksum()) {
                            return array('token' => '');
                        }
                    }

                    if ($revision === false || $createAdditionalRevision) {
                        // Create a new revision if none exists yet or if the user explicitly asks to create a new revision when
                        // a bitstream with the same checksum was found.
                        Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH.'/core/models/dao');
                        $revision = new ItemRevisionDao();
                        if ($createAdditionalRevision === false) {
                            $revision->setChanges('Initial revision');
                        }
                        $revision->setUser_id($userDao->getKey());
                        $revision->setDate(date('Y-m-d H:i:s'));
                        $revision->setLicenseId(null);
                        $itemModel->addRevision($item, $revision);
                    }

                    $siblings = $revision->getBitstreams();
                    foreach ($siblings as $sibling) {
                        if ($sibling->getName() == $args['filename']) {
                            // already have a file with this name. don't add new record.
                            return array('token' => '');
                        }
                    }
                    Zend_Loader::loadClass('BitstreamDao', BASE_PATH.'/core/models/dao');
                    $bitstream = new BitstreamDao();
                    $bitstream->setChecksum($args['checksum']);
                    $bitstream->setName($args['filename']);
                    $bitstream->setSizebytes($existingBitstream->getSizebytes());
                    $bitstream->setPath($existingBitstream->getPath());
                    $bitstream->setAssetstoreId($existingBitstream->getAssetstoreId());
                    $bitstream->setMimetype($existingBitstream->getMimetype());

                    /** @var ItemRevisionModel $revisionModel */
                    $revisionModel = MidasLoader::loadModel('ItemRevision');
                    $revisionModel->addBitstream($revision, $bitstream);

                    return array('token' => '');
                }
            }
        }
        // we don't already have this content, so create the token
        /** @var HttpuploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Httpupload');
        $apiSetup = $apihelperComponent->getApiSetup();
        $uploadComponent->setTestingMode(Zend_Registry::get('configGlobal')->get('environment', 'production') === 'testing');

        return $uploadComponent->generateToken($args, $userDao->getKey().'/'.$item->getKey());
    }

    /**
     * Upload a file to the server. PUT or POST is required.
     * Will add the file as a bitstream to the item that was specified when
     * generating the upload token in a new revision to that item, unless
     * <b>revision</b> param is set.
     *
     * @path /system/upload
     * @http POST
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
     * @param license (Optional) The license for the revision.
     * @return The item information of the item created or changed.
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function uploadPerform($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('uploadtoken', 'filename', 'length'));
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request->isPost() && !$request->isPut()) {
            throw new Exception('POST or PUT method required', MIDAS_HTTP_ERROR);
        }

        list($userid, $itemid) = explode('/', $args['uploadtoken']);

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');

        $userDao = $userModel->load($userid);
        if (!$userDao) {
            throw new Exception('Invalid user id from upload token', MIDAS_INVALID_PARAMETER);
        }
        $item = $itemModel->load($itemid);

        if ($item == false) {
            throw new Exception('Unable to find item', MIDAS_INVALID_PARAMETER);
        }
        if (!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception('Permission error', MIDAS_INVALID_POLICY);
        }

        if (array_key_exists('revision', $args)) {
            if (strtolower($args['revision']) == 'head') {
                $revision = $itemModel->getLastRevision($item);
                // if no revision exists, it will be created later
            } else {
                $revision = $itemModel->getRevision($item, $args['revision']);
                if ($revision == false) {
                    throw new Exception('Unable to find revision', MIDAS_INVALID_PARAMETER);
                }
            }
        }

        $mode = array_key_exists('mode', $args) ? $args['mode'] : 'stream';

        /** @var HttpuploadComponent $httpUploadComponent */
        $httpUploadComponent = MidasLoader::loadComponent('Httpupload');
        $apiSetup = $apihelperComponent->getApiSetup();
        $httpUploadComponent->setTestingMode(Zend_Registry::get('configGlobal')->get('environment', 'production') === 'testing');

        if (array_key_exists('testingmode', $args)) {
            $httpUploadComponent->setTestingMode(true);
            if (!array_key_exists('localinput', $args)) {
                $args['localinput'] = UtilityComponent::getTempDirectory().'/'.$args['filename'];
            }
        }

        // Use the Httpupload component to handle the actual file upload
        if ($mode == 'stream') {
            $result = $httpUploadComponent->process($args);

            $filename = $result['filename'];
            $filePath = $result['path'];
            $fileSize = (int) $result['size'];
            $fileChecksum = $result['md5'];
        } elseif ($mode == 'multipart') {
            if (!array_key_exists('file', $args) || !array_key_exists('file', $_FILES)
            ) {
                throw new Exception('Parameter file is not defined', MIDAS_INVALID_PARAMETER);
            }
            $file = $_FILES['file'];
            $filename = $file['name'];
            $filePath = $file['tmp_name'];
            $fileSize = (int) $file['size'];
            $fileChecksum = '';
        } else {
            throw new Exception('Invalid upload mode', MIDAS_INVALID_PARAMETER);
        }

        // get the parent folder of this item and notify the callback
        // this is made more difficult by the fact the items can have many parents,
        // just use the first in the list.
        $parentFolders = $item->getFolders();
        if (!isset($parentFolders) || !$parentFolders || count($parentFolders) === 0
        ) {
            // this shouldn't happen with any self-respecting item
            throw new Exception('Item does not have a parent folder', MIDAS_INVALID_PARAMETER);
        }
        $firstParent = $parentFolders[0];
        $validations = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_VALIDATE_UPLOAD',
            array(
                'filename' => $filename,
                'size' => $fileSize,
                'path' => $filePath,
                'folderId' => $firstParent->getFolderId(),
            )
        );
        foreach ($validations as $validation) {
            if (!$validation['status']) {
                unlink($filePath);
                throw new Exception($validation['message'], MIDAS_INVALID_POLICY);
            }
        }

        $license = array_key_exists('license', $args) ?  $args['license'] : null;
        $changes = array_key_exists('changes', $args) ? $args['changes'] : '';
        $revisionNumber = null;
        if (isset($revision) && $revision !== false) {
            $revisionNumber = $revision->getRevision();
        }

        /** @var UploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Upload');
        $item = $uploadComponent->createNewRevision(
            $userDao,
            $filename,
            $filePath,
            $changes,
            $item->getKey(),
            $revisionNumber,
            $license,
            $fileChecksum,
            false,
            $fileSize
        );

        if (!$item) {
            throw new Exception('Upload failed', MIDAS_INTERNAL_ERROR);
        }

        return $item->toArray();
    }

    /**
     * Get the size of a partially completed upload.
     *
     * @path /system/uploadoffset
     * @http GET
     * @param uploadtoken The upload token for the file
     * @return The size of the file currently on the server
     *
     * @param array $args parameters
     */
    public function uploadGetoffset($args)
    {
        /** @var HttpuploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Httpupload');

        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apiSetup = $apihelperComponent->getApiSetup();
        $uploadComponent->setTestingMode(Zend_Registry::get('configGlobal')->get('environment', 'production') === 'testing');

        return $uploadComponent->getOffset($args);
    }

    /**
     * Get the metadata qualifiers stored in the system for a given metadata type
     * and element. If the type name is specified, it will be used instead of the
     * type.
     *
     * @path /system/metadataqualifiers
     * @http GET
     * @param type the metadata type index
     * @param element the metadata element under which the qualifier is collated
     * @param typename (Optional) the metadata type name
     * @return Array of metadata qualifiers
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function metadataQualifiersList($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('element'));

        /** @var MetadataModel $metadataModel */
        $metadataModel = MidasLoader::loadModel('Metadata');
        $type = $apihelperComponent->checkMetadataTypeOrName($args, $metadataModel);
        $element = $args['element'];

        return $metadataModel->getMetaDataQualifiers($type, $element);
    }

    /**
     * Get the metadata types stored in the system.
     *
     * @path /system/metadatatypes
     * @http GET
     * @return Array of metadata types
     *
     * @param array $args parameters
     */
    public function metadataTypesList()
    {
        /** @var MetadataModel $metadataModel */
        $metadataModel = MidasLoader::loadModel('Metadata');

        return $metadataModel->getMetadataTypes();
    }

    /**
     * Get the metadata elements stored in the system for a given metadata type.
     * If the type name is specified, it will be used instead of the index.
     *
     * @path /system/metadaelements
     * @http GET
     * @param type the metadata type index
     * @param typename (Optional) the metadata type name
     * @return Array of metadata elements
     *
     * @param array $args parameters
     */
    public function metadataElementsList($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');

        /** @var MetadataModel $metadataModel */
        $metadataModel = MidasLoader::loadModel('Metadata');
        $type = $apihelperComponent->checkMetadataTypeOrName($args, $metadataModel);

        return $metadataModel->getMetadataElements($type);
    }

    /**
     * Remove orphaned resources in the database.  Must be admin to use.
     *
     * @path /system/databasecleanup
     * @http POST
     * @param useSession (Optional) Authenticate using the current Midas session
     * @param token (Optional) Authentication token
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function adminDatabaseCleanup($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $userDao = $apihelperComponent->getUser($args);

        if (!$userDao || !$userDao->isAdmin()) {
            throw new Exception('Only admin users may call this method', MIDAS_INVALID_POLICY);
        }
        foreach (array('Folder', 'Item', 'ItemRevision', 'Bitstream') as $modelName) {
            /** @var BitstreamModel|FolderModel|ItemModel|ItemRevisionModel $model */
            $model = MidasLoader::loadModel($modelName);
            $model->removeOrphans();
        }
    }
}
