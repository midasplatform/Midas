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
   * Pass the args and a list of required parameters.
   * Will throw an exception if a required one is missing.
   */
  private function _validateParams($args, $requiredList)
    {
    foreach($requiredList as $param)
      {
      if(!array_key_exists($param, $args))
        {
        throw new Exception('Parameter '.$param.' is not defined', MIDAS_INVALID_PARAMETER);
        }
      }
    }

  /** Return the user dao */
  private function _getUser($args)
    {
    $authComponent = MidasLoader::loadComponent('Authentication', 'api');
    return $authComponent->getUser($args, $this->userSession->Dao);
    }

  /**
   * Get the server version
   * @return Server version in the form <major>.<minor>.<patch>
   */
  public function version($args)
    {
    return array('version' => Zend_Registry::get('configDatabase')->version);
    }

  /**
   * Get the enabled modules on the server
   * @return List of enabled modules on the server
   */
  public function modulesList($args)
    {
    return array('modules' => array_keys(Zend_Registry::get('configsModules')));
    }

  /**
   * List all available web api methods on the server
   * @return List of api method names and their corresponding documentation
   */
  public function methodsList($args)
    {
    $data = array();
    $data['methods'] = array();

    $apiMethods = Zend_Registry::get('notifier')->callback('CALLBACK_API_HELP', array());
    foreach($apiMethods as $module => $methods)
      {
      foreach($methods as $method)
        {
        $apiMethodName = $module != 'api' ? $module.'.' : '';
        $apiMethodName .= $method['name'];
        $data['methods'][] = array('name' => $apiMethodName, 'help' => $method['help']);
        }
      }
    return $data;
    }

  /**
   * Get the server information including version, modules enabled,
     and available web api methods (names do not include the global prefix)
   * @return Server information
   */
  public function info($args)
    {
    return array_merge($this->version($args),
                       $this->modulesList($args),
                       $this->methodsList($args));
    }

  /**
   * Login as a user using a web api key
   * @param appname The application name
   * @param email The user email
   * @param apikey The api key corresponding to the given application name
   * @return A web api token that will be valid for a set duration
   */
  function login($args)
    {
    $this->_validateParams($args, array('email', 'appname', 'apikey'));

    $data['token'] = '';
    $email = $args['email'];
    $appname = $args['appname'];
    $apikey = $args['apikey'];
    $Api_Userapi = MidasLoader::loadModel('Userapi', 'api');
    $tokenDao = $Api_Userapi->getToken($email, $apikey, $appname);
    if(empty($tokenDao))
      {
      throw new Exception('Unable to authenticate. Please check credentials.', MIDAS_INVALID_PARAMETER);
      }
    $userDao = $tokenDao->getUserapi()->getUser();
    $notifications = Zend_Registry::get('notifier')->callback('CALLBACK_API_AUTH_INTERCEPT', array(
      'user' => $userDao,
      'tokenDao' => $tokenDao));
    foreach($notifications as $module => $value)
      {
      if($value['response'])
        {
        return $value['response'];
        }
      }
    $data['token'] = $tokenDao->getToken();
    return $data;
    }

  /**
   * Get a resource by its UUID
   * @param uuid Universal identifier for the resource
   * @param folder (Optional) If set, will return the folder instead of the community record
   * @return The resource's dao
   */
  function resourceGet($args)
    {
    $this->_validateParams($args, array('uuid'));

    $uuid = $args['uuid'];
    $uuidComponent = MidasLoader::loadComponent('Uuid');
    $resource = $uuidComponent->getByUid($uuid);

    if($resource == false)
      {
      throw new Exception('No resource for the given UUID.', MIDAS_INVALID_PARAMETER);
      }

    if($resource->resourceType == MIDAS_RESOURCE_COMMUNITY && array_key_exists('folder', $args))
      {
      return array('type' => MIDAS_RESOURCE_FOLDER, 'id' => $resource->getFolderId());
      }
    return array('type' => $resource->resourceType, 'id' => $resource->getKey());
    }

  /**
   * Returns a path of uuids from the root folder to the given node
   * @param uuid Unique identifier of the resource
   * @return An ordered list of uuids representing a path from the root node
   */
  function pathFromRoot($args)
    {
    return array_reverse($this->pathToRoot($args));
    }

  /**
   * Returns a path of uuids from the given node to the root node
   * @param uuid Unique identifier of the resource
   * @return An ordered list of uuids representing a path to the root node
   */
  function pathToRoot($args)
    {
    $this->_validateParams($args, array('uuid'));

    $uuidComponent = MidasLoader::loadComponent('Uuid');
    $element = $uuidComponent->getByUid($args['uuid']);

    $return = array();
    $return[] = $element->toArray();

    if($element == false)
      {
      throw new Exception('No resource for the given UUID.', MIDAS_INVALID_PARAMETER);
      }

    if($element instanceof FolderDao)
      {
      $parent = $element->getParent();
      while($parent !== false)
        {
        $return[] = $parent->toArray();
        $parent = $parent->getParent();
        }
      }
    else if($element instanceof ItemDao)
      {
      $owningFolders = $element->getFolders();
      // randomly pick one parent folder
      $parent = $owningFolders[0];
      while($parent !== false)
        {
        $return[] = $parent->toArray();
        $parent = $parent->getParent();
        }
      }
    // community element itself is the root
    else if(!$element instanceof CommunityDao)
      {
      throw new Exception('Should be a folder, an item or a community.', MIDAS_INVALID_PARAMETER);
      }
    return $return;
    }

  /**
   * Search items for the given words
   * @param token (Optional) Authentication token
   * @param search The search query
   * @param folder Parent uuid folder
   * @return An array of matching resources
   */
  function itemSearch($args)
    {
    $this->_validateParams($args, array('search'));
    $userDao = $this->_getUser($args);

    $order = 'view';
    if(isset($args['order']))
      {
      $order = $args['order'];
      }
    $folder = false;
    if(isset($args['folder']))
      {
      $folder = $args['folder'];
      }
    $componentLoader = new MIDAS_ComponentLoader();
    $searchComponent = $componentLoader->loadComponent('Search');
    return $searchComponent->searchItems($userDao, $args['search'], $folder, $order);
    }

  /**
   * Search resources for the given words
   * @param token (Optional) Authentication token
   * @param search The search query
   * @return An array of matching resources
   */
  function resourceSearch($args)
    {
    $this->_validateParams($args, array('search'));
    $userDao = $this->_getUser($args);

    $order = 'view';
    if(isset($args['order']))
      {
      $order = $args['order'];
      }
    $searchComponent = MidasLoader::loadComponent('Search');
    return $searchComponent->searchAll($userDao, $args['search'], $order);
    }

  /**
   * Generate a unique upload token.  Either <b>itemid</b> or <b>folderid</b> is required,
     but both are not allowed.
   * @param token Authentication token.
   * @param itemid
            The id of the item to upload into.
   * @param folderid
            The id of the folder to create a new item in and then upload to.
            The new item will have the same name as <b>filename</b> unless <b>itemname</b>
            is supplied.
   * @param filename The filename of the file you will upload, will be used as the
            bitstream's name and the item's name (unless <b>itemname</b> is supplied).
   * @param itemprivacy (Optional)
            When passing the <b>folderid</b> param, the privacy status of the newly
            created item, Default 'Public', possible values [Public|Private].
   * @param itemdescription (Optional)
            When passing the <b>folderid</b> param, the description of the item,
            if not supplied the item's description will be blank.
   * @param itemname (Optional)
            When passing the <b>folderid</b> param, the name of the newly created item,
            if not supplied, the item will have the same name as <b>filename</b>.
   * @param checksum (Optional) The md5 checksum of the file to be uploaded.
   * @return An upload token that can be used to upload a file.
             If <b>folderid</b> is passed instead of <b>itemid</b>, a new item will be created
             in that folder, but the id of the newly created item will not be
             returned.  If the id of the newly created item is needed,
             then call the <b>midas.item.create</b> method instead.
             If <b>checksum</b> is passed and the token returned is blank, the
             server already has this file and there is no need to follow this
             call with a call to <b>midas.upload.perform</b>, as the passed in
             file will have been added as a bitstream to the item's latest
             revision, creating a new revision if one doesn't exist.
   */
  function uploadGeneratetoken($args)
    {
    $this->_validateParams($args, array('filename'));
    if(!array_key_exists('itemid', $args) && !array_key_exists('folderid', $args))
      {
      throw new Exception('Parameter itemid or folderid must be defined', MIDAS_INVALID_PARAMETER);
      }
    if(array_key_exists('itemid', $args) && array_key_exists('folderid', $args))
      {
      throw new Exception('Parameter itemid or folderid must be defined, but not both', MIDAS_INVALID_PARAMETER);
      }

    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('Anonymous users may not upload', MIDAS_INVALID_POLICY);
      }

    $itemModel = MidasLoader::loadModel('Item');
    if(array_key_exists('itemid', $args))
      {
      $item = $itemModel->load($args['itemid']);
      if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy or itemid', MIDAS_INVALID_POLICY);
        }
      }
    else if(array_key_exists('folderid', $args))
      {
      $folderModel = MidasLoader::loadModel('Folder');
      $folder = $folderModel->load($args['folderid']);
      if($folder == false)
        {
        throw new Exception('Parent folder corresponding to folderid doesn\'t exist', MIDAS_INVALID_PARAMETER);
        }
      if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy or folderid', MIDAS_INVALID_POLICY);
        }
      // create a new item in this folder
      if(isset($args['itemprivacy']))
        {
        $privacy = $args['itemprivacy'];
        if($privacy !== 'Public' && $privacy !== 'Private')
          {
          throw new Exception('itemprivacy should be one of [Public|Private]');
          }
        if($privacy === 'Public')
          {
          $privacy_status = MIDAS_PRIVACY_PUBLIC;
          }
        else
          {
          $privacy_status = MIDAS_PRIVACY_PRIVATE;
          }
        }
      else
        {
        // Public by default
        $privacy_status = MIDAS_PRIVACY_PUBLIC;
        }
      $itemname = isset($args['itemname']) ? $args['itemname'] : $args['filename'];
      $description = isset($args['itemdescription']) ? $args['itemdescription'] : '';
      $item = $itemModel->createItem($itemname, $description, $folder);
      if($item === false)
        {
        throw new Exception('Create new item failed', MIDAS_INTERNAL_ERROR);
        }
      $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
      $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);
      $item->setPrivacyStatus($privacy_status);
      $itemModel->save($item);
      }

    if(array_key_exists('checksum', $args))
      {
      // If we already have a bitstream with this checksum, create a reference and return blank token
      $bitstreamModel = MidasLoader::loadModel('Bitstream');
      $existingBitstream = $bitstreamModel->getByChecksum($args['checksum']);
      if($existingBitstream)
        {
        $revision = $itemModel->getLastRevision($item);

        if($revision == false)
          {
          // Create new revision if none exists yet
          Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH.'/core/models/dao');
          $revision = new ItemRevisionDao();
          $revision->setChanges('Initial revision');
          $revision->setUser_id($userDao->getKey());
          $revision->setDate(date('c'));
          $revision->setLicenseId(null);
          $itemModel->addRevision($item, $revision);
          }

        $siblings = $revision->getBitstreams();
        foreach($siblings as $sibling)
          {
          if($sibling->getName() == $args['filename'])
            {
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
        $revisionModel = MidasLoader::loadModel('ItemRevision');
        $revisionModel->addBitstream($revision, $bitstream);
        return array('token' => '');
        }
      }
    //we don't already have this content, so create the token
    $uploadComponent = MidasLoader::loadComponent('Httpupload');
    $uploadComponent->setTestingMode($this->apiSetup['testing']);
    $uploadComponent->setTmpDirectory($this->apiSetup['tmpDirectory']);
    return $uploadComponent->generateToken($args, $userDao->getKey().'/'.$item->getKey());
    }

  /**
   * Get the size of a partially completed upload
   * @param uploadtoken The upload token for the file
   * @return [offset] The size of the file currently on the server
   */
  function uploadGetoffset($args)
    {
    $uploadComponent = MidasLoader::loadComponent('Httpupload');
    $uploadComponent->setTestingMode($this->apiSetup['testing']);
    $uploadComponent->setTmpDirectory($this->apiSetup['tmpDirectory']);
    return $uploadComponent->getOffset($args);
    }

  /**
   * Upload a file to the server. PUT or POST is required.
     Will add the file as a bitstream to the item that was specified when
     generating the upload token in a new revision to that item, unless
     <b>revision</b> param is set.
   * @param uploadtoken The upload token (see <b>midas.upload.generatetoken</b>).
   * @param filename The name of the bitstream that will be added to the item.
   * @param length The length in bytes of the file being uploaded.
   * @param mode (Optional) Stream or multipart. Default is stream.
   * @param revision (Optional)
            If set, will add a new file into the existing passed in revision number.
            If set to "head", will add a new file into the most recent revision,
            and will create a new revision in this case if none exists.
   * @param changes (Optional)
            The changes field on the affected item revision,
            e.g. for recording what has changed since the previous revision.
   * @param return The item information of the item created or changed.
   */
  function uploadPerform($args)
    {
    $this->_validateParams($args, array('uploadtoken', 'filename', 'length'));
    if(!$this->controller->getRequest()->isPost() && !$this->controller->getRequest()->isPut())
      {
      throw new Exception('POST or PUT method required', MIDAS_HTTP_ERROR);
      }

    list($userid, $itemid, ) = explode('/', $args['uploadtoken']);

    $itemModel = MidasLoader::loadModel('Item');
    $userModel = MidasLoader::loadModel('User');

    $userDao = $userModel->load($userid);
    if(!$userDao)
      {
      throw new Exception('Invalid user id from upload token', MIDAS_INVALID_PARAMETER);
      }
    $item = $itemModel->load($args['itemid']);

    if($item == false)
      {
      throw new Exception('Unable to find item', MIDAS_INVALID_PARAMETER);
      }
    if(!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception('Permission error', MIDAS_INVALID_POLICY);
      }

    if(array_key_exists('revision', $args))
      {
      if(strtolower($args['revision']) == 'head')
        {
        $revision = $itemModel->getLastRevision($item);
        // if no revision exists, it will be created later
        }
      else
        {
        $revision = $itemModel->getRevision($item, $args['revision']);
        if($revision == false)
          {
          throw new Exception('Unable to find revision', MIDAS_INVALID_PARAMETER);
          }
        }
      }

    $mode = array_key_exists('mode', $args) ? $args['mode'] : 'stream';

    $httpUploadComponent = MidasLoader::loadComponent('Httpupload');
    $httpUploadComponent->setTestingMode($this->apiSetup['testing']);
    $httpUploadComponent->setTmpDirectory($this->apiSetup['tmpDirectory']);

    if(array_key_exists('testingmode', $args))
      {
      $httpUploadComponent->setTestingMode(true);
      $args['localinput'] = $this->apiSetup['tmpDirectory'].'/'.$args['filename'];
      }

    // Use the Httpupload component to handle the actual file upload
    if($mode == 'stream')
      {
      $result = $httpUploadComponent->process($args);

      $filename = $result['filename'];
      $filepath = $result['path'];
      $filesize = $result['size'];
      $filemd5 = $result['md5'];
      }
    else if($mode == 'multipart')
      {
      if(!array_key_exists('file', $args) || !array_key_exists('file', $_FILES))
        {
        throw new Exception('Parameter file is not defined', MIDAS_INVALID_PARAMETER);
        }
      $file = $_FILES['file'];

      $filename = $file['name'];
      $filepath = $file['tmp_name'];
      $filesize = $file['size'];
      $filemd5 = '';
      }
    else
      {
      throw new Exception('Invalid upload mode', MIDAS_INVALID_PARAMETER);
      }

    // get the parent folder of this item and notify the callback
    // this is made more difficult by the fact the items can have many parents,
    // just use the first in the list.
    $parentFolders = $item->getFolders();
    if(!isset($parentFolders) || !$parentFolders || sizeof($parentFolders) === 0)
      {
      // this shouldn't happen with any self-respecting item
      throw new Exception('Item does not have a parent folder', MIDAS_INVALID_PARAMETER);
      }
    $firstParent = $parentFolders[0];
    $validations = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_VALIDATE_UPLOAD',
                                                            array('filename' => $filename,
                                                                  'size' => $filesize,
                                                                  'path' => $filepath,
                                                                  'folderId' => $firstParent->getFolderId()));
    foreach($validations as $validation)
      {
      if(!$validation['status'])
        {
        unlink($filepath);
        throw new Exception($validation['message'], MIDAS_INVALID_POLICY);
        }
      }
    $uploadComponent = MidasLoader::loadComponent('Upload');
    $license = null;
    $changes = array_key_exists('changes', $args) ? $args['changes'] : '';
    $revisionNumber = null;
    if(isset($revision) && $revision !== false)
      {
      $revisionNumber = $revision->getRevision();
      }
    $item = $uploadComponent->createNewRevision($userDao, $filename, $filepath, $changes, $item->getKey(), $revisionNumber, $license, $filemd5);

    if(!$item)
      {
      throw new Exception('Upload failed', MIDAS_INTERNAL_ERROR);
      }
    return $item->toArray();
    }

  /**
   * helper method to validate passed in community privacy status params and
   * map them to valid community privacy codes.
   * @param string $privacyStatus, should be 'Private' or 'Public'
   * @return valid community privacy code
   */
  private function _getValidCommunityPrivacyCode($privacyStatus)
    {
    if($privacyStatus !== 'Public' && $privacyStatus !== 'Private')
      {
      throw new Exception('privacy should be one of [Public|Private]', MIDAS_INVALID_PARAMETER);
      }
    if($privacyStatus === 'Public')
      {
      $privacyCode = MIDAS_COMMUNITY_PUBLIC;
      }
    else
      {
      $privacyCode = MIDAS_COMMUNITY_PRIVATE;
      }
    return $privacyCode;
    }


  /**
   * Create a new community or update an existing one using the uuid
   * @param token Authentication token
   * @param name The community name
   * @param description (Optional) The community description
   * @param uuid (Optional) Uuid of the community. If none is passed, will generate one.
   * @param privacy (Optional) Default 'Public', possible values [Public|Private].
   * @param canjoin (Optional) Default 'Everyone'.
   * @return The community dao that was created
   */
  function communityCreate($args)
    {
    $this->_validateParams($args, array('name'));
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', MIDAS_INVALID_POLICY);
      }

    $name = $args['name'];
    $uuid = isset($args['uuid']) ? $args['uuid'] : '';

    $uuidComponent = MidasLoader::loadComponent('Uuid');
    $communityModel = MidasLoader::loadModel('Community');
    $record = false;
    if(!empty($uuid))
      {
      $record = $uuidComponent->getByUid($uuid);
      }
    if($record != false && $record instanceof CommunityDao)
      {
      if(!$communityModel->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
        }
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        $privacyCode = $this->_getValidCommunityPrivacyCode($args['privacy']);
        $communityModel->setPrivacy($record, $privacyCode, $userDao);
        }
      if(isset($args['canjoin']))
        {
        $record->setCanJoin($args['canjoin']);
        }
      $communityModel->save($record);
      return $record->toArray();
      }
    else
      {
      if(!$userDao->isAdmin())
        {
        throw new Exception('Only admins can create communities', MIDAS_INVALID_POLICY);
        }
      $description = '';
      $privacy = MIDAS_COMMUNITY_PUBLIC;
      $canJoin = MIDAS_COMMUNITY_CAN_JOIN;
      if(isset($args['description']))
        {
        $description = $args['description'];
        }
      if(isset($args['privacy']))
        {
        $privacy = $this->_getValidCommunityPrivacyCode($args['privacy'], $userDao);
        }
      if(isset($args['canjoin']))
        {
        $canJoin = $args['canjoin'];
        }
      $communityDao = $communityModel->createCommunity($name, $description, $privacy, $userDao, $canJoin, $uuid);

      if($communityDao === false)
        {
        throw new Exception('Create community failed', MIDAS_INTERNAL_ERROR);
        }

      return $communityDao->toArray();
      }
    }

  /**
   * Get a community's information based on the id OR name
   * @param token (Optional) Authentication token
   * @param id The id of the community
   * @param name the name of the community
   * @return The community information
   */
  function communityGet($args)
    {
    $hasId = array_key_exists('id', $args);
    $hasName = array_key_exists('name', $args);

    $userDao = $this->_getUser($args);

    $communityModel = MidasLoader::loadModel('Community');
    if($hasId)
      {
      $community = $communityModel->load($args['id']);
      }
    else if($hasName)
      {
      $community = $communityModel->getByName($args['name']);
      }
    else
      {
      throw new Exception('Parameter id or name is not defined', MIDAS_INVALID_PARAMETER);
      }

    if($community === false || !$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This community doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    return $community->toArray();
    }

  /**
   * Get the immediate children of a community (non-recursive)
   * @param token (Optional) Authentication token
   * @param id The id of the community
   * @return The folders in the community
   */
  function communityChildren($args)
    {
    $this->_validateParams($args, array('id'));

    $id = $args['id'];

    $communityModel = MidasLoader::loadModel('Community');
    $folderModel = MidasLoader::loadModel('Folder');
    $community = $communityModel->load($id);
    if(!$community)
      {
      throw new Exception('Invalid community id', MIDAS_INVALID_PARAMETER);
      }
    $folder = $folderModel->load($community->getFolderId());

    $userDao = $this->_getUser($args);
    try
      {
      $folders = $folderModel->getChildrenFoldersFiltered($folder, $userDao);
      }
    catch(Exception $e)
      {
      throw new Exception($e->getMessage(), MIDAS_INTERNAL_ERROR);
      }

    return array('folders' => $folders);
    }

  /**
   * Return a list of all communities visible to a user
   * @param token (Optional) Authentication token
   * @return A list of all communities
   */
  function communityList($args)
    {
    $userDao = $this->_getUser($args);
    $communityModel = MidasLoader::loadModel('Community');
    $userModel = MidasLoader::loadModel('User');

    if($userDao && $userDao->isAdmin())
      {
      $communities = $communityModel->getAll();
      }
    else
      {
      $communities = $communityModel->getPublicCommunities();
      if($userDao)
        {
        $communities = array_merge($communities, $userModel->getUserCommunities($userDao));
        }
      }

    $sortDaoComponent = MidasLoader::loadComponent('Sortdao');
    $sortDaoComponent->field = 'name';
    $sortDaoComponent->order = 'asc';
    usort($communities, array($sortDaoComponent, 'sortByName'));
    return $sortDaoComponent->arrayUniqueDao($communities);
    }

  /**
   * Delete a community. Requires admin privileges on the community
   * @param token Authentication token
   * @param id The id of the community
   */
  function communityDelete($args)
    {
    $this->_validateParams($args, array('id'));

    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', MIDAS_INVALID_TOKEN);
      }
    $id = $args['id'];

    $communityModel = MidasLoader::loadModel('Community');
    $community = $communityModel->load($id);

    if($community === false || !$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This community doesn't exist  or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $communityModel->delete($community);
    }

  /**
   *  helper function to set the privacy code on a passed in folder.
   */
  protected function _setFolderPrivacy($folder, $privacyCode)
    {
    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $groupModel = MidasLoader::loadModel('Group');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $folderpolicygroupDao = $folderpolicygroupModel->getPolicy($anonymousGroup, $folder);
    if($privacyCode == MIDAS_PRIVACY_PRIVATE && $folderpolicygroupDao !== false)
      {
      $folderpolicygroupModel->delete($folderpolicygroupDao);
      }
    else if($privacyCode == MIDAS_PRIVACY_PUBLIC && $folderpolicygroupDao == false)
      {
      $policyDao = $folderpolicygroupModel->createPolicy($anonymousGroup, $folder, MIDAS_POLICY_READ);
      }
    }

  /**
   * Create a folder or update an existing one if one exists by the uuid passed
   * @param token Authentication token
   * @param name The name of the folder to create
   * @param description (Optional) The description of the folder
   * @param uuid (Optional) Uuid of the folder. If none is passed, will generate one.
   * @param privacy (Optional) Default 'Public', possible values [Public|Private].
   * @param parentid The id of the parent folder. Set this to -1 to create a top level user folder.
   * @return The folder object that was created
   */
  function folderCreate($args)
    {
    $this->_validateParams($args, array('name'));
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot create folder anonymously', MIDAS_INVALID_POLICY);
      }

    $folderModel = MidasLoader::loadModel('Folder');
    $name = $args['name'];
    $description = isset($args['description']) ? $args['description'] : '';

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';
    $record = false;
    if(!empty($uuid))
      {
      $uuidComponent = MidasLoader::loadComponent('Uuid');
      $record = $uuidComponent->getByUid($uuid);
      }
    if($record != false && $record instanceof FolderDao)
      {
      if(!$folderModel->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
        }
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        if(!$folderModel->policyCheck($record, $userDao, MIDAS_POLICY_ADMIN))
          {
          throw new Exception('Folder Admin privileges required to set privacy', MIDAS_INVALID_POLICY);
          }
        $privacyCode = $this->_getValidPrivacyCode($args['privacy']);
        $this->_setFolderPrivacy($record, $privacyCode);
        }
      $folderModel->save($record);
      return $record->toArray();
      }
    else
      {
      if(!array_key_exists('parentid', $args))
        {
        throw new Exception('Parameter parentid is not defined', MIDAS_INVALID_PARAMETER);
        }
      if($args['parentid'] == -1) //top level user folder being created
        {
        $new_folder = $folderModel->createFolder($name, $description, $userDao->getFolderId(), $uuid);
        }
      else //child of existing folder
        {
        $folder = $folderModel->load($args['parentid']);
        if($folder == false)
          {
          throw new Exception('Parent doesn\'t exist', MIDAS_INVALID_PARAMETER);
          }
        if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
          {
          throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
          }
        $new_folder = $folderModel->createFolder($name, $description, $folder, $uuid);
        if($new_folder === false)
          {
          throw new Exception('Create folder failed', MIDAS_INTERNAL_ERROR);
          }
        $policyGroup = $folder->getFolderpolicygroup();
        $policyUser = $folder->getFolderpolicyuser();
        $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
        $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
        foreach($policyGroup as $policy)
          {
          $folderpolicygroupModel->createPolicy($policy->getGroup(), $new_folder, $policy->getPolicy());
          }
        foreach($policyUser as $policy)
          {
          $folderpolicyuserModel->createPolicy($policy->getUser(), $new_folder, $policy->getPolicy());
          }
        if(!$folderModel->policyCheck($new_folder, $userDao, MIDAS_POLICY_ADMIN))
          {
          $folderpolicyuserModel->createPolicy($userDao, $new_folder, MIDAS_POLICY_ADMIN);
          }
        }

      // set privacy if desired
      if(isset($args['privacy']))
        {
        $privacyCode = $this->_getValidPrivacyCode($args['privacy']);
        $this->_setFolderPrivacy($new_folder, $privacyCode);
        }
      else
        {
        // explicitly set to Public
        $this->_setFolderPrivacy($new_folder, MIDAS_PRIVACY_PUBLIC);
        }

      return $new_folder->toArray();
      }
    }

  /**
   * Get information about the folder
   * @param token (Optional) Authentication token
   * @param id The id of the folder
   * @return The folder object, including its parent object
   */
  function folderGet($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');

    $id = $args['id'];
    $folder = $folderModel->load($id);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $arr = $folder->toArray();
    $arr['parent'] = $folder->getParent();
    return $arr;
    }

  /**
   * Get the immediate children of a folder (non-recursive)
   * @param token (Optional) Authentication token
   * @param id The id of the folder
   * @return The items and folders in the given folder
   */
  function folderChildren($args)
    {
    $this->_validateParams($args, array('id'));

    $id = $args['id'];
    $folderModel = MidasLoader::loadModel('Folder');
    $folder = $folderModel->load($id);

    $userDao = $this->_getUser($args);
    try
      {
      $folders = $folderModel->getChildrenFoldersFiltered($folder, $userDao);
      $items = $folderModel->getItemsFiltered($folder, $userDao);
      }
    catch(Exception $e)
      {
      throw new Exception($e->getMessage(), MIDAS_INTERNAL_ERROR);
      }
    $itemsList = array();
    foreach($items as $item)
      {
      $itemArray = $item->toArray();
      $itemArray['extraFields'] = $this->_getItemExtraFields($item);
      $itemsList[] = $itemArray;
      }

    return array('folders' => $folders, 'items' => $itemsList);
    }

  /**
   * Delete a folder. Requires admin privileges on the folder
   * @param token Authentication token
   * @param id The id of the folder
   */
  function folderDelete($args)
    {
    $this->_validateParams($args, array('id'));

    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', MIDAS_INVALID_TOKEN);
      }
    $id = $args['id'];
    $folderModel = MidasLoader::loadModel('Folder');
    $folder = $folderModel->load($id);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This folder doesn't exist  or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $folderModel->delete($folder);
    }

  /**
   * Download a folder
   * @param token (Optional) Authentication token
   * @param id The id of the folder
   * @return A zip archive of the folder's contents
   */
  function folderDownload($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $id = $args['id'];
    $folderModel = MidasLoader::loadModel('Folder');
    $folder = $folderModel->load($id);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $redirUrl = '/download/?folders='.$folder->getKey();
    if($userDao && array_key_exists('token', $args))
      {
      $redirUrl .= '&authToken='.$args['token'];
      }
    $this->controller->redirect($redirUrl);
    }

  /**
   * Move a folder to the destination folder
   * @param token Authentication token
   * @param id The id of the folder
   * @param dstfolderid The id of destination folder (new parent folder) where the folder is moved to
   * @return The folder object
   */
  function folderMove($args)
    {
    $this->_validateParams($args, array('id', 'dstfolderid'));
    $userDao = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $folder = $folderModel->load($id);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }
    if($dstFolder == false)
      {
      throw new Exception("Unable to load destination folder.", MIDAS_INVALID_POLICY);
      }
    $folderModel->move($folder, $dstFolder);

    $folder = $folderModel->load($id);
    return $folder->toArray();
    }

  /**
   * List the permissions on a folder, requires Admin access to the folder.
   * @param folder_id The id of the folder
   * @return A list with three keys: privacy, user, group; privacy will be the
     folder's privacy string [Public|Private]; user will be a list of
     (user_id, policy, email); group will be a list of (group_id, policy, name).
     policy for user and group will be a policy string [Admin|Write|Read].
   */
  public function folderListPermissions($args)
    {
    $this->_validateParams($args, array('folder_id'));
    $userDao = $this->_getUser($args);

    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $groupModel = MidasLoader::loadModel('Group');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['folder_id'];
    $folder = $folderModel->load($folderId);

    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder to list permissions.", MIDAS_INVALID_POLICY);
      }

    $privacyStrings = array(MIDAS_PRIVACY_PUBLIC => "Public", MIDAS_PRIVACY_PRIVATE => "Private");
    $privilegeStrings = array(MIDAS_POLICY_ADMIN => "Admin", MIDAS_POLICY_WRITE => "Write", MIDAS_POLICY_READ => "Read");

    $return = array('privacy' => $privacyStrings[$folderpolicygroupModel->computePolicyStatus($folder)]);

    $userPolicies = $folder->getFolderpolicyuser();
    $userPoliciesOutput = array();
    foreach($userPolicies as $userPolicy)
      {
      $user = $userPolicy->getUser();
      $userPoliciesOutput[] = array('user_id' => $user->getUserId(), 'policy' => $privilegeStrings[$userPolicy->getPolicy()], 'email' => $user->getEmail());
      }
    $return['user'] = $userPoliciesOutput;

    $groupPolicies = $folder->getFolderpolicygroup();
    $groupPoliciesOutput = array();
    foreach($groupPolicies as $groupPolicy)
      {
      $group = $groupPolicy->getGroup();
      $groupPoliciesOutput[] = array('group_id' => $group->getGroupId(), 'policy' => $privilegeStrings[$groupPolicy->getPolicy()], 'name' => $group->getName());
      }
    $return['group'] = $groupPoliciesOutput;

    return $return;
    }

  /**
   * Set the privacy status on a folder, and push this value down recursively
     to all children folders and items, requires Admin access to the folder.
   * @param folder_id The id of the folder.
   * @param privacy Desired privacy status, one of [Public|Private].
   * @return An array with keys 'success' and 'failure' indicating a count
     of children resources that succeeded or failed the permission change.
   */
  function folderSetPrivacyRecursive($args)
    {
    $this->_validateParams($args, array('folder_id', 'privacy'));
    $userDao = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['folder_id'];
    $folder = $folderModel->load($folderId);

    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder to set privacy.", MIDAS_INVALID_POLICY);
      }

    $privacyCode = $this->_getValidPrivacyCode($args['privacy']);
    $this->_setFolderPrivacy($folder, $privacyCode);

    // now push down the privacy recursively
    $policyComponent = MidasLoader::loadComponent('Policy');
    // send a null Progress since we aren't interested in progress
    // prepopulate results with 1 success for the folder we have already changed
    $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results = array('success' => 1, 'failure' => 0));
    return $results;
    }


  /**
   * helper method to validate passed in policy params and
   * map them to valid policy codes.
   * @param string $policy, should be [Admin|Write|Read]
   * @return valid policy code
   */
  private function _getValidPolicyCode($policy)
    {
    $policyCodes = array('Admin' => MIDAS_POLICY_ADMIN, 'Write' => MIDAS_POLICY_WRITE, 'Read' => MIDAS_POLICY_READ);
    if(!array_key_exists($policy, $policyCodes))
      {
      $validCodes = '[' . implode('|', array_keys($policyCodes)) . ']';
      throw new Exception('policy should be one of ' . $validCodes, MIDAS_INVALID_PARAMETER);
      }
    return $policyCodes[$policy];
    }

  /**
   * Add a folderpolicygroup to a folder with the passed in group and policy;
     if a folderpolicygroup exists for that group and folder, it will be replaced
     with the passed in policy.
   * @param folder_id The id of the folder.
   * @param group_id The id of the group.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @param recursive If included will push all policies from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the addition.
   */
  function folderAddPolicygroup($args)
    {
    $this->_validateParams($args, array('folder_id', 'group_id', 'policy'));
    $userDao = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['folder_id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($args['group_id']);
    if($group === false)
      {
      throw new Exception("This group doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $policyCode = $this->_getValidPolicyCode($args['policy']);

    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $folderpolicygroupModel->createPolicy($group, $folder, $policyCode);

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results);
      }

    return $results;
    }

  /**
   * Remove a folderpolicygroup from a folder with the passed in group if the
     folderpolicygroup exists.
   * @param folder_id The id of the folder.
   * @param group_id The id of the group.
   * @param recursive If included will push all policies after the removal from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the removal.
   */
  function folderRemovePolicygroup($args)
    {
    $this->_validateParams($args, array('folder_id', 'group_id'));
    $userDao = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['folder_id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($args['group_id']);
    if($group === false)
      {
      throw new Exception("This group doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $folderpolicygroup = $folderpolicygroupModel->getPolicy($group, $folder);
    if($folderpolicygroup !== false)
      {
      $folderpolicygroupModel->delete($folderpolicygroup);
      }

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results);
      }

    return $results;
    }

  /**
   * Add a folderpolicyuser to a folder with the passed in user and policy;
     if a folderpolicyuser exists for that user and folder, it will be replaced
     with the passed in policy.
   * @param folder_id The id of the folder.
   * @param user_id The id of the targeted user to create the policy for.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @param recursive If included will push all policies from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the addition.
   */
  function folderAddPolicyuser($args)
    {
    $this->_validateParams($args, array('folder_id', 'user_id', 'policy'));
    $adminUser = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['folder_id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $adminUser, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $userModel = MidasLoader::loadModel('User');
    $targetUserId = $args['user_id'];
    $targetUser = $userModel->load($targetUserId);
    if($targetUser === false)
      {
      throw new Exception("This user doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $policyCode = $this->_getValidPolicyCode($args['policy']);

    $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
    $folderpolicyuserModel->createPolicy($targetUser, $folder, $policyCode);

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $adminUser, null, $results);
      }

    return $results;
    }

  /**
   * Remove a folderpolicyuser from a folder with the passed in user if the
     folderpolicyuser exists.
   * @param folder_id The id of the folder.
   * @param user_id The id of the target user.
   * @param recursive If included will push all policies after the removal from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the removal.
   */
  function folderRemovePolicyuser($args)
    {
    $this->_validateParams($args, array('folder_id', 'user_id'));
    $userDao = $this->_getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['folder_id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $userModel = MidasLoader::loadModel('User');
    $user = $userModel->load($args['user_id']);
    if($user === false)
      {
      throw new Exception("This user doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
    $folderpolicyuser = $folderpolicyuserModel->getPolicy($user, $folder);
    if($folderpolicyuser !== false)
      {
      $folderpolicyuserModel->delete($folderpolicyuser);
      }

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results);
      }

    return $results;
    }

  /**
   * helper method to validate passed in privacy status params and
   * map them to valid privacy codes.
   * @param string $privacyStatus, should be 'Private' or 'Public'
   * @return valid privacy code
   */
  private function _getValidPrivacyCode($privacyStatus)
    {
    if($privacyStatus !== 'Public' && $privacyStatus !== 'Private')
      {
      throw new Exception('privacy should be one of [Public|Private]', MIDAS_INVALID_PARAMETER);
      }
    if($privacyStatus === 'Public')
      {
      $privacyCode = MIDAS_PRIVACY_PUBLIC;
      }
    else
      {
      $privacyCode = MIDAS_PRIVACY_PRIVATE;
      }
    return $privacyCode;
    }


  /**
   *  helper function to set the privacy code on a passed in item.
   */
  protected function _setItemPrivacy($item, $privacyCode)
    {
    $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');
    $groupModel = MidasLoader::loadModel('Group');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $itempolicygroupDao = $itempolicygroupModel->getPolicy($anonymousGroup, $item);
    if($privacyCode == MIDAS_PRIVACY_PRIVATE && $itempolicygroupDao !== false)
      {
      $itempolicygroupModel->delete($itempolicygroupDao);
      }
    else if($privacyCode == MIDAS_PRIVACY_PUBLIC && $itempolicygroupDao == false)
      {
      $itempolicygroupDao = $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
      }
    }



  /**
   * Create an item or update an existing one if one exists by the uuid passed.
     Note: In the case of an already existing item, any parameters passed whose name
     begins with an underscore are assumed to be metadata fields to set on the item.
   * @param token Authentication token
   * @param parentid The id of the parent folder. Only required for creating a new item.
   * @param name The name of the item to create
   * @param description (Optional) The description of the item
   * @param uuid (Optional) Uuid of the item. If none is passed, will generate one.
   * @param privacy (Optional) Default 'Public', possible values [Public|Private].
   * @return The item object that was created
   */
  function itemCreate($args)
    {
    $this->_validateParams($args, array('name'));
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot create item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $name = $args['name'];
    $description = isset($args['description']) ? $args['description'] : '';

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';
    $record = false;
    if(!empty($uuid))
      {
      $uuidComponent = MidasLoader::loadComponent('Uuid');
      $record = $uuidComponent->getByUid($uuid);
      }
    if($record != false && $record instanceof ItemDao)
      {
      if(!$itemModel->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
        }
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        if(!$itemModel->policyCheck($record, $userDao, MIDAS_POLICY_ADMIN))
          {
          throw new Exception('Item Admin privileges required to set privacy', MIDAS_INVALID_POLICY);
          }
        $privacyCode = $this->_getValidPrivacyCode($args['privacy']);
        $this->_setItemPrivacy($record, $privacyCode);
        }
      foreach($args as $key => $value)
        {
        // Params beginning with underscore are assumed to be metadata fields
        if(substr($key, 0, 1) == '_')
          {
          $this->_setMetadata($record, MIDAS_METADATA_TEXT, substr($key, 1), '', $value);
          }
        }
      $itemModel->save($record, true);
      return $record->toArray();
      }
    else
      {
      if(!array_key_exists('parentid', $args))
        {
        throw new Exception('Parameter parentid is not defined', MIDAS_INVALID_PARAMETER);
        }
      $folderModel = MidasLoader::loadModel('Folder');
      $folder = $folderModel->load($args['parentid']);
      if($folder == false)
        {
        throw new Exception('Parent folder doesn\'t exist', MIDAS_INVALID_PARAMETER);
        }
      if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid permissions on parent folder', MIDAS_INVALID_POLICY);
        }
      $item = $itemModel->createItem($name, $description, $folder, $uuid);
      if($item === false)
        {
        throw new Exception('Create new item failed', MIDAS_INTERNAL_ERROR);
        }
      $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
      $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);

      // set privacy if desired
      if(isset($args['privacy']))
        {
        $privacyCode = $this->_getValidPrivacyCode($args['privacy']);
        $this->_setItemPrivacy($item, $privacyCode);
        }
      else
        {
        // explicitly set to Public
        $this->_setItemPrivacy($item, MIDAS_PRIVACY_PUBLIC);
        }

      return $item->toArray();
      }
    }

  /**
   * Get an item's information
   * @param token (Optional) Authentication token
   * @param id The item id
   * @param head (Optional) only list the most recent revision
   * @return The item object
   */
  function itemGet($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $itemid = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($itemid);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $itemArray = $item->toArray();

    $owningFolders = $item->getFolders();
    if(count($owningFolders) > 0)
      {
      $itemArray['folder_id'] = $owningFolders[0]->getKey();
      }

    $revisionsArray = array();
    if(array_key_exists('head', $args))
      {
      $revisions = array($itemModel->getLastRevision($item));
      }
    else //get all revisions
      {
      $revisions = $item->getRevisions();
      }

    foreach($revisions as $revision)
      {
      if(!$revision)
        {
        continue;
        }
      $bitstreamArray = array();
      $bitstreams = $revision->getBitstreams();
      foreach($bitstreams as $b)
        {
        $bitstreamArray[] = $b->toArray();
        }
      $tmp = $revision->toArray();
      $tmp['bitstreams'] = $bitstreamArray;
      $revisionsArray[] = $tmp;
      }
    $itemArray['revisions'] = $revisionsArray;
    $itemArray['extraFields'] = $this->_getItemExtraFields($item);

    return $itemArray;
    }

  /**
   * Helper function to return any extra fields that should be passed with an item
   * @param item The item dao
   */
  private function _getItemExtraFields($item)
    {
    $extraFields = array();
    // Add any extra fields that modules want to attach to the item
    $modules = Zend_Registry::get('notifier')->callback('CALLBACK_API_EXTRA_ITEM_FIELDS',
                                                        array('item' => $item));
    foreach($modules as $module => $fields)
      {
      foreach($fields as $name => $value)
        {
        $extraFields[$module.'_'.$name] = $value;
        }
      }
    return $extraFields;
    }

  /**
   * Download an item
   * @param token (Optional) Authentication token
   * @param id The id of the item
   * @param revision (Optional) Revision to download. Defaults to latest revision
   * @return The bitstream(s) in the item
   */
  function itemDownload($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $id = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($id);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $redirUrl = '/download/?items='.$item->getKey();
    if(isset($args['revision']))
      {
      $redirUrl .= ','.$args['revision'];
      }
    if($userDao && array_key_exists('token', $args))
      {
      $redirUrl .= '&authToken='.$args['token'];
      }
    $this->controller->redirect($redirUrl);
    }

  /**
   * Delete an item
   * @param token Authentication token
   * @param id The id of the item
   */
  function itemDelete($args)
    {
    $this->_validateParams($args, array('id'));

    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', MIDAS_INVALID_TOKEN);
      }
    $id = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($id);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $itemModel->delete($item);
    }

  /**
   * helper function to get a revision of a certain number from an item,
   * if revisionNumber is null will get the last revision of the item; used
   * by the metadata calls and so has exception handling built in for them.
   *
   * will return a valid ItemRevision or else throw an exception.
   */
  private function _getItemRevision($item, $revisionNumber = null)
    {
    $itemModel = MidasLoader::loadModel('Item');
    if(!isset($revisionNumber))
      {
      $revisionDao = $itemModel->getLastRevision($item);
      if($revisionDao)
        {
        return $revisionDao;
        }
      else
        {
        throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
        }
      }

    $revisionNumber = (int)$revisionNumber;
    if(!is_int($revisionNumber) || $revisionNumber < 1)
      {
      throw new Exception("Revision Numbers must be integers greater than 0.".$revisionNumber, MIDAS_INVALID_PARAMETER);
      }
    $revisions = $item->getRevisions();
    if(sizeof($revisions) === 0)
      {
      throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
      }
    // check revisions exist
    foreach($revisions as $revision)
      {
      if($revisionNumber == $revision->getRevision())
        {
        $revisionDao = $revision;
        break;
        }
      }
    if(isset($revisionDao))
      {
      return $revisionDao;
      }
    else
      {
      throw new Exception("This revision number is invalid for this item.", MIDAS_INVALID_PARAMETER);
      }
    }

  /**
   * Get the item's metadata
   * @param token (Optional) Authentication token
   * @param id The id of the item
   * @param revision (Optional) Revision of the item. Defaults to latest revision
   * @return the sought metadata array on success,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemGetmetadata($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $itemid = $args['id'];
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($itemid);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $revisionDao = $this->_getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);
    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    $metadata = $itemRevisionModel->getMetadata($revisionDao);
    $metadataArray = array();
    foreach($metadata as $m)
      {
      $metadataArray[] = $m->toArray();
      }
    return $metadataArray;
    }

  /**
   * Set a metadata field on an item
   * @param token Authentication token
   * @param itemId The id of the item
   * @param element The metadata element
   * @param value The metadata value for the field
   * @param qualifier (Optional) The metadata qualifier. Defaults to empty string.
   * @param type (Optional) The metadata type (integer constant). Defaults to MIDAS_METADATA_TEXT type (0).
   * @param revision (Optional) Revision of the item. Defaults to latest revision.
   * @return true on success,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemSetmetadata($args)
    {
    $this->_validateParams($args, array('itemId', 'element', 'value'));
    $userDao = $this->_getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['itemId']);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $type = array_key_exists('type', $args) ? (int)$args['type'] : MIDAS_METADATA_TEXT;
    $qualifier = array_key_exists('qualifier', $args) ? $args['qualifier'] : '';
    $element = $args['element'];
    $value = $args['value'];

    $revisionDao = $this->_getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);
    $this->_setMetadata($item, $type, $element, $qualifier, $value, $revisionDao);
    return true;
    }

  /**
   * Helper function to set metadata on an item.
   * Does not perform permission checks; these should be done in advance.
   */
  private function _setMetadata($item, $type, $element, $qualifier, $value, $revisionDao = null)
    {
    $itemModel = MidasLoader::loadModel('Item');
    if($revisionDao === null)
      {
      $revisionDao = $itemModel->getLastRevision($item);
      }
    $modules = Zend_Registry::get('notifier')->callback('CALLBACK_API_METADATA_SET',
                                                        array('item' => $item,
                                                              'revision' => $revisionDao,
                                                              'type' => $type,
                                                              'element' => $element,
                                                              'qualifier' => $qualifier,
                                                              'value' => $value));
    foreach($modules as $name => $retval)
      {
      if($retval['status'] === true) //module has handled the event, so we don't have to
        {
        return;
        }
      }

    // If no module handles this metadata, we add it as normal metadata on the item revision
    if(!$revisionDao)
      {
      throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
      }

    $metadataModel = MidasLoader::loadModel('Metadata');
    $metadataDao = $metadataModel->getMetadata($type, $element, $qualifier);
    if($metadataDao == false)
      {
      $metadataModel->addMetadata($type, $element, $qualifier, '');
      }
    $metadataModel->addMetadataValue($revisionDao, $type, $element, $qualifier, $value);
    }

  /**
   * helper function to parse out the metadata tuples from the params for a
   * call to setmultiplemetadata, will validate matching tuples to count.
   */
  private function _parseMetadataTuples($args)
    {
    $count = (int)$args['count'];
    if(!is_int($count) || $count < 1)
      {
      throw new Exception("Count must be an integer greater than 0.", MIDAS_INVALID_PARAMETER);
      }
    $metadataTuples = array();
    for($i = 0; $i < $count; $i = $i + 1)
      {
      // counters are 1 indexed
      $counter = $i + 1;
      $element_i_key = 'element_'.$counter;
      $value_i_key = 'value_'.$counter;
      $qualifier_i_key = 'qualifier_'.$counter;
      $type_i_key = 'type_'.$counter;
      if(!array_key_exists($element_i_key, $args))
        {
        throw new Exception("Count was ".$i." but param ".$element_i_key." is missing.", MIDAS_INVALID_PARAMETER);
        }
      if(!array_key_exists($value_i_key, $args))
        {
        throw new Exception("Count was ".$i." but param ".$value_i_key." is missing.", MIDAS_INVALID_PARAMETER);
        }
      $element = $args[$element_i_key];
      $value = $args[$value_i_key];
      $qualifier = array_key_exists($qualifier_i_key, $args) ? $args[$qualifier_i_key] : '';
      $type = array_key_exists($type_i_key, $args) ? $args[$qualifier_i_key] : MIDAS_METADATA_TEXT;
      if(!is_int($type) || $type < 0 || $type > 6)
        {
        throw new Exception("param ".$type_i_key." must be an integer between 0 and 6.", MIDAS_INVALID_PARAMETER);
        }
      $metadataTuples[] = array('element' => $element,
                                'qualifier' => $qualifier,
                                'type' => $type,
                                'value' => $value);
      }
    return $metadataTuples;
    }



  /**
   * Set multiple metadata fields on an item, requires specifying the number of
     metadata tuples to add.
   * @param token Authentication token
   * @param itemid The id of the item
     @param revision (Optional) Item Revision number to set metadata on, defaults to latest revision.
   * @param count The number of metadata tuples that will be set.  For every one
     of these metadata tuples there will be the following set of params with counters
     at the end of each param name, from 1..<b>count</b>, following the example
     using the value <b>i</b> (i.e., replace <b>i</b> with values 1..<b>count</b>)
     (<b>element_i</b>, <b>value_i</b>, <b>qualifier_i</b>, <b>type_i</b>).

     @param element_i metadata element for tuple i
     @param value_i   metadata value for the field, for tuple i
     @param qualifier_i (Optional) metadata qualifier for tuple i. Defaults to empty string.
     @param type_i (Optional) metadata type (integer constant). Defaults to MIDAS_METADATA_TEXT type (0).
   * @return true on success,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemSetmultiplemetadata($args)
    {
    $this->_validateParams($args, array('itemid', 'count'));
    $metadataTuples = $this->_parseMetadataTuples($args);

    $userDao = $this->_getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['itemid']);
    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $revisionNumber = array_key_exists('revision', $args) ? (int)$args['revision'] : null;
    $revision = $this->_getItemRevision($item, $revisionNumber);

    foreach($metadataTuples as $tup)
      {
      $this->_setMetadata($item, $tup['type'], $tup['element'], $tup['qualifier'], $tup['value'], $revision);
      }
    return true;
    }

  /**
     Delete a metadata tuple (element, qualifier, type) from a specific item revision,
     defaults to the latest revision of the item.
   * @param token Authentication token
   * @param itemid The id of the item
   * @param element The metadata element
   * @param qualifier (Optional) The metadata qualifier. Defaults to empty string.
   * @param type (Optional) metadata type (integer constant).
     Defaults to MIDAS_METADATA_TEXT (0).
   * @param revision (Optional) Revision of the item. Defaults to latest revision.
   * @return true on success,
             false if the metadata was not found on the item revision,
             will fail if there are no revisions or the specified revision is not found.
   */
  function itemDeletemetadata($args)
    {
    $this->_validateParams($args, array('itemid', 'element'));
    $userDao = $this->_getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['itemid']);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $element = $args['element'];
    $qualifier = array_key_exists('qualifier', $args) ? $args['qualifier'] : '';
    $type = array_key_exists('type', $args) ? (int)$args['type'] : MIDAS_METADATA_TEXT;

    $revisionDao = $this->_getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);

    $metadataModel = MidasLoader::loadModel('Metadata');
    $metadata = $metadataModel->getMetadata($type, $element, $qualifier);
    if(!isset($metadata) || $metadata === false)
      {
      return false;
      }

    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    $itemRevisionModel->deleteMetadata($revisionDao, $metadata->getMetadataId());

    return true;
    }

  /**
     Deletes all metadata associated with a specific item revision;
     defaults to the latest revision of the item;
     pass <b>revision</b>=<b>all</b> to delete all metadata from all revisions.
   * @param token Authentication token
   * @param itemid The id of the item
   * @param revision (Optional)
     Revision of the item. Defaults to latest revision; pass <b>all</b> to delete all metadata from all revisions.
   * @return true on success,
     will fail if there are no revisions or the specified revision is not found.
   */
  function itemDeletemetadataAll($args)
    {
    $this->_validateParams($args, array('itemid'));
    $userDao = $this->_getUser($args);

    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($args['itemid']);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have write permission.", MIDAS_INVALID_POLICY);
      }

    $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
    if(array_key_exists('revision', $args) && $args['revision'] === 'all')
      {
      $revisions = $item->getRevisions();
      if(sizeof($revisions) === 0)
        {
        throw new Exception("The item must have at least one revision to have metadata.", MIDAS_INVALID_POLICY);
        }
      foreach($revisions as $revisionDao)
        {
        $itemRevisionModel->deleteMetadata($revisionDao);
        }
      }
    else
      {
      $revisionDao = $this->_getItemRevision($item, isset($args['revision']) ? $args['revision'] : null);
      if(isset($revisionDao) && $revisionDao !== false)
        {
        $itemRevisionModel->deleteMetadata($revisionDao);
        }
      }

    return true;
    }

  /**
   * Duplicate an item to the desination folder
   * @param token Authentication token
   * @param id The id of the item
   * @param dstfolderid The id of destination folder where the item is duplicated to
   * @return The item object that was created
   */
  function itemDuplicate($args)
    {
    $this->_validateParams($args, array('id', 'dstfolderid'));
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot duplicate item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $item = $itemModel->load($id);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $duplicatedItem = $itemModel->duplicateItem($item, $userDao, $dstFolder);

    return $duplicatedItem->toArray();
    }

  /**
   * Share an item to the destination folder
   * @param token Authentication token
   * @param id The id of the item
   * @param dstfolderid The id of destination folder where the item is shared to
   * @return The item object
   */
  function itemShare($args)
    {
    $this->_validateParams($args, array('id', 'dstfolderid'));
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot share item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $item = $itemModel->load($id);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $itemArray = $item->toArray();
    $owningFolderIds = array();
    $owningFolderArray = array();
    foreach($item->getFolders() as $owningFolder)
      {
      $owningFolderIds[] = $owningFolder->getKey();
      $owningFolderArray[] = $owningFolder->toArray();
      }
    if(!in_array($dstFolder->getKey(), $owningFolderIds))
      {
      // Do not update item name in item share action
      $folderModel->addItem($dstFolder, $item, false);
      $itemModel->addReadonlyPolicy($item, $dstFolder);
      $owningFolderArray[] = $dstFolder->toArray();
      }

    $itemArray['owningfolders'] = $owningFolderArray;
    return $itemArray;
    }

  /**
   * Move an item from the source folder to the desination folder
   * @param token Authentication token
   * @param id The id of the item
   * @param srcfolderid The id of source folder where the item is located
   * @param dstfolderid The id of destination folder where the item is moved to
   * @return The item object
   */
  function itemMove($args)
    {
    $this->_validateParams($args, array('id', 'srcfolderid', 'dstfolderid'));
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot move item anonymously', MIDAS_INVALID_POLICY);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $item = $itemModel->load($id);
    $srcFolderId = $args['srcfolderid'];
    $srcFolder = $folderModel->load($srcFolderId);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($item === false || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    if($srcFolder == false || $dstFolder == false)
      {
      throw new Exception("Unable to load source or destination folder.", MIDAS_INVALID_POLICY);
      }
    if($dstFolder->getKey() != $srcFolder->getKey())
      {
      $folderModel->addItem($dstFolder, $item);
      $itemModel->copyParentPolicies($item, $dstFolder);
      $folderModel->removeItem($srcFolder, $item);
      }

    $itemArray = $item->toArray();
    $owningFolderArray = array();
    foreach($item->getFolders() as $owningFolder)
      {
      $owningFolderArray[] = $owningFolder->toArray();
      }
    $itemArray['owningfolders'] = $owningFolderArray;
    return $itemArray;
    }

  /**
   * Return all items
   * @param token (Optional) Authentication token
   * @param name The name of the item to search by
   * @return A list of all items with the given name
   */
  function itemSearchbyname($args)
    {
    $this->_validateParams($args, array('name'));
    $userDao = $this->_getUser($args);
    $itemModel = MidasLoader::loadModel('Item');
    $items = $itemModel->getByName($args['name']);

    $matchList = array();
    foreach($items as $item)
      {
      if($itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
        {
        $matchList[] = $item->toArray();
        }
      }

    return array('items' => $matchList);
    }

  /**
   * Return a list of top level folders belonging to the user
   * @param token Authentication token
   * @return List of the user's top level folders
   */
  function userFolders($args)
    {
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      return array();
      }

    $userRootFolder = $userDao->getFolder();
    $folderModel = MidasLoader::loadModel('Folder');
    return $folderModel->getChildrenFoldersFiltered($userRootFolder, $userDao, MIDAS_POLICY_READ);
    }

  /**
   * Returns the user's default API key given their username and password.
       POST method required.
   * @param email The user's email
   * @param password The user's password
   * @return Array with a single key, 'apikey', whose value is the user's default api key
   */
  function userApikeyDefault($args)
    {
    $this->_validateParams($args, array('email', 'password'));
    if(!$this->controller->getRequest()->isPost())
      {
      throw new Exception('POST method required', MIDAS_HTTP_ERROR);
      }
    $email = $args['email'];
    $password = $args['password'];

    try
      {
      $notifications = array();
      $notifications = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_AUTHENTICATION', array(
        'email' => $email,
        'password' => $password));
      }
    catch(Zend_Exception $exc)
      {
      throw new Exception('Login failed', MIDAS_INVALID_PARAMETER);
      }
    $authModule = false;
    foreach($notifications as $module => $user)
      {
      if($user)
        {
        $userDao = $user;
        $authModule = true;
        break;
        }
      }

    $userModel = MidasLoader::loadModel('User');
    $userApiModel = MidasLoader::loadModel('Userapi', 'api');
    if(!$authModule)
      {
      $userDao = $userModel->getByEmail($email);
      }

    $salt = Zend_Registry::get('configGlobal')->password->prefix;
    if($authModule || $userDao !== false && md5($salt.$password) == $userDao->getPassword())
      {
      $defaultApiKey = $userApiModel->getByAppAndEmail('Default', $email)->getApikey();
      return array('apikey' => $defaultApiKey);
      }
    else
      {
      throw new Exception('Login failed', MIDAS_INVALID_PARAMETER);
      }
    }

  /**
   * Returns a portion or the entire set of public users based on the limit var.
   * @param limit The maximum number of users to return
   * @return the list of users
   */
  function userList($args)
    {
    $this->_validateParams($args, array('limit'));

    $userModel = MidasLoader::loadModel('User');
    return $userModel->getAll(true, $args['limit']);
    }

  /**
   * Returns a user either by id or by email or by first name and last name.
   * @param user_id The id of the user desired (ignores firstname and lastname)
   * @param email The email of the user desired
   * @param firstname The first name of the desired user (use with lastname)
   * @param lastname The last name of the desired user (use with firstname)
   * @return The user corresponding to the user_id or first and lastname
   */
  function userGet($args)
    {
    $userModel = MidasLoader::loadModel('User');
    if(array_key_exists('user_id', $args))
      {
      return $userModel->getByUser_id($args['user_id']);
      }
    else if(array_key_exists('email', $args))
      {
      return $userModel->getByEmail($args['email']);
      }
    else if(array_key_exists('firstname', $args) &&
            array_key_exists('lastname', $args))
      {
      return $userModel->getByName($args['firstname'], $args['lastname']);
      }
    else
      {
      throw new Exception('Please provide a user_id or both first and last name', MIDAS_INVALID_PARAMETER);
      }
    }

  /**
   * Fetch the information about a bitstream
   * @param token (Optional) Authentication token
   * @param id The id of the bitstream
   * @return Bitstream dao
   */
  function bitstreamGet($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $bitstreamModel = MidasLoader::loadModel('Bitstream');
    $bitstream = $bitstreamModel->load($args['id']);

    if(!$bitstream)
      {
      throw new Exception('Invalid bitstream id', MIDAS_INVALID_PARAMETER);
      }

    if(array_key_exists('name', $args))
      {
      $bitstream->setName($args['name']);
      }
    $revisionModel = MidasLoader::loadModel('ItemRevision');
    $revision = $revisionModel->load($bitstream->getItemrevisionId());

    if(!$revision)
      {
      throw new Exception('Invalid revision id', MIDAS_INTERNAL_ERROR);
      }
    $itemModel = MidasLoader::loadModel('Item');
    $item = $itemModel->load($revision->getItemId());
    if(!$item || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }
    $bitstreamArray = array();
    $bitstreamArray['name'] = $bitstream->getName();
    $bitstreamArray['size'] = $bitstream->getSizebytes();
    $bitstreamArray['mimetype'] = $bitstream->getMimetype();
    $bitstreamArray['checksum'] = $bitstream->getChecksum();
    $bitstreamArray['itemrevision_id'] = $bitstream->getItemrevisionId();
    $bitstreamArray['item_id'] = $revision->getItemId();
    return $bitstreamArray;
    }

  /**
   * Download a bitstream either by its id or by a checksum.
       Either an id or checksum parameter is required.
   * @param token (Optional) Authentication token
   * @param id (Optional) The id of the bitstream
   * @param checksum (Optional) The checksum of the bitstream
   * @param name (Optional) Alternate filename to download as
   * @param offset (Optional) The download offset in bytes (used for resume)
   */
  function bitstreamDownload($args)
    {
    if(!array_key_exists('id', $args) && !array_key_exists('checksum', $args))
      {
      throw new Exception('Either an id or checksum parameter is required', MIDAS_INVALID_PARAMETER);
      }
    $userDao = $this->_getUser($args);

    $bitstreamModel = MidasLoader::loadModel('Bitstream');
    $itemModel = MidasLoader::loadModel('Item');

    if(array_key_exists('id', $args))
      {
      $bitstream = $bitstreamModel->load($args['id']);
      }
    else
      {
      $bitstreams = $bitstreamModel->getByChecksum($args['checksum'], true);
      $bitstream = null;
      foreach($bitstreams as $candidate)
        {
        $rev = $candidate->getItemrevision();
        if(!$rev)
          {
          continue;
          }
        $item = $rev->getItem();
        if($itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ))
          {
          $bitstream = $candidate;
          break;
          }
        }
      }

    if(!$bitstream)
      {
      throw new Exception('The bitstream does not exist or you do not have the permissions', MIDAS_INVALID_PARAMETER);
      }

    $revision = $bitstream->getItemrevision();
    if(!$revision)
      {
      throw new Exception('Bitstream does not belong to a revision', MIDAS_INTERNAL_ERROR);
      }

    $name = array_key_exists('name', $args) ? $args['name'] : $bitstream->getName();
    $offset = array_key_exists('offset', $args) ? $args['offset'] : '0';

    $redirUrl = '/download/?bitstream='.$bitstream->getKey().'&offset='.$offset.'&name='.$name;
    if($userDao && array_key_exists('token', $args))
      {
      $redirUrl .= '&authToken='.$args['token'];
      }
    $this->controller->redirect($redirUrl);
    }

  /**
   * Count the bitstreams under a containing resource. Uses latest revision of each item.
   * @param token (Optional) Authentication token
   * @param uuid The uuid of the containing resource
   * @return array(size=>total_size_in_bytes, count=>total_number_of_files)
   */
  function bitstreamCount($args)
    {
    $this->_validateParams($args, array('uuid'));
    $userDao = $this->_getUser($args);

    $uuidComponent = MidasLoader::loadComponent('Uuid');
    $resource = $uuidComponent->getByUid($args['uuid']);

    if($resource == false)
      {
      throw new Exception('No resource for the given UUID.', MIDAS_INVALID_PARAMETER);
      }

    switch($resource->resourceType)
      {
      case MIDAS_RESOURCE_COMMUNITY:
        $communityModel = MidasLoader::loadModel('Community');
        if(!$communityModel->policyCheck($resource, $userDao, MIDAS_POLICY_READ))
          {
          throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
          }
        return $communityModel->countBitstreams($resource, $userDao);
      case MIDAS_RESOURCE_FOLDER:
        $folderModel = MidasLoader::loadModel('Folder');
        if(!$folderModel->policyCheck($resource, $userDao, MIDAS_POLICY_READ))
          {
          throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
          }
        return $folderModel->countBitstreams($resource, $userDao);
      case MIDAS_RESOURCE_ITEM:
        $itemModel = MidasLoader::loadModel('Item');
        if(!$itemModel->policyCheck($resource, $userDao, MIDAS_POLICY_READ))
          {
          throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
          }
        return $itemModel->countBitstreams($resource);
      default:
        throw new Exception('Invalid resource type', MIDAS_INTERNAL_ERROR);
      }
    }

  /**
   * Change the properties of a bitstream. Requires write access to the containing item.
   * @param token Authentication token
   * @param id The id of the bitstream to edit
   * @param name (optional) New name for the bitstream
   * @param mimetype (optional) New MIME type for the bitstream
   * @return The bitstream dao
   */
  function bitstreamEdit($args)
    {
    $this->_validateParams($args, array('id'));
    $userDao = $this->_getUser($args);

    $bitstreamModel = MidasLoader::loadModel('Bitstream');
    $itemModel = MidasLoader::loadModel('Item');

    $bitstream = $bitstreamModel->load($args['id']);
    if(!$bitstream)
      {
      throw new Exception('Invalid bitstream id', MIDAS_INVALID_PARAMETER);
      }

    if(!$itemModel->policyCheck($bitstream->getItemrevision()->getItem(), $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception('Write access on item is required', MIDAS_INVALID_POLICY);
      }

    if(array_key_exists('name', $args))
      {
      $bitstream->setName($args['name']);
      }
    if(array_key_exists('mimetype', $args))
      {
      $bitstream->setMimetype($args['mimetype']);
      }
    $bitstreamModel->save($bitstream);
    return $bitstream->toArray();
    }
  } // end class
