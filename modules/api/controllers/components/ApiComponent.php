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
    foreach($requiredList as $param)
      {
      if(!array_key_exists($param, $args))
        {
        throw new Exception('Parameter '.$param.' is not defined', MIDAS_INVALID_PARAMETER);
        }
      }
    }

  /**
   * Rename a request parameter's key to provide backward compatibility for existing WebAPIs .
   */
  private function _renameParamKey(&$args, $oldKey, $newKey, $oldKeyRequired = true)
    {
    if($oldKeyRequired)
      {
      $this->_validateParams($args, array($oldKey));
      }
    if(isset($args[$oldKey]))
      {
      $args[$newKey] = $args[$oldKey];
      unset($args[$oldKey]);
      }
    }

  /** Return the user dao */
  private function _getUser($args)
    {
    $authComponent = MidasLoader::loadComponent('Authentication', 'api');
    return $authComponent->getUser($args, $this->userSession->Dao);
    }

  /** Return the user dao */
  private function _callCoreApiMethod($args, $coreApiMethod, $hasReturn = true, $needAuth = true)
    {
    $authComponent = MidasLoader::loadComponent('Authentication', 'api');
    $ApiComponent = MidasLoader::loadComponent('Api');
    if($needAuth)
      {
      $userDao = $authComponent->getUser($args, $this->userSession->Dao);
      $rtn = $ApiComponent->$coreApiMethod($args, $userDao);
      }
    else
      {
      $rtn = $ApiComponent->$coreApiMethod($args);
      }
    if($hasReturn)
      {
      return $rtn;
      }
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
    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
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
    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
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
    else
      {
      // ensure the cached privacy status value is up to date
      $itempolicygroupModel->computePolicyStatus($item);
      }
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

    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
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
      $itemname = isset($args['itemname']) ? $args['itemname'] : $args['filename'];
      $description = isset($args['itemdescription']) ? $args['itemdescription'] : '';
      $item = $itemModel->createItem($itemname, $description, $folder);
      if($item === false)
        {
        throw new Exception('Create new item failed', MIDAS_INTERNAL_ERROR);
        }
      $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
      $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);

      if(isset($args['itemprivacy']))
        {
        $privacyCode = $this->_getValidPrivacyCode($args['itemprivacy']);
        }
      else
        {
        // Public by default
        $privacyCode = MIDAS_PRIVACY_PUBLIC;
        }
      $this->_setItemPrivacy($item, $privacyCode);
      }

    if(array_key_exists('checksum', $args))
      {
      // If we already have a bitstream with this checksum, create a reference and return blank token
      $bitstreamModel = MidasLoader::loadModel('Bitstream');
      $existingBitstream = $bitstreamModel->getByChecksum($args['checksum']);
      if($existingBitstream)
        {
        // User must have read access to the existing bitstream if they are circumventing the upload.
        // Otherwise an attacker could spoof the checksum and read a private bitstream with a known checksum.
        if($itemModel->policyCheck($existingBitstream->getItemrevision()->getItem(), $userDao, MIDAS_POLICY_READ))
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
    $item = $itemModel->load($itemid);

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
   * Create a new community or update an existing one using the uuid
   * @param token Authentication token
   * @param name The community name
   * @param description (Optional) The community description
   * @param uuid (Optional) Uuid of the community. If none is passed, will generate one.
   * @param privacy (Optional) Default 'Public', possible values [Public|Private].
   * @param canjoin (Optional) Default 'Everyone', possible values [Everyone|Invitation].
   * @return The community dao that was created
   */
  function communityCreate($args)
    {
    return $this->_callCoreApiMethod($args, 'communityCreate');
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
    return $this->_callCoreApiMethod($args, 'communityGet');
    }

  /**
   * Get the immediate children of a community (non-recursive)
   * @param token (Optional) Authentication token
   * @param id The id of the community
   * @return The folders in the community
   */
  function communityChildren($args)
    {
    return $this->_callCoreApiMethod($args, 'communityChildren');
    }

  /**
   * Return a list of all communities visible to a user
   * @param token (Optional) Authentication token
   * @return A list of all communities
   */
  function communityList($args)
    {
    return $this->_callCoreApiMethod($args, 'communityList');
    }

  /**
   * Delete a community. Requires admin privileges on the community
   * @param token Authentication token
   * @param id The id of the community
   */
  function communityDelete($args)
    {
    $this->_callCoreApiMethod($args, 'communityDelete', false);
    }

  /**
   * Create a folder or update an existing one if one exists by the uuid passed.
   * If a folder is requested to be created with the same parentid and name as
   * an existing folder, an exception will be thrown and no new folder will
   * be created.
   * @param token Authentication token
   * @param name The name of the folder to create
   * @param description (Optional) The description of the folder
   * @param uuid (Optional) Uuid of the folder. If none is passed, will generate one.
   * @param privacy (Optional) Possible values [Public|Private]. Default behavior is to inherit from parent folder.
   * @param reuseExisting (Optional) If this parameter is set, will just return the existing folder if there is one with the name provided
   * @param parentid The id of the parent folder. Set this to -1 to create a top level user folder.
   * @return The folder object that was created
   */
  function folderCreate($args)
    {
    return $this->_callCoreApiMethod($args, 'folderCreate');
    }

  /**
   * Get information about the folder
   * @param token (Optional) Authentication token
   * @param id The id of the folder
   * @return The folder object, including its parent object
   */
  function folderGet($args)
    {
    return $this->_callCoreApiMethod($args, 'folderGet');
    }

  /**
   * Get the immediate children of a folder (non-recursive)
   * @param token (Optional) Authentication token
   * @param id The id of the folder
   * @return The items and folders in the given folder
   */
  function folderChildren($args)
    {
    return $this->_callCoreApiMethod($args, 'folderChildren');
    }

  /**
   * Delete a folder. Requires admin privileges on the folder
   * @param token Authentication token
   * @param id The id of the folder
   */
  function folderDelete($args)
    {
    $this->_callCoreApiMethod($args, 'folderDelete', false);
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
    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
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
    return $this->_callCoreApiMethod($args, 'folderMove');
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
    $this->_renameParamKey($args, 'folder_id', 'id');
    return $this->_callCoreApiMethod($args, 'folderListPermissions');
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
    $this->_renameParamKey($args, 'folder_id', 'id');
    return $this->_callCoreApiMethod($args, 'folderSetPrivacyRecursive');
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
    $this->_renameParamKey($args, 'folder_id', 'id');
    return $this->_callCoreApiMethod($args, 'folderAddPolicygroup');
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
    $this->_renameParamKey($args, 'folder_id', 'id');
    return $this->_callCoreApiMethod($args, 'folderRemovePolicygroup');
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
    $this->_renameParamKey($args, 'folder_id', 'id');
    return $this->_callCoreApiMethod($args, 'folderAddPolicyuser');
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
    $this->_renameParamKey($args, 'folder_id', 'id');
    return $this->_callCoreApiMethod($args, 'folderRemovePolicyuser');
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
   * Check whether an item with the given name exists in the given folder
   * @param parentid The id of the parent folder
   * @param name The name of the item
   * @return array('exists' => bool)
   */
  function itemExists($args)
    {
    return $this->_callCoreApiMethod($args, 'itemExists');
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
   * @param privacy (Optional) [Public|Private], default will inherit from parent folder
   * @param updatebitstream (Optional) If set, the bitstream's name will be updated
      simultaneously with the item's name if and only if the item has already
      existed and its latest revision contains only one bitstream.
   * @return The item object that was created
   */
  function itemCreate($args)
    {
    return $this->_callCoreApiMethod($args, 'itemCreate');
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
    return $this->_callCoreApiMethod($args, 'itemGet');
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
    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
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
    $this->_callCoreApiMethod($args, 'itemDelete', false);
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
    return $this->_callCoreApiMethod($args, 'itemGetmetadata');
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
    $this->_renameParamKey($args, 'itemId', 'id');
    return $this->_callCoreApiMethod($args, 'itemSetmetadata');
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
    $this->_renameParamKey($args, 'itemid', 'id');
    return $this->_callCoreApiMethod($args, 'itemSetmultiplemetadata');
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
    $this->_renameParamKey($args, 'itemid', 'id');
    return $this->_callCoreApiMethod($args, 'itemDeletemetadata');
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
    $this->_renameParamKey($args, 'itemid', 'id');
    return $this->_callCoreApiMethod($args, 'itemDeletemetadataAll');
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
    return $this->_callCoreApiMethod($args, 'itemDuplicate');
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
    return $this->_callCoreApiMethod($args, 'itemShare');
    }

  /**
   * List the permissions on an item, requires Admin access to the item.
   * @param item_id The id of the item
   * @return A list with three keys: privacy, user, group; privacy will be the
     item's privacy string [Public|Private]; user will be a list of
     (user_id, policy, email); group will be a list of (group_id, policy, name).
     policy for user and group will be a policy string [Admin|Write|Read].
   */
  public function itemListPermissions($args)
    {
    $this->_renameParamKey($args, 'item_id', 'id');
    return $this->_callCoreApiMethod($args, 'itemListPermissions');
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
    return $this->_callCoreApiMethod($args, 'itemMove');
    }

  /**
   * Return all items
   * @param token (Optional) Authentication token
   * @param name The name of the item to search by
   * @return A list of all items with the given name
   */
  function itemSearchbyname($args)
    {
    return $this->_callCoreApiMethod($args, 'itemSearchbyname');
    }

  /**
   * Add an itempolicygroup to an item with the passed in group and policy;
     if an itempolicygroup exists for that group and item, it will be replaced
     with the passed in policy.
   * @param item_id The id of the item.
   * @param group_id The id of the group.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @return success = true on success.
   */
  function itemAddPolicygroup($args)
    {
    $this->_renameParamKey($args, 'item_id', 'id');
    return $this->_callCoreApiMethod($args, 'itemAddPolicygroup');
    }

  /**
   * Remove a itempolicygroup from a item with the passed in group if the
     itempolicygroup exists.
   * @param item_id The id of the item.
   * @param group_id The id of the group.
   * @return success = true on success.
   */
  function itemRemovePolicygroup($args)
    {
    $this->_renameParamKey($args, 'item_id', 'id');
    return $this->_callCoreApiMethod($args, 'itemRemovePolicygroup');
    }

  /**
   * Add a itempolicyuser to an item with the passed in user and policy;
     if an itempolicyuser exists for that user and item, it will be replaced
     with the passed in policy.
   * @param item_id The id of the item.
   * @param user_id The id of the targeted user to create the policy for.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @return success = true on success.
   */
  function itemAddPolicyuser($args)
    {
    $this->_renameParamKey($args, 'item_id', 'id');
    return $this->_callCoreApiMethod($args, 'itemAddPolicyuser');
    }

  /**
   * Remove an itempolicyuser from an item with the passed in user if the
     itempolicyuser exists.
   * @param item_id The id of the item.
   * @param user_id The id of the target user.
   * @return success = true on success.
   */
  function itemRemovePolicyuser($args)
    {
    $this->_renameParamKey($args, 'item_id', 'id');
    return $this->_callCoreApiMethod($args, 'itemRemovePolicyuser');

    }

  /**
   * Return a list of top level folders belonging to the user
   * @param token Authentication token
   * @return List of the user's top level folders
   */
  function userFolders($args)
    {
    return $this->_callCoreApiMethod($args, 'userFolders');
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
      if(!$userDao)
        {
        throw new Exception('Login failed', MIDAS_INVALID_PARAMETER);
        }
      }

    $instanceSalt = Zend_Registry::get('configGlobal')->password->prefix;
    if($authModule || $userModel->hashExists(hash($userDao->getHashAlg(), $instanceSalt.$userDao->getSalt().$password)))
      {
      if($userDao->getSalt() == '')
        {
        $passwordHash = $userModel->convertLegacyPasswordHash($userDao, $password);
        }
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
    return $this->_callCoreApiMethod($args, 'userList', true, false);
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
    $this->_renameParamKey($args, 'user_id', 'id', false);
    return $this->_callCoreApiMethod($args, 'userGet', true, false);
    }

  /**
   * Fetch the information about a bitstream
   * @param token (Optional) Authentication token
   * @param id The id of the bitstream
   * @return Bitstream dao
   */
  function bitstreamGet($args)
    {
    return $this->_callCoreApiMethod($args, 'bitstreamGet');
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
    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
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
    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
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
    return $this->_callCoreApiMethod($args, 'bitstreamEdit');
    }

  /**
   * Remove orphaned resources in the database.  Must be admin to use.
   */
  function adminDatabaseCleanup($args)
    {
    $userDao = $this->_getUser($args);

    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only admin users may call this method', MIDAS_INVALID_POLICY);
      }
    foreach(array('Folder', 'Item', 'ItemRevision', 'Bitstream') as $model)
      {
      MidasLoader::loadModel($model)->removeOrphans();
      }
    }

  /**
   * Delete a bitstream. Requires admin privileges on the containing item.
   * @param token Authentication token
   * @param id The id of the bitstream to delete
   */
  function bitstreamDelete($args)
    {
    $this->_callCoreApiMethod($args, 'bitstreamDelete', false);
    }

  /**
   * Get the metadata types stored in the system
   */
  function metadataTypesList()
    {
    $metadataModel = MidasLoader::loadModel('Metadata');
    return $metadataModel->getMetadataTypes();
    }

  /**
   * Get the metadata elements stored in the system for a given metadata type.
   * If the typename is specified, it will be used instead of the index.
   * @param type the metadata type index
   * @param typename (optional) the metadata type name
   */
  function metadataElementsList($args)
    {
    $metadataModel = MidasLoader::loadModel('Metadata');
    $type = $this->_checkMetadataTypeOrName($args, $metadataModel);
    return $metadataModel->getMetadataElements($type);
    }

  /**
   * Helper function for checking for a metadata type index or name and
   * handling the error conditions.
   */
  protected function _checkMetadataTypeOrName(&$args, &$metadataModel)
    {
    if(array_key_exists('typename', $args))
      {
      return $metadataModel->mapNameToType($args['typename']);
      }
    else if(array_key_exists('type', $args))
      {
      return $args['type'];
      }
    else
      {
      throw new Exception('Parameter type is not defined', MIDAS_INVALID_PARAMETER);
      }
    }

  /**
   * Get the metadata qualifiers stored in the system for a given metadata type
   * and element. If the typename is specified, it will be used instead of the
   * type.
   * @param type the metadata type index
   * @param element the metadata element under which the qualifier is collated
   * @param typename (optional) the metadata type name
   */
  function metadataQualifiersList($args)
    {
    $this->_validateParams($args, array('element'));
    $metadataModel = MidasLoader::loadModel('Metadata');
    $type = $this->_checkMetadataTypeOrName($args, $metadataModel);
    $element = $args['element'];
    return $metadataModel->getMetaDataQualifiers($type, $element);
    }

  /**
   * helper function to validate args of methods for adding or removing
   * users from groups.
   * @param group_id the group to add the user to
   * @param user_id the user to add to the group
   * @return an array of (groupModel, groupDao, groupUserDao)
   */
  protected function _validateGroupUserChangeParams($args)
    {
    $this->_validateParams($args, array('group_id', 'user_id'));

    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must be logged in to add a user to a group', MIDAS_INVALID_POLICY);
      }

    $groupId = $args['group_id'];
    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($groupId);
    if($group == false)
      {
      throw new Exception('This group does not exist', MIDAS_INVALID_PARAMETER);
      }

    $communityModel = MidasLoader::loadModel('Community');
    if(!$communityModel->policyCheck($group->getCommunity(), $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Community Admin permissions required.", MIDAS_INVALID_POLICY);
      }

    $groupUserId = $args['user_id'];
    $userModel = MidasLoader::loadModel('User');
    $groupUser = $userModel->load($groupUserId);
    if($groupUser == false)
      {
      throw new Exception('This user does not exist', MIDAS_INVALID_PARAMETER);
      }

    return array($groupModel, $group, $groupUser);
    }

  /**
   * Add a user to a group, returns 'success' => 'true' on success, requires
   * admin privileges on the community associated with the group.
   * @param group_id the group to add the user to
   * @param user_id the user to add to the group
   * @return success = true on success.
   */
  function groupAddUser($args)
    {
    list($groupModel, $group, $addedUser) = $this->_validateGroupUserChangeParams($args);
    $groupModel->addUser($group, $addedUser);
    return array('success' => 'true');
    }

  /**
   * Remove a user from a group, returns 'success' => 'true' on success, requires
   * admin privileges on the community associated with the group.
   * @param group_id the group to remove the user from
   * @param user_id the user to remove from the group
   * @return success = true on success.
   */
  function groupRemoveUser($args)
    {
    list($groupModel, $group, $removedUser) = $this->_validateGroupUserChangeParams($args);
    $groupModel->removeUser($group, $removedUser);
    return array('success' => 'true');
    }



  /**
   * add a group associated with a community, requires admin privileges on the
   * community.
   * @param community_id the id of the community the group will associate with
   * @param name the name of the new group
   * @return group_id of the newly created group on success.
   */
  function groupAdd($args)
    {
    $this->_validateParams($args, array('community_id', 'name'));

    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must be logged in to add group', MIDAS_INVALID_POLICY);
      }

    $communityModel = MidasLoader::loadModel('Community');
    $communityId = $args['community_id'];
    $community = $communityModel->load($communityId);
    if($community == false)
      {
      throw new Exception('This community does not exist', MIDAS_INVALID_PARAMETER);
      }
    if(!$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Community Admin permissions required.", MIDAS_INVALID_POLICY);
      }

    $name = $args['name'];
    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->createGroup($community, $name);

    return array('group_id' => $group->getGroupId());
    }

  /**
   * remove a group associated with a community, requires admin privileges on the
   * community.
   * @param group_id the id of the group to be removed
   * @return success = true on success.
   */
  function groupRemove($args)
    {
    $this->_validateParams($args, array('group_id'));

    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must be logged in to remove a group', MIDAS_INVALID_POLICY);
      }

    $groupId = $args['group_id'];
    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($groupId);
    if($group == false)
      {
      throw new Exception('This group does not exist', MIDAS_INVALID_PARAMETER);
      }

    $communityModel = MidasLoader::loadModel('Community');
    if(!$communityModel->policyCheck($group->getCommunity(), $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Community Admin permissions required.", MIDAS_INVALID_POLICY);
      }

    $groupModel->delete($group);
    return array('success' => 'true');
    }

  /**
   * list the users for a group, requires admin privileges on the community
   * assiated with the group
   * @param group_id id of group
   * @return array users => a list of user ids mapped to a two element list of
   * user firstname and lastname
   */
  function groupListUsers($args)
    {
    $this->_validateParams($args, array('group_id'));

    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_MANAGE_GROUPS));
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must be logged in to list users in a group', MIDAS_INVALID_POLICY);
      }

    $groupId = $args['group_id'];
    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($groupId);
    if($group == false)
      {
      throw new Exception('This group does not exist', MIDAS_INVALID_PARAMETER);
      }

    $communityModel = MidasLoader::loadModel('Community');
    if(!$communityModel->policyCheck($group->getCommunity(), $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Community Admin permissions required.", MIDAS_INVALID_POLICY);
      }

    $users = $group->getUsers();
    $userIdsToEmail = array();
    foreach($users as $user)
      {
      $userIdsToEmail[$user->getUserId()] = array('firstname' => $user->getFirstname(), 'lastname' => $user->getLastname());
      }
    return array('users' => $userIdsToEmail);
    }

  /**
   * list the groups for a community, requires admin privileges on the community
   * @param community_id id of community
   * @return array groups => a list of group ids mapped to group names
   */
  function communityListGroups($args)
    {
    $this->_validateParams($args, array('community_id'));

    $this->_requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_GROUPS));
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must be logged in to list groups in a community', MIDAS_INVALID_POLICY);
      }

    $communityId = $args['community_id'];
    $communityModel = MidasLoader::loadModel('Community');
    $community = $communityModel->load($communityId);
    if(!$community)
      {
      throw new Exception('Invalid community_id', MIDAS_INVALID_PARAMETER);
      }
    if(!$communityModel->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("Community Admin permissions required.", MIDAS_INVALID_POLICY);
      }

    $groups = $community->getGroups();
    $groupIdsToName = array();
    foreach($groups as $group)
      {
      $groupIdsToName[$group->getGroupId()] = $group->getName();
      }
    return array('groups' => $groupIdsToName);
    }


  } // end class
