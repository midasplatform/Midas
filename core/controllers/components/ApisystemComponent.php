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

/** These are the implementations of the web api methods for system*/
class ApisystemComponent extends AppComponent
  {
  /**
   * Get the server version
   * @path /system/version
   * @http GET
   * @return Server version in the form {major}.{minor}.{patch}
   */
  public function version($args)
    {
    return array('version' => Zend_Registry::get('configDatabase')->version);
    }

  /**
   * Get the enabled modules on the server
   * @path /system/module
   * @http GET
   * @return List of enabled modules on the server
   */
  public function modulesList($args)
    {
    return array('modules' => array_keys(Zend_Registry::get('configsModules')));
    }

  /**
   * List all available web api resources on the server
   * @path /system/resource
   * @http GET
   * @return List of api resources names and their corresponding url
   */
  public function resourcesList($args)
    {
    $data = array();
    $docsComponent = MidasLoader::loadComponent('Apidocs');
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $baseUrl = $request->getScheme().'://'.$request->getHttpHost().$request->getBaseUrl();;
    $apiroot = $baseUrl.'/rest';
    $resources = $docsComponent->getEnabledResources();
    foreach($resources as $resource)
      {
      if(strpos($resource, '/') > 0)
        {
        $resource = '/' . $resource;
        }
      $data[$resource] = $apiroot . $resource;
      }
    return array('resources' => $data);

    }

  /**
   * Get the server information including version, modules enabled,
     and available resources
   * @path /system/info
   * @http GET
   * @return Server information
   */
  public function info($args)
    {
    return array_merge($this->version($args),
                       $this->modulesList($args),
                       $this->resourcesList($args));
    }

  /**
   * Login as a user using a web api key
   * @path /system/login
   * @http GET
   * @param appname The application name
   * @param email The user email
   * @param apikey The api key corresponding to the given application name
   * @return A web api token that will be valid for a set duration
   */
  function login($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('email', 'appname', 'apikey'));

    $data['token'] = '';
    $email = $args['email'];
    $appname = $args['appname'];
    $apikey = $args['apikey'];
    $Userapi = MidasLoader::loadModel('Userapi');
    $tokenDao = $Userapi->getToken($email, $apikey, $appname);
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
   * Returns the user's default API key given their username and password.
   * @path /system/defaultapikey
   * @http POST
   * @param email The user's email
   * @param password The user's password
   * @return Array with a single key, 'apikey', whose value is the user's default api key
   */
  function userApikeyDefault($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('email', 'password'));
    $request = Zend_Controller_Front::getInstance()->getRequest();
    if(!$request->isPost())
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
    $userApiModel = MidasLoader::loadModel('Userapi');
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
   * Generate a unique upload token.  Either <b>itemid</b> or <b>folderid</b> is required,
     but both are not allowed.
   * @path /system/uploadtoken
   * @http GET
   * @param useSession (Optional) Authenticate using the current Midas session
   * @param token (Optional) Authentication token
   * @param itemid (Optional)
            The id of the item to upload into.
   * @param folderid (Optional)
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
             then call the <b>/item (POST)</b> api instead.
             If <b>checksum</b> is passed and the token returned is blank, the
             server already has this file and there is no need to follow this
             call with a call to <b>/system/upload</b>, as the passed in
             file will have been added as a bitstream to the item's latest
             revision, creating a new revision if one doesn't exist.
   */
  function uploadGeneratetoken($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('filename'));
    if(!array_key_exists('itemid', $args) && !array_key_exists('folderid', $args))
      {
      throw new Exception('Parameter itemid or folderid must be defined', MIDAS_INVALID_PARAMETER);
      }
    if(array_key_exists('itemid', $args) && array_key_exists('folderid', $args))
      {
      throw new Exception('Parameter itemid or folderid must be defined, but not both', MIDAS_INVALID_PARAMETER);
      }

    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
    $userDao = $apihelperComponent->getUser($args);
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
        $privacyCode = $apihelperComponent->getValidPrivacyCode($args['itemprivacy']);
        }
      else
        {
        // Public by default
        $privacyCode = MIDAS_PRIVACY_PUBLIC;
        }
      $apihelperComponent->setItemPrivacy($item, $privacyCode);
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
    $apiSetup = $apihelperComponent->getApiSetup();
    $uploadComponent->setTestingMode($apiSetup['testing']);
    $uploadComponent->setTmpDirectory($apiSetup['tmpDirectory']);
    return $uploadComponent->generateToken($args, $userDao->getKey().'/'.$item->getKey());
    }

  /**
   * Upload a file to the server. PUT or POST is required.
     Will add the file as a bitstream to the item that was specified when
     generating the upload token in a new revision to that item, unless
     <b>revision</b> param is set.
   * @path /system/upload
   * @http POST
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
   * @return The item information of the item created or changed.
   */
  function uploadPerform($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('uploadtoken', 'filename', 'length'));
    $request = Zend_Controller_Front::getInstance()->getRequest();
    if(!$request->isPost() && !$request->isPut())
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
    $apiSetup = $apihelperComponent->getApiSetup();
    $httpUploadComponent->setTestingMode($apiSetup['testing']);
    $httpUploadComponent->setTmpDirectory($apiSetup['tmpDirectory']);

    if(array_key_exists('testingmode', $args))
      {
      $httpUploadComponent->setTestingMode(true);
      $args['localinput'] = $apiSetup['tmpDirectory'].'/'.$args['filename'];
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
   * Get the size of a partially completed upload
   * @path /system/uploadeoffset
   * @http GET
   * @param uploadtoken The upload token for the file
   * @return [offset] The size of the file currently on the server
   */
  function uploadGetoffset($args)
    {
    $uploadComponent = MidasLoader::loadComponent('Httpupload');
    $apiSetup = $apihelperComponent->getApiSetup();
    $uploadComponent->setTestingMode($apiSetup['testing']);
    $uploadComponent->setTmpDirectory($apiSetup['tmpDirectory']);
    return $uploadComponent->getOffset($args);
    }

  /**
   * Get the metadata qualifiers stored in the system for a given metadata type
   * and element. If the typename is specified, it will be used instead of the
   * type.
   * @path /system/metadataqualifiers
   * @http GET
   * @param type the metadata type index
   * @param element the metadata element under which the qualifier is collated
   * @param typename (Optional) the metadata type name
   */
  function metadataQualifiersList($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('element'));
    $metadataModel = MidasLoader::loadModel('Metadata');
    $type = $apihelperComponent->checkMetadataTypeOrName($args, $metadataModel);
    $element = $args['element'];
    return $metadataModel->getMetaDataQualifiers($type, $element);
    }

  /**
   * Get the metadata types stored in the system
   * @path /system/metadatatypes
   * @http GET
   */
  function metadataTypesList()
    {
    $metadataModel = MidasLoader::loadModel('Metadata');
    return $metadataModel->getMetadataTypes();
    }

  /**
   * Get the metadata elements stored in the system for a given metadata type.
   * If the typename is specified, it will be used instead of the index.
   * @path /system/metadaelements
   * @http GET
   * @param type the metadata type index
   * @param typename (Optional) the metadata type name
   */
  function metadataElementsList($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $metadataModel = MidasLoader::loadModel('Metadata');
    $type = $apihelperComponent->checkMetadataTypeOrName($args, $metadataModel);
    return $metadataModel->getMetadataElements($type);
    }

  /**
   * Remove orphaned resources in the database.  Must be admin to use.
   * @path /system/databasecleanup
   * @http POST
   * @param useSession (Optional) Authenticate using the current Midas session
   * @param token (Optional) Authentication token
   */
  function adminDatabaseCleanup($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $userDao = $apihelperComponent->getUser($args);

    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only admin users may call this method', MIDAS_INVALID_POLICY);
      }
    foreach(array('Folder', 'Item', 'ItemRevision', 'Bitstream') as $model)
      {
      MidasLoader::loadModel($model)->removeOrphans();
      }
    }

  } // end class
