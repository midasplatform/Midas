<?php

//App::import('Vendor', 'kwwebapi');
require_once BASE_PATH . '/modules/api/library/KwWebApiCore.php';

class Api_IndexController extends Api_AppController
{
  public $_moduleModels=array('Userapi');
  public $_models=array('Community', 'ItemRevision', 'Item', 'User', 'Uniqueidentifier', "Folderpolicyuser", 'Folderpolicygroup', 'Folder');

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
    $this->apiSetup['testing']         = Zend_Registry::get('configGlobal')->environment=="testing";
    $this->apiSetup['tmp_directory']   = $this->getTempDirectory();
    $modulesConfig=Zend_Registry::get('configsModules');
    $this->apiSetup['apiMethodPrefix'] = $modulesConfig['api']->methodprefix;

    $this->_SetApiCallbacks( $this->apiSetup['apiMethodPrefix'] );
    $this->action=$actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    switch($this->action)
      {
      case "rest":
      case "json":
      case "php_serial":
      case "xmlrpc":
      case "soap":
        $this->_InitApiCommons();
        break;
      }
    ob_start();    
    }

  function postDispatch()
    {
    parent::postDispatch();
    ob_clean();
    }

  /** Index function */
  function indexAction()
    {
    $this->view->header='Web API';

    // Prepare the data used by the view
    $data = array(
      'api.enable'        => $this->apiEnable,
      'api.methodprefix'  => $this->apiSetup['apiMethodPrefix'],
      'api.listmethods'   => array_keys($this->apicallbacks),
      );

    $this->view->data= $data; // transfer data to the view
    $this->view->help = $this->helpContent;
    }

   /** Set the call back API */
  function _SetApiCallbacks( $apiMethodPrefix )
    {
    $apiMethodPrefix = KwWebApiCore::checkApiMethodPrefix( $apiMethodPrefix );
    
    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = 'String version';
    $help['description'] = 'Return the version of MIDAS';
    $this->helpContent[$apiMethodPrefix.'version']                   = $help;
    $this->apicallbacks[$apiMethodPrefix.'version']                   = array(&$this, '_Version');    
    
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
    $this->apicallbacks[$apiMethodPrefix.'login']                  = array(&$this, '_Login');
    
    $help = array();
    $help['params'] = array();
    $help['params']['id'] = 'Element Id';
    $help['params']['type'] = 'Element Type: bitstream='.MIDAS_RESOURCE_BITSTREAM.', item='.MIDAS_RESOURCE_ITEM.', revision='.MIDAS_RESOURCE_REVISION.', folder='.MIDAS_RESOURCE_FOLDER.', community='.MIDAS_RESOURCE_COMMUNITY;
    $help['example'] = array();
    $help['return'] = 'Universal identifier';
    $help['description'] = 'Get uuid';
    $this->helpContent[$apiMethodPrefix.'uuid.get'] = $help;    
    $this->apicallbacks[$apiMethodPrefix.'uuid.get']               = array(&$this, '_UuidGet');
    
    $help = array();
    $help['params'] = array();
    $help['params']['uuid'] = 'Universal identifier';
    $help['example'] = array();
    $help['return'] = 'Universal identifier (Dao)';
    $help['description'] = 'Get Universal identifier (contain resource id and type)';
    $this->helpContent[$apiMethodPrefix.'resource.get'] = $help; 
    $this->apicallbacks[$apiMethodPrefix.'resource.get']           = array(&$this, '_ResourceGet');
        
    
    
    /* ----- Upload ------*/
    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = 'Token';
    $help['description'] = 'Generate an upload token';
    $this->helpContent[$apiMethodPrefix.'upload.generatetoken'] = $help;
    $this->apicallbacks[$apiMethodPrefix.'upload.generatetoken']   = array(&$this, '_UploadApiGenerateToken');
    
    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Get offset';
    $this->helpContent[$apiMethodPrefix.'upload.getoffset'] = $help;    
    $this->apicallbacks[$apiMethodPrefix.'upload.getoffset']       = array(&$this, '_UploadApiGetOffset');
   
    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Upload a bitstream';
    $this->helpContent[$apiMethodPrefix.'upload.bitstream'] = $help;    
    $this->apicallbacks[$apiMethodPrefix.'upload.bitstream']       = array(&$this, '_UploadBitstream');
    
    
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
    $this->apicallbacks[$apiMethodPrefix.'community.create']       = array(&$this, '_CommunityCreate');
    
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the community';
    $help['example'] = array();
    $help['return'] = 'Community Information';
    $help['description'] = 'Get a community';
    $this->helpContent[$apiMethodPrefix.'community.get'] = $help;  
    $this->apicallbacks[$apiMethodPrefix.'community.get']          = array(&$this, '_CommunityGet');
    
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['id'] = 'Id of the community';
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Delete a community';
    $this->helpContent[$apiMethodPrefix.'community.delete'] = $help;  
    $this->apicallbacks[$apiMethodPrefix.'community.delete']       = array(&$this, '_CommunityDelete');
    
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
    $this->apicallbacks[$apiMethodPrefix.'folder.create']      = array(&$this, '_FolderCreate');
    
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = 'Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = '';
    $help['description'] = 'Delete a folder';
    $this->helpContent[$apiMethodPrefix.'folder.delete'] = $help; 
    $this->apicallbacks[$apiMethodPrefix.'folder.delete']      = array(&$this, '_FolderDelete');
    
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'Folder Information';
    $help['description'] = 'Get a folder';
    $this->helpContent[$apiMethodPrefix.'folder.get'] = $help;  
    $this->apicallbacks[$apiMethodPrefix.'folder.get']          = array(&$this, '_FolderGet');
    
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'File';
    $help['description'] = 'Download a folder';
    $this->helpContent[$apiMethodPrefix.'folder.download'] = $help;  
    $this->apicallbacks[$apiMethodPrefix.'folder.download']    = array(&$this, '_FolderDownload');
    
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'Array of Items and Folders';
    $help['description'] = 'Get folder Content';
    $this->helpContent[$apiMethodPrefix.'folder.content'] = $help;  
    $this->apicallbacks[$apiMethodPrefix.'folder.content']         = array(&$this, '_FolderContent');
    
    /* TODO
    $help = array();
    $help['params'] = array();
    $help['params']['token'] = '(Optional) Authentification token';
    $help['params']['id'] = 'Id of the folder';
    $help['example'] = array();
    $help['return'] = 'Array of Items and Folders';
    $help['description'] = 'Get folder Tree';
    $this->helpContent[$apiMethodPrefix.'folder.tree'] = $help;  
    $this->apicallbacks[$apiMethodPrefix.'folder.tree']         = array(&$this, '_FolderTree');    
    */
    
/*
    

    $this->apicallbacks[$apiMethodPrefix.'item.create']            = array(&$this, '_ItemCreate');
    $this->apicallbacks[$apiMethodPrefix.'item.get']               = array(&$this, '_ItemGet');
    $this->apicallbacks[$apiMethodPrefix.'item.abstract.get']      = array(&$this, '_ItemAbstractGet');
    $this->apicallbacks[$apiMethodPrefix.'item.title.get']         = array(&$this, '_ItemTitleGet');
    $this->apicallbacks[$apiMethodPrefix.'item.resource.create']   = array(&$this, '_ItemResourceCreate');
    $this->apicallbacks[$apiMethodPrefix.'item.download']          = array(&$this, '_ItemDownload');
    $this->apicallbacks[$apiMethodPrefix.'item.delete']            = array(&$this, '_ItemDelete');
    $this->apicallbacks[$apiMethodPrefix.'item.keys']              = array(&$this, '_ItemKeys');

    $this->apicallbacks[$apiMethodPrefix.'bitstream.download']     = array(&$this, '_BitstreamDownload');
    $this->apicallbacks[$apiMethodPrefix.'bitstream.by.hash']      = array(&$this, '_BitstreamDownloadByHash');
    $this->apicallbacks[$apiMethodPrefix.'bitstream.get']          = array(&$this, '_BitstreamGet');
    $this->apicallbacks[$apiMethodPrefix.'bitstream.delete']       = array(&$this, '_BitstreamDelete');
    $this->apicallbacks[$apiMethodPrefix.'bitstream.count']        = array(&$this, '_BitstreamCount');
    $this->apicallbacks[$apiMethodPrefix.'bitstream.keyfile']      = array(&$this, '_BitstreamKeyFile');
    $this->apicallbacks[$apiMethodPrefix.'bitstream.locations']    = array(&$this, '_BitstreamLocations');


    $this->apicallbacks[$apiMethodPrefix.'resources.search']       = array(&$this, '_ResourcesSearch');
    $this->apicallbacks[$apiMethodPrefix.'newresources.get']       = array(&$this, '_NewResourcesGet');
    $this->apicallbacks[$apiMethodPrefix.'path.to.root']           = array(&$this, '_PathToRoot');
    $this->apicallbacks[$apiMethodPrefix.'path.from.root']         = array(&$this, '_PathFromRoot');
    $this->apicallbacks[$apiMethodPrefix.'convert.path.to.id']     = array(&$this, '_ConvertPathToId');

    $this->apicallbacks[$apiMethodPrefix.'check.user.agreement']   = array(&$this, '_CheckUserAgreement');

    
    

    /*
    // Load the plugins API
    foreach($this->getPlugins() as $plugin=>$enable)
      {
      if($enable)
        {
        //$this->loadControllers(array("project.api"));
        $pluginname = ucfirst($plugin);
        App::Import('Controller',$pluginname.'.'.$pluginname.'Api');
        $classname = $pluginname.'ApiController';
        if(class_exists($classname))
          {
          $apicontroller = new $classname;
          $apicontroller->setMIDASAPI($this);
          $this->apicallbacks = array_merge($this->apicallbacks,$apicontroller->getCallbacks());
          }
        }
      } // end loading plugins callbacks
     * *
     */
    }

  /** Initialize property allowing to generate XML */
  function _InitApiCommons()
    {
    // Disable debug information - Required to generate valid XML output
    //Configure::write('debug', 0);

    // Avoids render() call
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    // Instanciate Upload Module
    $this->uploadApi = new KwUploadAPI($this->apiSetup);
    }

  /** Controller action handling REST request */
  function restAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request_data = $this->_getAllParams();
      
    $method_name = $this->_getParam('method');
    if( !isset ($method_name))
      {
      echo "Inconsistent request";
      exit;
      }
    
    $request_data=$this->_getAllParams();
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiRestCore( $this->apiSetup, $this->apicallbacks, $request_data);
    }
  /** Controller action handling JSON request */
  function jsonAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request_data = $this->_getAllParams();
      
    $method_name = $this->_getParam('method');
    if( !isset ($method_name))
      {
      echo "Inconsistent request";
      exit;
      }
    
    $request_data=$this->_getAllParams();
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiJsonCore( $this->apiSetup, $this->apicallbacks, $request_data);
    }


  /** Return the information */
  function _Version( $args )
    {
    $data['version'] = $this->view->version;
    return $data;
    }

  /** Return the user id given the arguments */
  function _Login( $args )
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
      $userapiDao = $this->Api_Userapi->createKeyFromEmailPassword($args['appname'],$args['email'],$args['password']);
      
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

  /** Return the user id given the arguments */
  function _getUserId( $args )
    {
    if(!array_key_exists('token', $args))
      {
      echo "Unable to find token";
      exit;
      }
    $token = $args['token'];
    $userapiDao=$this->Api_Userapi->getUserapiFromToken($token);
    if(!$userapiDao)
      {
      echo "Error token";
      exit;
      }
    return $userapiDao->getUserId();
    }
    
  /** Return the user */
  function _getUser( $args )
    {
    $userid = $this->_getUserId($args);    
    $userDao = $this->User->load($userid);
    return $userDao;
    }

  /** Generate an unique upload token */
  function _UploadApiGenerateToken( $args )
    {
    return $this->uploadApi->generateToken( $args );
    }

  /** Get the offset of the current upload */
  function _UploadApiGetOffset( $args )
    {
    return $this->uploadApi->getOffset( $args );
    }

  /** Upload a Bitstream */
  function _UploadBitstream( $args )
    {
    if (!$this->_request->isPost() && !$this->_request->isPut())
      {
      throw new Exception('POST or PUT method required', -153);
      }

    $userDao = _getUser($args);
    
    if($userDao == false)
      {
      throw new Exception('Please log in', -150);
      }
      
    if(array_key_exists('itemrevision_id', $args))
      {
      $revision = $this->ItemRevision->load($args['itemrevision_id']);
      if($revision != false)
        {
        throw new Exception('Unable to find revision', -150);
        }
      $item = $revision->getItem();
      if($item != false)
        {
        throw new Exception('Unable to find item', -150);
        }
      if(!$this->Item->policyCheck($item, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Permission error', -150);
        }
      }
    elseif(array_key_exists('item_id', $args))
      {      
      $item = $this->Item->load($args['item_id']);
      if($item != false)
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
      if($folder != false)
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

    if(!array_key_exists('itemid', $args))
      {
      throw new Exception('Parameter itemid is not defined', -150);
      }

    $mode = array_key_exists('mode', $args) ? $args['mode'] : "stream";

    if ($mode == "stream")
      {
      $token = $this->uploadApi->generateToken($args);
      $args['uploadtoken'] = $token['token'];
      $args['length'] = $args['size'];
      $result = $this->uploadApi->process($args);

      $filename = $result['filename'];
      $filepath = $result['path'];
      $filesize = $result['size'];
      }
    else if ($mode == "multipart")
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

    return $data;
    }

  /** Create an item */
  function _ItemCreate( $args )
    {
    if(!$this->RequestHandler->isPost())
      {
      throw new Exception('POST method required', -153);
      }

    $userid = $this->_getUserId($args);

    if(!array_key_exists('parentid', $args))
      {
      throw new Exception('Parameter parentid is not defined', -150);
      }

    if(!array_key_exists('name', $args))
      {
      throw new Exception('Parameter name is not defined', -150);
      }

    // Get the parentid
    $parentid = $args['parentid'];
    if(!$this->User->isPolicyValid($parentid, $userid, MIDAS_RESOURCE_COLLECTION, MIDAS_POLICY_ADD))
      {
      throw new Exception('Invalid policy', -151);
      }

    // Get the name
    $Title = $args['name'];

    $Copyright = isset($args['copyright']) ? $args['copyright'] : '';
    $Description = isset($args['description']) ? $args['description'] : '';
    $Abstract = isset($args['abstract']) ? $args['abstract'] : '';
    $Authors = isset($args['authors']) ? explode('/',$args['authors']) : array();
    $Keywords = isset($args['keywords']) ? explode('/',$args['keywords']) : array();

    $FirstNames = array();
    $LastNames = array();
    foreach($Authors as $author)
      {
      if($author == '') break;
      $name = explode(",", $author);

      if(count($name) >= 2)
        {
        $FirstNames[] = trim($name[0]);
        $LastNames[] = trim($name[1]);
        }
      else
        {
        $FirstNames[] = '';
        $LastNames[] = trim($name[0]);
        }
      }

    for($i = 0; $i < count($Keywords); $i++)
      {
      $Keywords[$i] = trim($Keywords[$i]);
      }

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';

    $record = $this->Api->getResourceForUuid($uuid);
    if(!empty($record))
      {
      if(!$this->User->isPolicyValid($record['id'], $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', -151);
        }

      $metadata['title'] = $Title;
      $metadata['abstract'] = $Abstract;
      $metadata['description'] = $Description;
      $metadata['copyright'] = $Copyright;
      $metadata['firstname'] = $FirstNames;
      $metadata['lastname'] = $LastNames;
      $metadata['keyword'] = $Keywords;

      if($this->Item->updateItem($record['id'], $userid, $metadata) === false)
        {
        throw new Exception('Item metadata update failed', -201);
        }
      return $record;
      }
    else
      {
      $itemid = $this->Item->createItem($parentid, $userid, $FirstNames, $LastNames, '',
                        $Title, $Keywords, $Abstract, '', '', $Description, $Copyright,
                        '', 0, 0, 0, 0, '', '', '',
                        $this->getMidasBaseHandle(), false, false, $uuid);

      if($itemid === false)
        {
        throw new Exception('Request failed', -200);
        }

      $data['id'] = $itemid;

      return $data;
      }
    }

  /** Create a community */
  function _CommunityCreate( $args )
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
      $record = $this->Uniqueidentifier->getByUid($uuid);
      if($record != false)
        {
        $record = $this->Uniqueidentifier->getResource($record);
        }
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

  /** Get a community information */
  function _CommunityGet( $args )
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
    }// _CommunityGet

  /** Get folderContent */
  function _FolderContent( $args )
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
      $tmp = array();
      $tmp['folder_id'] = $folder->getFolderId();
      $tmp['name'] = $folder->getName();
      $tmp['creation'] = $folder->getDate();
      if($tmp['name'] == 'Public' || $tmp['name'] == 'Private')
        {
        $tmp['deletable'] = 'false';
        }
      else
        {
        $tmp['deletable'] = 'true';
        }
      $tmp['policy'] = $folder->policy;
      $tmp['privacy_status'] = $folder->privacy_status;
      $jsonContent[$folder->getParentId()]['folders'][] = $tmp;
      unset($tmp);
      }
    foreach($items as $item)
      {
      $tmp = array();
      $tmp['item_id'] = $item->getItemId();
      $tmp['name'] = $item->getName();
      $tmp['parent_id'] = $item->parent_id;
      $tmp['creation'] = $item->getDate();
      $tmp['size'] = $item->getSizebytes();
      $tmp['policy'] = $item->policy;
      $tmp['privacy_status'] = $item->privacy_status;
      $jsonContent[$item->parent_id]['items'][] = $tmp;
      unset($tmp);
      }
      
    return $jsonContent[$parent->getKey()];
    }

  /** Get the full tree from a community */
  function _FolderTree( $args )
    {
    App::import("Component", "communitytree");
    $userid = $this->_getUserId($args);
    $tree = new communitytreeComponent();
    return $tree->getTree(0,$userid);
    } //_CommunityTree

  /** Get information about the folder */
  function _FolderGet( $args )
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

    return $folder->toArray();
    }

  /** Create a folder */
  function _FolderCreate( $args )
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
      $record = $this->Uniqueidentifier->getByUid($uuid);      
      if($record != false)
        {
        $record = $this->Uniqueidentifier->getResource($record);
        }
      if($record === false || !$this->Folder->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception("This community doesn't exist  or you don't have the permissions.", 200);
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
  function _ItemAbstractGet( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $itemid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($itemid,$userid,MIDAS_RESOURCE_ITEM,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $abstract = $this->Item->GetAbstract($itemid);
    return array($abstract);
    }

  /** Return the title given an item id */
  function _ItemTitleGet( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }
    $itemid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($itemid,$userid,MIDAS_RESOURCE_ITEM,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }
    $title = $this->Item->GetTitle($itemid);
    return array($title);
    }

  /** Group resource given and item */
  function _ItemResourceCreate( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }
    $itemid = $args['id'];
    $userid = $this->_getUserId($args);

    if(!$this->User->isPolicyValid($itemid, $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_WRITE))
      {
      throw new Exception('Invalid policy', -151);
      }

    // Forward parameter to the item controller
    $ret = $this->requestAction(
      array( 'controller' => 'item', 'action' => 'create_resource' ),
      array( 'pass' => array( $itemid )),
      'return'
      );

    return array($title);
    //return true;
    }

  /** Return a list of item ids given a search criteria */
  function _ItemSearch( $args )
    {
    if( ! array_key_exists( 'term', $args ) )
      {
      throw new Exception( 'Parameter term is not defined', -150 );
      }

    $searchTerm = $args['term'];
    $userid = $this->_getUserId( $args );

    $itemids = array();
    foreach( $itemsearchids as $itemid )
      {
      if( ! $this->User->isPolicyValid( $itemid, $userid, MIDAS_RESOURCE_ITEM,MIDAS_POLICY_READ ) )
        {
        continue;
        }
      $itemids[] = $itemid;
      }
    return $itemids;
    }

  /** Get the item */
  function _ItemGet( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $itemid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($itemid,$userid,MIDAS_RESOURCE_ITEM,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    // We should do this in one SQL request
    $data['id'] = $itemid;
    $data['uuid'] = $this->Item->getUuid($itemid);
    $data['parent'] = $this->Item->getOwningCollection($itemid);
    $data['hasAgreement'] = $this->Item->hasAgreement($itemid) ? '1' : '0';
    $data['title'] = $this->Item->getTitle($itemid);
    $data['abstract'] = $this->Item->getAbstract($itemid);
    $data['description'] = $this->Item->getDescription($itemid);
    $data['size'] = $this->Resourcelog->getFileSize($itemid, MIDAS_RESOURCE_ITEM);

    $authors = $this->Item->getAuthors($itemid);
    $data['keywords'] = $this->Item->getKeywords($itemid);

    foreach($authors as $author)
      {
      $data['authors'][] = $author['lastname'].", ".$author['firstname'];
      }

    $i=0;
    $bitstreamids = $this->Item->getBitstreams($itemid);
    foreach($bitstreamids as $bitstreamid)
      {
      $data['bitstreams'][$i]['name'] = $this->Bitstream->getName($bitstreamid);
      $data['bitstreams'][$i]['size'] = $this->Bitstream->getSizeInBytes($bitstreamid);
      $data['bitstreams'][$i]['uuid'] = $this->Bitstream->getUuid($bitstreamid);
      $data['bitstreams'][$i]['checksum'] = $this->Bitstream->getChecksum($bitstreamid);
      $data['bitstreams'][$i]['id'] = $bitstreamid;
      $i++;
      }
    return $data;
    }

  /** Download a folder */
  function _FolderDownload( $args )
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
  function _ItemDownload( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $itemid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($itemid,$userid,MIDAS_RESOURCE_ITEM,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $this->requestAction('/item/download/'.$itemid,array('return'));
    exit();
    }

  /** Return all locations for a bitstream */
  function _BitstreamLocations( $args )
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
    if(!$this->User->isPolicyValid($bitstreamid,$userid,MIDAS_RESOURCE_BITSTREAM,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }
    return $this->Bitstream->getLocations($bitstreamid);
    }

  /** Download a bitstream */
  function _BitstreamDownload( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $bitstreamid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid,$userid,MIDAS_RESOURCE_BITSTREAM,MIDAS_POLICY_READ))
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
    $this->requestAction('/bitstream/view/'.$bitstreamid.$location,array('return'));
    exit();
    }

  function _BitstreamDownloadByHash( $args )
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
      throw new Exception("No bitstream exists with $alg = $hash", -152);
      }
    if(array_key_exists('checkExistsOnly', $args))
      {
      return array('exists'=>'true');
      }
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid,$userid,MIDAS_RESOURCE_BITSTREAM,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    // must call ob_end_clean before we forward to bitstream/download or bitstream/view
    ob_end_clean();
    $this->requestAction("/bitstream/view/$bitstreamid$name",array('return'));
    exit();
    }

  /** Get bitstream info */
  function _BitstreamGet( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $bitstreamid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($bitstreamid,$userid,MIDAS_RESOURCE_BITSTREAM,MIDAS_POLICY_READ))
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
  function _UuidGet( $args )
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
      
    $uuid = $this->Uniqueidentifier->getIndentifier($dao);

    if($uuid == false)
      {
      throw new Exception('Invalid resource type or id.', -151);
      }
      
    return $uuid->toArray();
    }

  /** Get resource given a uuid */
  function _ResourceGet( $args )
    {
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }

    $uuid = $args['uuid'];
    $resource = $this->Uniqueidentifier->getByUid($uuid);

    if($resource == false)
      {
      throw new Exception('No resource for the given UUID.', -151);
      }

    return $resource->toArray();
    }

  /** Returns a path of uuids from the root community to the given node */
  function _PathFromRoot ( $args )
    {
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }
    return array_reverse($this->Api->getPathToRoot($args['uuid']));
    }

  /** Returns a path of uuids from the given node to the root community */
  function _PathToRoot ( $args )
    {
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }
    return $this->Api->getPathToRoot($args['uuid']);
    }

  /** Search resources for the given words */
  function _ResourcesSearch( $args )
    {
    if(!array_key_exists('search', $args))
      {
      throw new Exception('Parameter search is not defined', -150);
      }
    $userid = $this->_getUserId($args);

    $results = $this->Search->searchResources($userid, $args['search']);
    $retVal = array();

    foreach($results['bitstreams'] as $bitstream)
      {
      $retVal['bitstreams'][] = array(
        'bitstream_id'=>$bitstream['bitstream_id'],
        'bitstream_name'=>$bitstream['bitstream_name'],
        'uuid'=>$bitstream['uuid']);
      }
    foreach($results['items'] as $item)
      {
      $retVal['items'][] = array(
        'item_id'=>$item['item_id'],
        'item_name'=>$item['item_name'],
        'uuid'=>$item['uuid']);
      }
    foreach($results['collections'] as $coll)
      {
      $retVal['collections'][] = array(
        'collection_id'=>$coll['collection_id'],
        'collection_name'=>$coll['collection_name'],
        'uuid'=>$coll['uuid']);
      }
    foreach($results['communities'] as $comm)
      {
      $retVal['communities'][] = array(
        'community_id'=>$comm['community_id'],
        'community_name'=>$comm['community_name'],
        'uuid'=>$comm['uuid']);
      }

    return $retVal;
    }

  /** Return all changed resources since a given timestamp, and provide a new timestamp */
  function _NewResourcesGet( $args )
    {
    if(array_key_exists('since', $args))
      {
      App::import('Model','Resourcelog');
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

  function _ConvertPathToId( $args )
    {
    if(!array_key_exists('path', $args))
      {
      throw new Exception('Parameter path is not defined', -150);
      }
    $data = $this->Api->convertPathToId($args['path']);

    if($data === false)
      {
      throw new Exception('Invalid resource path', -151);
      }
    return $data;
    }

  function _CommunityDelete( $args )
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
  function _FolderDelete( $args )
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

  function _ItemDelete( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $id = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($id, $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_DELETE))
      {
      throw new Exception('Invalid policy', -151);
      }
    if(!$this->Item->delete($id))
      {
      throw new Exception("Failed to delete item $id", -100);
      }
    }

  function _BitstreamDelete( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $id = $args['id'];
    $userid = $this->_getUserId($args);
    $parentid = $this->Bitstream->getItemId($id);
    if(!$this->User->isPolicyValid($parentid, $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_REMOVE))
      {
      throw new Exception('Invalid policy', -151);
      }
    if(!$this->Bitstream->delete($id))
      {
      throw new Exception("Failed to delete bitstream $id", -100);
      }
    }

  /** Outputs a count of all bitstreams under the given resource */
  function _BitstreamCount( $args )
    {
    if(!array_key_exists('type', $args))
      {
      throw new Exception('Parameter type is not defined', -155);
      }
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $type = $args['type'];
    $id = $args['id'];

    if(!is_numeric($id) || $id < 1)
      {
      throw new Exception('Id must be a number greater than zero.', -155);
      }
    $userid = $this->_getUserId($args);

    $count = 0;
    switch($type)
      {
      case MIDAS_RESOURCE_COMMUNITY:
        list($count,$size) = $this->Community->countBitstreams($id, $userid);
        break;
      case MIDAS_RESOURCE_COLLECTION:
        list($count,$size) = $this->Collection->countBitstreams($id, $userid);
        break;
      case MIDAS_RESOURCE_ITEM:
        list($count,$size) = $this->Item->countBitstreams($id, $userid);
        break;
      default:
        throw new Exception('Invalid type provided.', -155);
      }
    return array('count'=>$count, 'size'=>$size);
    }

  function _BitstreamKeyFile( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $id = $args['id'];
    $userid = $this->_getUserId($args);

    if(!$this->User->isPolicyValid($id, $userid, MIDAS_RESOURCE_BITSTREAM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }
    if(!$this->Bitstream->getExists($id))
      {
      throw new Exception('Bitstream does not exist', -100);
      }
    $checksum = $this->Bitstream->getChecksum($id);
    ob_end_clean();
    $this->requestAction('/bitstream/keyfile/'.$checksum,array('return'));
    exit();
    }

  function _ItemKeys( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $format = 'tgz';
    if(array_key_exists('format', $args))
      {
      $format = $args['format'];
      }
    $zip = $format == 'zip' ? '1' : '0';
    $id = $args['id'];
    $userid = $this->_getUserId($args);

    if(!$this->User->isPolicyValid($id, $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }
    if(!$this->Item->getExists($id))
      {
      throw new Exception('Item does not exist', -100);
      }
    // make a valid filename from the item title
    $name = str_replace(" ", "_", $this->Item->getTitle($id) );
    $name = str_replace(":", "_", $name);
    $name = str_replace(";", "_", $name);
    $name = str_replace("\n", "_", $name);
    $name = str_replace("\\", "_", $name);
    $name = str_replace("/", "_", $name);
    $name = str_replace("&", "_", $name);

    ob_end_clean();

    $this->requestAction(
        array( 'controller' => 'item', 'action' => 'downloadkeys' ),
        array( 'pass' => array( $id ),
               'url' => array('name' => $name, 'zip' => $zip)
             ),
        'return'
    );
    exit();
    }

  function _CheckUserAgreement( $args )
    {
    $userid = $this->_getUserId($args);
    if(!$userid)
      {
      throw new Exception('Invalid user token', -151);
      }
    if(!array_key_exists('type', $args))
      {
      throw new Exception('Parameter type is not defined', -155);
      }
    $type = $args['type'];
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $id = $args['id'];
    if(!is_numeric($id) || $id < 1)
      {
      throw new Exception('Id must be a number greater than zero.', -155);
      }

    $communityid = 0;
    switch($type)
      {
      case MIDAS_RESOURCE_COMMUNITY:
        $communityid = $this->Community->hasAgreement($id);
        break;
      case MIDAS_RESOURCE_COLLECTION:
        $communityid = $this->Collection->hasAgreement($id);
        break;
      case MIDAS_RESOURCE_ITEM:
        $communityid = $this->Item->hasAgreement($id);
        break;
      case MIDAS_RESOURCE_BITSTREAM:
        $communityid = $this->Bitstream->hasAgreement($id);
        break;
      default:
        throw new Exception('Invalid type provided.', -155);
      }
    if(!$communityid)
      {
      throw new Exception('No license agreement found', -42);
      }
    $data['hasAgreed'] = $this->User->isAdmin($userid) ||
      $this->CommunityAgreement->isInGroup($userid, $this->CommunityAgreement->getGroupId($communityid))
      ? '1' : '0';
    return $data;
    }
  } // end class
?>
