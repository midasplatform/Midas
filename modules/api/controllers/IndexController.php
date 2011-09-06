<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

require_once BASE_PATH . '/modules/api/library/KwWebApiCore.php';

/** Main controller for the web api module */
class Api_IndexController extends Api_AppController
{
  public $_moduleModels = array('Userapi');
  public $_models = array('Community', 'ItemRevision', 'Item', 'User', "Folderpolicyuser", 'Folderpolicygroup', 'Folder');
  public $_components = array('Upload', 'Search', 'Uuid');

  var $kwWebApiCore = null;

  // Use this parameter to map API methods to your protected or private controller methods
  var $apicallbacks = array();

  // Config parameters
  var $apiEnable = '';
  var $apiSetup  = array();

  /** Before filter */
  function preDispatch()
    {
    parent::preDispatch();
    $this->apiEnable = true;

    // define api parameters
    $this->apiSetup['testing']         = Zend_Registry::get('configGlobal')->environment == "testing";
    $this->apiSetup['tmp_directory']   = $this->getTempDirectory();
    $modulesConfig = Zend_Registry::get('configsModules');
    $this->apiSetup['apiMethodPrefix'] = $modulesConfig['api']->methodprefix;

    $this->_setApiCallbacks($this->apiSetup['apiMethodPrefix']);
    $this->action = $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    switch($this->action)
      {
      case "rest":
      case "json":
      case "php_serial":
      case "xmlrpc":
      case "soap":
        $this->_initApiCommons();
        break;
      default:
        break;
      }
    ob_start();
    }

  /** Post dispatch */
  function postDispatch()
    {
    parent::postDispatch();
    ob_clean();
    }

  /** Index function */
  function indexAction()
    {
    $this->view->header = 'Web API';

    // Prepare the data used by the view
    $data = array(
      'api.enable'        => $this->apiEnable,
      'api.methodprefix'  => $this->apiSetup['apiMethodPrefix'],
      'api.listmethods'   => array_keys($this->apicallbacks),
      );

    $this->view->data = $data; // transfer data to the view
    $this->view->help = $this->helpContent;
    $this->view->serverURL = $this->getServerURL();
    }

  /** Set the call back API */
  private function _setApiCallbacks($apiMethodPrefix)
    {
    $apiMethodPrefix = KwWebApiCore::checkApiMethodPrefix($apiMethodPrefix);

    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = 'String version';
    $help['description'] = 'Return the version of MIDAS';
    $this->helpContent[$apiMethodPrefix.'version']                   = $help;
    $this->apicallbacks[$apiMethodPrefix.'version']                  = array(&$this, 'version');

    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = 'MIDAS info';
    $help['description'] = 'Get information about this MIDAS instance';
    $this->helpContent[$apiMethodPrefix.'info']                      = $help;
    $this->apicallbacks[$apiMethodPrefix.'info']                     = array(&$this, 'info');

    $help = array();
    $help['params'] = array();
    $help['params']['appname'] = 'Application Name';
    $help['params']['email'] = 'E-mail of the user';
    $help['params']['password'] = '(Optional) Password of the user';
    $help['params']['apikey'] = '(Optional) Key of the user';
    $help['example'] = array();
    $help['example']['?method=midas.login&appname=test&email=user@test.com&password=YourPass'] = 'Authenticate using password';
    $help['example']['?method=midas.login&appname=test&email=user@test.com&apikey=YourKey'] = 'Authenticate using key';
    $help['return'] = 'Token';
    $help['description'] = 'Authenticate an user';
    $this->helpContent[$apiMethodPrefix.'login']                   = $help;
    $this->apicallbacks[$apiMethodPrefix.'login']                  = array(&$this, 'login');

    $help = array();
    $help['params'] = array();
    $help['params']['id'] = 'Element Id';
    $help['params']['type'] = 'Element Type: bitstream='.MIDAS_RESOURCE_BITSTREAM.', item='.MIDAS_RESOURCE_ITEM.', revision='.MIDAS_RESOURCE_REVISION.', folder='.MIDAS_RESOURCE_FOLDER.', community='.MIDAS_RESOURCE_COMMUNITY;
    $help['example'] = array();
    $help['return'] = 'Universal identifier';
    $help['description'] = 'Get uuid';
    $this->helpContent[$apiMethodPrefix.'uuid.get'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'uuid.get']               = array(&$this, 'uuidGet');

    $help = array();
    $help['params'] = array();
    $help['params']['uuid'] = 'Universal identifier';
    $help['example'] = array();
    $help['return'] = 'Universal identifier (Dao)';
    $help['description'] = 'Get Universal identifier (contain resource id and type)';
    $this->helpContent[$apiMethodPrefix.'resource.get'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'resource.get']           = array(&$this, 'resourceGet');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['search'] = 'Search Query';
    $help['params']['order'] = '(Optional) name or date or view. Default view';
    $help['example'] = array();
    $help['return'] = 'Array of resource)';
    $help['description'] = 'Global search';
    $this->helpContent[$apiMethodPrefix.'resource.search'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'resource.search']       = array(&$this, 'resourcesSearch');

    $help = array();
    $help['params'] = array();
    $help['params']['uuid'] = 'Unique identifier of the resource';
    $help['example'] = array();
    $help['return'] = 'Array of resource';
    $help['description'] = 'Return the path to the root';
    $this->helpContent[$apiMethodPrefix.'path.to.root'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'path.to.root']           = array(&$this, 'pathToRoot');

    $help = array();
    $help['params'] = array();
    $help['params']['uuid'] = 'Unique identifier of the resource';
    $help['example'] = array();
    $help['return'] = 'Array of resource';
    $help['description'] = 'Return the path from the root';
    $this->helpContent[$apiMethodPrefix.'path.from.root'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'path.from.root']         = array(&$this, 'pathFromRoot');

    /* ----- Upload ------*/
    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = 'Token';
    $help['description'] = 'Generate an upload token';
    $this->helpContent[$apiMethodPrefix.'upload.generatetoken'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'upload.generatetoken']   = array(&$this, 'uploadApiGenerateToken');

    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Get offset';
    $this->helpContent[$apiMethodPrefix.'upload.getoffset'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'upload.getoffset']       = array(&$this, 'uploadApiGetOffset');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['mode'] = '(Optional) stream or multipart. Default: stream';
    $help['params']['folder_id'] = 'If set, will create a new item in the folder';
    $help['params']['item_id'] = 'If set, will create a new revision in the item';
    $help['params']['revision'] = 'If set, will create a new add files to a revision';
    $help['example'] = array();
    $help['return'] = 'Item information';
    $help['description'] = 'Upload a file (using put or post method)';
    $this->helpContent[$apiMethodPrefix.'upload.file'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'upload.file']       = array(&$this, 'uploadFile');


    /* ----- Community ------*/
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['name'] = '';
    $help['params']['description'] = '(Optional) Default \'\'';
    $help['params']['privacy'] = '(Optional) Default \'Public\'. '.MIDAS_COMMUNITY_PRIVATE.'= Private, '.MIDAS_COMMUNITY_PUBLIC.'= Public';
    $help['params']['canjoin'] = '(Optional) Default \'Everyone\'. '.MIDAS_COMMUNITY_INVITATION_ONLY.'= Invitation, '.MIDAS_COMMUNITY_CAN_JOIN.'= Everyone';
    $help['params']['uuid'] = '(Optional) Unique identifier. If set, will edit the community';
    $help['example'] = array();
    $help['return'] = 'Community Information';
    $help['description'] = 'Create or update a community';
    $this->helpContent[$apiMethodPrefix.'community.create'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'community.create']       = array(&$this, 'communityCreate');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the community';
    $help['example'] = array();
    $help['return'] = 'Community Information';
    $help['description'] = 'Get a community';
    $this->helpContent[$apiMethodPrefix.'community.get'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'community.get']          = array(&$this, 'communityGet');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['id'] = 'Id of the community';
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Delete a community';
    $this->helpContent[$apiMethodPrefix.'community.delete'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'community.delete']       = array(&$this, 'communityDelete');

     /* ----- Folder ------*/
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['name'] = '';
    $help['params']['description'] = '';
    $help['params']['parentid'] = '(Optional during update) Id of the parent folder ';
    $help['params']['uuid'] = '(Optional) Unique identifier. If set, will edit the folder';
    $help['example'] = array();
    $help['return'] = 'Folder information';
    $help['description'] = 'Create or edit a folder';
    $this->helpContent[$apiMethodPrefix.'folder.create'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'folder.create']      = array(&$this, 'folderCreate');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Delete a folder';
    $this->helpContent[$apiMethodPrefix.'folder.delete'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'folder.delete']      = array(&$this, 'folderDelete');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'Folder Information';
    $help['description'] = 'Get a folder';
    $this->helpContent[$apiMethodPrefix.'folder.get'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'folder.get']          = array(&$this, 'folderGet');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'File';
    $help['description'] = 'Download a folder';
    $this->helpContent[$apiMethodPrefix.'folder.download'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'folder.download']    = array(&$this, 'folderDownload');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'Array of Items and Folders';
    $help['description'] = 'Get folder Content';
    $this->helpContent[$apiMethodPrefix.'folder.content'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'folder.content']         = array(&$this, 'folderContent');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'Array of Folders';
    $help['description'] = 'Get folder Tree';
    $this->helpContent[$apiMethodPrefix.'folder.tree'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'folder.tree']         = array(&$this, 'folderTree');

    /** ------ ITEM --- */
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the item';
    $help['example'] = array();
    $help['return'] = 'Item Information';
    $help['description'] = 'Get an item information (contains its revisions information)';
    $this->helpContent[$apiMethodPrefix.'item.get'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'item.get']               = array(&$this, 'itemGet');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the item';
    $help['params']['revision'] = '(Optional) If not set, will download last revision';
    $help['example'] = array();
    $help['return'] = 'File';
    $help['description'] = 'Download an item';
    $this->helpContent[$apiMethodPrefix.'item.download'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'item.download']          = array(&$this, 'itemDownload');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['id'] = 'Id of the item';
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Delete an item (an its bitstream)';
    $this->helpContent[$apiMethodPrefix.'item.delete'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'item.delete']            = array(&$this, 'itemDelete');

    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['id'] = 'Id of the item';
    $help['params']['revision'] = '(Optional) Revision of the item';
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Get metadata';
    $this->helpContent[$apiMethodPrefix.'item.getmetadata'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'item.getmetadata']            = array(&$this, 'itemGetMetadata');

    /*
    $this->apicallbacks[$apiMethodPrefix.'newresources.get']       = array(&$this, 'newResourcesGet');     */
    }

  /** Initialize property allowing to generate XML */
  private function _initApiCommons()
    {
    // Disable debug information - Required to generate valid XML output
    //Configure::write('debug', 0);

    // Avoids render() call
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    // Instanciate Upload Module
    $this->uploadApi = new KwUploadAPI($this->apiSetup);
    }

  /** Return the user id given the arguments */
  private function _getUserId($args)
    {
    if(!array_key_exists('token', $args))
      {
      echo "Unable to find token";
      exit;
      }
    $token = $args['token'];
    $userapiDao = $this->Api_Userapi->getUserapiFromToken($token);
    if(!$userapiDao)
      {
      echo "Error token. Token does'nt exist";
      exit;
      }
    return $userapiDao->getUserId();
    }

  /** Return the user */
  private function _getUser($args)
    {
    $userid = $this->_getUserId($args);
    $userDao = $this->User->load($userid);
    return $userDao;
    }

  /** Controller action handling REST request */
  function restAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request_data = $this->_getAllParams();

    $method_name = $this->_getParam('method');
    if(!isset($method_name))
      {
      echo "Inconsistent request";
      exit;
      }

    $request_data = $this->_getAllParams();
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiRestCore($this->apiSetup, $this->apicallbacks, $request_data);
    }

  /** Controller action handling JSON request */
  function jsonAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request_data = $this->_getAllParams();

    $method_name = $this->_getParam('method');
    if(!isset($method_name))
      {
      echo "Inconsistent request";
      exit;
      }

    $request_data = $this->_getAllParams();
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiRestCore($this->apiSetup, $this->apicallbacks, array_merge($request_data, array('format' => 'json')));
    }

  /** Return the information */
  function version($args)
    {
    $data['version'] = $this->view->version;
    return $data;
    }

  /**
   * Return the information.  Currently this is the same behavior as the version function,
   * and is provided to maintain backward compatibility with MIDAS 2 for MIDASClient
   */
  function info($args)
    {
    $data['version'] = $this->view->version;
    return $data;
    }

  /** Return the user id given the arguments */
  function login($args)
    {
    if(!array_key_exists('email', $args))
      {
      throw new Exception('Parameter email is not defined', -150);
      }

    if(!array_key_exists('appname', $args))
      {
      throw new Exception('Parameter appname is not defined', -150);
      }

    $data['token'] = "";

    // If we have a password we generate an API key for the user
    if(array_key_exists('password', $args))
      {
      $userapiDao = $this->Api_Userapi->createKeyFromEmailPassword($args['appname'], $args['email'], $args['password']);

      if($userapiDao === false)
        {
        throw new Exception('Unable to authenticate.Please check credentials.', -150);
        }

      $args['apikey'] = $userapiDao->getApikey();
      }
    else
      {
      if(!array_key_exists('apikey', $args))
        {
        throw new Exception('Parameter apikey is not defined', -150);
        }
      }

    $email = $args['email'];
    $appname = $args['appname'];
    $apikey = $args['apikey'];
    $tokenDao = $this->Api_Userapi->getToken($email, $apikey, $appname);
    if(empty($tokenDao))
      {
      throw new Exception('Unable to authenticate.Please check credentials.', -150);
      }
    $data['token'] = $tokenDao->getToken();
    return $data;
    }

  /** Generate an unique upload token */
  function uploadApiGenerateToken($args)
    {
    return $this->uploadApi->generateToken($args);
    }

  /** Get the offset of the current upload */
  function uploadApiGetOffset($args)
    {
    return $this->uploadApi->getOffset($args);
    }

  /** Upload a Bitstream */
  function uploadFile($args)
    {
    if(!$this->_request->isPost() && !$this->_request->isPut())
      {
      throw new Exception('POST or PUT method required', -153);
      }

    $userDao = $this->_getUser($args);

    if($userDao == false)
      {
      throw new Exception('Please log in', -150);
      }

    if(array_key_exists('revision', $args) && array_key_exists('item_id', $args))
      {
      $item = $this->Item->load($args['item_id']);
      if($item == false)
        {
        throw new Exception('Unable to find item', -150);
        }
      if(!$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Permission error', -150);
        }
      $revision = $this->Item->getRevision($item, $args['revision']);
      if($revision == false)
        {
        throw new Exception('Unable to find revision', -150);
        }
      }
    elseif(array_key_exists('item_id', $args))
      {
      $item = $this->Item->load($args['item_id']);
      if($item == false)
        {
        throw new Exception('Unable to find item', -150);
        }
      if(!$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Permission error', -150);
        }
      }
    elseif(array_key_exists('folder_id', $args))
      {
      $folder = $this->Folder->load($args['folder_id']);
      if($folder == false)
        {
        throw new Exception('Unable to find folder', -150);
        }
      if(!$this->Folder->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Permission error', -150);
        }
      }
    else
      {
      throw new Exception('Parameter itemrevision_id or item_id or folder_id is not defined', -150);
      }

    $mode = array_key_exists('mode', $args) ? $args['mode'] : "stream";

    if($mode == "stream")
      {
      $token = $this->uploadApi->generateToken($args);
      $args['uploadtoken'] = $token['token'];
      $args['length'] = $args['size'];
      $result = $this->uploadApi->process($args);
      $filename = $result['filename'];
      $filepath = $result['path'];
      $filesize = $result['size'];
      }
    else if($mode == "multipart")
      {
      if(!array_key_exists('file', $args) || !array_key_exists('file', $_FILES))
        {
        throw new Exception('Parameter file is not defined', -150);
        }
      $file = $_FILES['file'];

      $filename = $file['name'];
      $filepath = $file['tmp_name'];
      $filesize = $file['size'];
      }
    else
      {
      throw new Exception('Invalid upload mode', -155);
      }

    if(isset($folder))
      {
      $item = $this->Component->Upload->createUploadedItem($userDao, $filename, $filepath, $folder);
      }
    else if(isset($revision))
      {
      $tmp = array($item->getKey(), $revision->getRevision());
      $item = $this->Component->Upload->createNewRevision($userDao, $filename, $filepath, $tmp, '');
      }
    else
      {
      $tmp = array($item->getKey(), 99999);//new revision
      $item = $this->Component->Upload->createNewRevision($userDao, $filename, $filepath, $tmp, '');
      }

    return $item->toArray();
    }


  /** Create a community */
  function communityCreate($args)
    {
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', -150);
      }

    if(!array_key_exists('name', $args))
      {
      throw new Exception('Parameter name is not defined', -150);
      }

    $name = $args['name'];

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';
    $record = false;
    if(!empty($uuid))
      {
      $record = $this->Component->Uuid->getByUid($uuid);
      if($record === false || !$this->Community->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception("This community doesn't exist  or you don't have the permissions.", 200);
        }
      }
    if($record != false && $record instanceof CommunityDao)
      {
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        $record->setPrivacy($args['privacy']);
        }
      if(isset($args['canjoin']))
        {
        $record->setCanJoin($args['canjoin']);
        }
      $this->Community->save($record);
      return $record->toArray();
      }
    else
      {
      $description = "";
      $privacy = MIDAS_COMMUNITY_PUBLIC;
      $canJoin = MIDAS_COMMUNITY_CAN_JOIN;
      if(isset($args['description']))
        {
        $description = $args['description'];
        }
      if(isset($args['privacy']))
        {
        $privacy = $args['privacy'];
        }
      if(isset($args['canjoin']))
        {
        $canJoin = $args['canjoin'];
        }
      $communityDao = $this->Community->createCommunity($name, $description, $privacy, $userDao, $canJoin);

      if($communityDao === false)
        {
        throw new Exception('Request failed', -200);
        }

      return $communityDao->toArray();
      }
    }

  /** Get a community's information */
  function communityGet($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $communityid = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $community = $this->Community->load($communityid);

    if($community === false || !$this->Community->policyCheck($community, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This community doesn't exist  or you don't have the permissions.", 200);
      }

    return $community->toArray();
    }

  /** Get folderContent */
  function folderContent($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $id = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $parent = $this->Folder->load($id);

    if($parent === false || !$this->Folder->policyCheck($parent, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist  or you don't have the permissions.", 200);
      }

    $folders = $this->Folder->getChildrenFoldersFiltered($parent, $this->userSession->Dao, MIDAS_POLICY_READ);
    $items = $this->Folder->getItemsFiltered($parent, $this->userSession->Dao, MIDAS_POLICY_READ);
    $jsonContent = array();
    foreach($folders as $folder)
      {
      $jsonContent[$folder->getParentId()]['folders'][] = $folder->toArray();
      unset($tmp);
      }
    foreach($items as $item)
      {
      $jsonContent[$item->parent_id]['items'][] = $item->toArray();
      unset($tmp);
      }

    return $jsonContent[$parent->getKey()];
    }

  /** Get the full tree from a community */
  function folderTree($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $id = $args['id'];
    $folder = $this->Folder->load($id);

    $userDao = $this->_getUser($args);
    $folders = $this->Folder->getAllChildren($folder, $userDao);

    $tree = array();
    $folderTree = array();

    foreach($folders as $folder)
      {
      $mainnode = $folder->toArray();

      if($folder->getParentId() != -1)
        {
        if(isset($folderTree[$folder->getParentId()]))
          {
          $mainnode['depth'] = $folderTree[$folder->getParentId()]['depth'] + 1;
          }
        else
          {
          $mainnode['depth'] = 1;
          }
        }
      // Cut the name to fit in the tree
      $maxsize = 24 - ($mainnode['depth'] * 2.5);
      if(strlen($mainnode['name']) > $maxsize)
        {
        $mainnode['name'] = substr($mainnode['name'], 0, $maxsize).'...';
        }
      $folderTree[$folder->getKey()] = $mainnode;
      if($folder->getParentId() == -1)
        {
        $tree[] = &$folderTree[$folder->getKey()];
        }
      else
        {
        $tree[$folder->getParentId()][] = &$folderTree[$folder->getKey()];
        }
      }
    return $tree;
    } //_CommunityTree

  /** Get information about the folder */
  function folderGet($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $id = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $folder = $this->Folder->load($id);

    if($folder === false || !$this->Folder->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", 200);
      }

    return $folder->toArray();
    }

  /** Create a folder */
  function folderCreate($args)
    {
    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', -150);
      }

    if(!array_key_exists('name', $args))
      {
      throw new Exception('Parameter name is not defined', -150);
      }
    if(!array_key_exists('description', $args))
      {
      throw new Exception('Parameter name is not defined', -150);
      }

    $name = $args['name'];
    $description = $args['description'];

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';
    $record = false;
    if(!empty($uuid))
      {
      $record = $this->Component->Uuid->getByUid($uuid);
      if($record === false || !$this->Folder->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception("This folder doesn't exist  or you don't have the permissions.", 200);
        }
      }
    if($record != false && $record instanceof FolderDao)
      {
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        $record->setPrivacy($args['privacy']);
        }
      $this->Folder->save($record);
      return $record->toArray();
      }
    else
      {
      if(!array_key_exists('parentid', $args))
        {
        throw new Exception('Parameter parentid is not defined', -150);
        }
      $parentid = $args['parentid'];
      $folder = $this->Folder->load($parentid);
      if($folder == false)
        {
        throw new Exception('Parent doesn\'t exit', -150);
        }
      $new_folder = $this->Folder->createFolder($name, $description, $folder);
      if($new_folder === false)
        {
        throw new Exception('Request failed', -200);
        }
      $policyGroup = $folder->getFolderpolicygroup();
      $policyUser = $folder->getFolderpolicyuser();
      foreach($policyGroup as $policy)
        {
        $group = $policy->getGroup();
        $policyValue = $policy->getPolicy();
        $this->Folderpolicygroup->createPolicy($group, $new_folder, $policyValue);
        }
      foreach($policyUser as $policy)
        {
        $user = $policy->getUser();
        $policyValue = $policy->getPolicy();
        $this->Folderpolicyuser->createPolicy($user, $new_folder, $policyValue);
        }

      return $new_folder->toArray();
      }
    }

  /** Return the abstract given an item id */
  function itemAbstractGet($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $itemid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($itemid, $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $abstract = $this->Item->GetAbstract($itemid);
    return array($abstract);
    }

  /** Get the item */
  function itemGet($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $itemid = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $item = $this->Item->load($itemid);

    if($item === false || !$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist  or you don't have the permissions.", 200);
      }

    $itemArray = $item->toArray();
    $revisions = $item->getRevisions();
    $revisionsArray = array();
    foreach($revisions as $revision)
      {
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
    return $itemArray;
    }

  /** Get the item's metadata */
  function itemGetMetadata($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $itemid = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $item = $this->Item->load($itemid);

    if($item === false || !$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist  or you don't have the permissions.", 200);
      }

    if(isset($args['revision']))
      {
      $revisionNumber = $args['revision'];
      $revisions = $item->getRevisions();
      foreach($revisions as $revision)
        {
        if($revisionNumber == $revision->getRevision())
          {
          $revisionDao = $revision;
          break;
          }
        }
      }

    if(!isset($revisionDao))
      {
      $revisionDao = $this->Item->getLastRevision($item);
      }

    $metadata = $this->ItemRevision->getMetadata($revisionDao);
    $metadataArray = array();
    foreach($metadata as $m)
      {
      $metadataArray[] = $m->toArray();
      }
    return $metadataArray;
    }

  /** Download a folder */
  function folderDownload($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $id = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $folder = $this->Folder->load($id);

    if($folder === false || !$this->Folder->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist  or you don't have the permissions.", 200);
      }

    $this->_redirect('/download/?folders='.$folder->getKey());
    }

  /** Download an item */
  function itemDownload($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $id = $args['id'];

    if(array_key_exists('token', $args))
      {
      $userDao = $this->_getUser($args);
      }
    else
      {
      $userDao = false;
      }

    $item = $this->Item->load($id);

    if($item === false || !$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This item doesn't exist  or you don't have the permissions.", 200);
      }

    if(isset($args['revision']))
      {
      $this->_redirect('/download/?items='.$item->getKey().','.$args['revision']);
      }
    else
      {
      $this->_redirect('/download/?items='.$item->getKey());
      }
    }

  /** Return all locations for a bitstream */
  function bitstreamLocations($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $bitstreamid = $args['id'];
    if(!is_numeric($bitstreamid))
      {
      throw new Exception('Invalid id parameter', -150);
      }
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid, $userid, MIDAS_RESOURCE_BITSTREAM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }
    return $this->Bitstream->getLocations($bitstreamid);
    }

  /** Download a bitstream */
  function bitstreamDownload($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $bitstreamid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid, $userid, MIDAS_RESOURCE_BITSTREAM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $location = '';
    if(array_key_exists('location', $args) && is_numeric($args['location']))
      {
      $location = '?location='.$args['location']; //choose a particular assetstore
      }
    if($userid)
      {
      $this->Session->write('User', $userid);
      }
    // must call ob_end_clean before we forward to bitstream/download or bitstream/view
    ob_end_clean();
    $this->requestAction('/bitstream/view/'.$bitstreamid.$location, array('return'));
    exit();
    }

  /** Download a bitstream by its checksum */
  function bitstreamDownloadByHash($args)
    {
    if(!array_key_exists('hash', $args))
      {
      throw new Exception('Parameter hash is not defined', -150);
      }
    $name = array_key_exists('name', $args) ? '/'.$args['name'] : '';
    $alg = array_key_exists('algorithm', $args) ? $args['algorithm'] : 'MD5';
    $alg = strtoupper($alg); //in case they pass in 'md5' or 'sha1'
    $hash = $args['hash'];

    $bitstreamid = $this->Bitstream->getByHash($hash, $alg);

    if($bitstreamid === false)
      {
      throw new Exception('No bitstream exists with'.$alg.' = '.$hash, -150);
      }
    if(array_key_exists('checkExistsOnly', $args))
      {
      return array('exists' => 'true');
      }
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid, $userid, MIDAS_RESOURCE_BITSTREAM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    // must call ob_end_clean before we forward to bitstream/download or bitstream/view
    ob_end_clean();
    $this->requestAction('/bitstream/view/'.$bitstreamid.$name, array('return'));
    exit();
    }

  /** Get bitstream info */
  function bitstreamGet($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $bitstreamid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid, $userid, MIDAS_RESOURCE_BITSTREAM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }
    $data['id'] = $bitstreamid;
    $data['uuid'] = $this->Bitstream->getUuid($bitstreamid);
    $data['parent'] = $this->Bitstream->getItemId($bitstreamid);
    $data['hasAgreement'] = $this->Bitstream->hasAgreement($bitstreamid) ? '1' : '0';
    $data['name'] = $this->Bitstream->getName($bitstreamid);
    $data['description'] = $this->Bitstream->getDescription($bitstreamid);
    $data['size'] = $this->Bitstream->getSizeInBytes($bitstreamid);
    $data['format'] = $this->Bitstream->getBitstreamFormatId($bitstreamid);
    return $data;
    }

  /** Get uuid for a resource */
  function uuidGet($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }
    if(!array_key_exists('type', $args))
      {
      throw new Exception('Parameter type is not defined', -150);
      }

    $id = $args['id'];
    $type = $args['type'];
    $modelLoad = new MIDAS_ModelLoader();
    switch($type)
      {
      case MIDAS_RESOURCE_ASSETSTORE:
        $model = $modelLoad->loadModel('Assetstore');
        break;
      case MIDAS_RESOURCE_BITSTREAM:
        $model = $modelLoad->loadModel('Bitstream');
        break;
      case MIDAS_RESOURCE_ITEM:
        $model = $modelLoad->loadModel('Item');
        break;
      case MIDAS_RESOURCE_COMMUNITY:
        $model = $modelLoad->loadModel('Community');
        break;
      case MIDAS_RESOURCE_REVISION:
        $model = $modelLoad->loadModel('ItemRevision');
        break;
      case MIDAS_RESOURCE_FOLDER:
        $model = $modelLoad->loadModel('Folder');
        break;
      case MIDAS_RESOURCE_USER:
        $model = $modelLoad->loadModel('User');
        break;
      default :
        throw new Zend_Exception("Undefined type");
      }
    $dao = $model->load($id);

    if($dao == false)
      {
      throw new Exception('Invalid resource type or id.', -151);
      }

    $uuid = $dao->getUuid();

    if($uuid == false)
      {
      throw new Exception('Invalid resource type or id.', -151);
      }

    return $uuid;
    }

  /** Get resource given a uuid */
  function resourceGet($args)
    {
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }

    $uuid = $args['uuid'];
    $resource = $this->Component->Uuid->getByUid($uuid);

    if($resource == false)
      {
      throw new Exception('No resource for the given UUID.', -151);
      }

    return $resource->toArray();
    }

  /** Returns a path of uuids from the root community to the given node */
  function pathFromRoot ($args)
    {
    return array_reverse($this->pathToRoot($args));
    }

  /** Returns a path of uuids from the given node to the root community */
  function pathToRoot ($args)
    {
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }

    $folder = $this->Component->Uuid->getByUid($args['uuid']);

    $return = array();
    $return[] = $folder->toArray();

    if($folder == false)
      {
      throw new Exception('No resource for the given UUID.', -151);
      }

    if(!$folder instanceof FolderDao)
      {
      throw new Exception('Should be a folder.', -150);
      }

    $parent = $folder->getParent();
    while($parent !== false)
      {
      $return[] = $parent->toArray();
      $parent = $parent->getParent();
      }
    return $return;
    }

  /** Search resources for the given words */
  function resourcesSearch($args)
    {
    if(!array_key_exists('search', $args))
      {
      throw new Exception('Parameter search is not defined', -150);
      }
    $userDao = $this->_getUser($args);

    $order = 'view';
    if(isset($args['order']))
      {
      $order = $args['order'];
      }
    return $this->Component->Search->searchAll($userDao, $args['search'], $order);
    }

  /** Return all changed resources since a given timestamp, and provide a new timestamp */
  function newResourcesGet($args)
    {
    if(array_key_exists('since', $args))
      {
      App::import('Model', 'Resourcelog');
      $Resourcelog = new Resourcelog();
      $results = $Resourcelog->getAllModifiedSince($args['since']);
      foreach($results as $result)
        {
        $data['modified'][] = $this->Api->getUuid($result['resource_id'], $result['resource_id_type']);
        }
      }
    $data['timestamp'] = $this->Api->getCurrentSQLTime();
    return $data;
    }

  /** Delete a community */
  function communityDelete($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', -150);
      }
    $id = $args['id'];
    $community = $this->Community->load($id);

    if($community === false || !$this->Community->policyCheck($community, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This community doesn't exist  or you don't have the permissions.", 200);
      }

    $this->Community->delete($community);
    }

  /** Delete Folder*/
  function folderDelete($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', -150);
      }
    $id = $args['id'];
    $folder = $this->Folder->load($id);

    if($folder === false || !$this->Folder->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This community doesn't exist  or you don't have the permissions.", 200);
      }

    $this->Folder->delete($folder);
    }

  /** Delete an item */
  function itemDelete($args)
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }

    $userDao = $this->_getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', -150);
      }
    $id = $args['id'];
    $item = $this->Item->load($id);

    if($item === false || !$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This item doesn't exist  or you don't have the permissions.", 200);
      }

    $this->Item->delete($item);
    }
  } // end class
?>
