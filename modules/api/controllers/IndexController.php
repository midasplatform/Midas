<?php

//App::import('Vendor', 'kwwebapi');
require_once BASE_PATH . '/modules/api/library/KwWebApiCore.php';

class Api_IndexController extends Api_AppController
{

  public $_moduleModels=array('Userapi');

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
    }

  /** User function */
  function userAction()
    {
    $this->view->header='My Web API';
    if(!$this->logged)  
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->set('currentMenu', "My Midas");
    $this->set('activeMenu', "currentMenu");
    $this->set('currentMenuLink', $this->webroot."user");

    // Create a new API key
    if( isset($this->params['form']['createAPIKey']))
      {
      $applicationName      = $this->params['data']['API']['applicationName'];
      $tokenExperiationTime = $this->params['data']['API']['experiationtime'];

      if (strlen($applicationName) > 0)
        {
        if(!$this->Api->createKey($userid,$applicationName,$tokenExperiationTime))
          {
          $this->set('error', 'Cannot generate API key. Make sure the applicationname is not already taken');
          }
        }
      else
        {
        $this->set('error', 'Application name should be set');
        }
      }
    else if(isset($this->params['pass'][0]) && $this->params['pass'][0]=='deletekey')
      {
      // Make sure the key belongs to the user
      if($userid  == $this->Api->getUserFromKey($this->params['pass'][1]))
        {
        $this->Api->deleteKey($this->params['pass'][1]);
        $this->redirect('/api/user');
        }
      }

    // List the previously generated API keys
    $apikeys = array();
    $apikeysids = $this->Api->getUserKeys($userid);
    foreach($apikeysids as $apikeysid)
      {
      $apikey = array();
      $apikey['id']              = $apikeysid;
      $apikey['applicationname'] = $this->Api->getApplicationName($apikeysid);
      $apikey['creationdate']    = $this->Api->getCreationDate($apikeysid);
      $apikey['key']             = $this->Api->getKey($apikeysid);
      $apikey['tokenexpiration'] = $this->Api->getTokenExpirationTime($apikeysid);
      $apikeys[]                 = $apikey;
      }

    $this->set('apikeys',$apikeys);
    $this->set('serverURL',$this->getServerURL());
    }

   /** Set the call back API */
  function _SetApiCallbacks( $apiMethodPrefix )
    {
    $apiMethodPrefix = KwWebApiCore::checkApiMethodPrefix( $apiMethodPrefix );

    $this->apicallbacks[$apiMethodPrefix.'info']                   = array(&$this, '_Info');
    $this->apicallbacks[$apiMethodPrefix.'login']                  = array(&$this, '_Login');

    $this->apicallbacks[$apiMethodPrefix.'upload.generatetoken']   = array(&$this, '_UploadApiGenerateToken');
    $this->apicallbacks[$apiMethodPrefix.'upload.getoffset']       = array(&$this, '_UploadApiGetOffset');
/*
    $this->apicallbacks[$apiMethodPrefix.'upload.bitstream']       = array(&$this, '_UploadBitstream');

    $this->apicallbacks[$apiMethodPrefix.'community.create']       = array(&$this, '_CommunityCreate');
    $this->apicallbacks[$apiMethodPrefix.'community.get']          = array(&$this, '_CommunityGet');
    $this->apicallbacks[$apiMethodPrefix.'community.list']         = array(&$this, '_CommunityList');
    $this->apicallbacks[$apiMethodPrefix.'community.tree']         = array(&$this, '_CommunityTree');
    $this->apicallbacks[$apiMethodPrefix.'community.delete']       = array(&$this, '_CommunityDelete');

    $this->apicallbacks[$apiMethodPrefix.'collection.get']         = array(&$this, '_CollectionGet');
    $this->apicallbacks[$apiMethodPrefix.'collection.download']    = array(&$this, '_CollectionDownload');
    $this->apicallbacks[$apiMethodPrefix.'collection.create']      = array(&$this, '_CollectionCreate');
    $this->apicallbacks[$apiMethodPrefix.'collection.delete']      = array(&$this, '_CollectionDelete');

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

    $this->apicallbacks[$apiMethodPrefix.'uuid.get']               = array(&$this, '_UuidGet');
    $this->apicallbacks[$apiMethodPrefix.'resource.get']           = array(&$this, '_ResourceGet');
    $this->apicallbacks[$apiMethodPrefix.'resources.search']       = array(&$this, '_ResourcesSearch');
    $this->apicallbacks[$apiMethodPrefix.'newresources.get']       = array(&$this, '_NewResourcesGet');
    $this->apicallbacks[$apiMethodPrefix.'path.to.root']           = array(&$this, '_PathToRoot');
    $this->apicallbacks[$apiMethodPrefix.'path.from.root']         = array(&$this, '_PathFromRoot');
    $this->apicallbacks[$apiMethodPrefix.'convert.path.to.id']     = array(&$this, '_ConvertPathToId');

    $this->apicallbacks[$apiMethodPrefix.'check.user.agreement']   = array(&$this, '_CheckUserAgreement');

    $this->apicallbacks[$apiMethodPrefix.'example.reversestring']  = array(&$this, '_ExampleReverseString');*/
    
    

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
    if ( $this->_request->isGet() )
      {
      $request_data = $this->_getParam('url');
      }

    if ( $this->_request->isPost() )
      {
      $request_data = array_merge($this->_getParam('form'),$this->_getParam('url'));
      }

    if( $this->_request->isPut() )
      {
      $request_data = array_merge($this->_getParam('form'),$this->_getParam('url'));
      }
      
    

    $method_name = $this->_getParam('method');
    if( !isset ($method_name))
      {
      throw new Zend_Exception("Inconsistent request");
      }
    
    $request_data=$this->_getAllParams();
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiRestCore( $this->apiSetup, $this->apicallbacks, $request_data);
    }

  /** Controller action handling XMLRPC request */
  function xmlrpcAction($method_name = '')
    {
    $request_data = '';
    // NOT IMPLEMENTED

    if($this->apiEnable)
      {
      // Handle XML-RPC request
      //$this->kwWebApiCore = new KwWebApiXmlRpcCore( $this->apiSetup, $this->apicallbacks, $request_data);
      die(__FUNCTION__.' API not implemented');
      }
    else
      {
      die("Midas Api disabled - Check 'condig.api.php' file");
      }
    }

  /** Controller action handling SOAP request */
  function soapAction($method_name = '')
    {
    $request_data = '';
    // NOT IMPLEMENTED

    if($this->apiEnable)
      {
      // Handle XML-RPC request
      //$this->kwWebApiCore = new KwWebApiSoapCore( $this->apiSetup, $this->apicallbacks, $request_data);
      die(__FUNCTION__.' API not implemented');
      }
    else
      {
      die("Midas Api disabled - Check 'condig.api.php' file");
      }
    }

  /** Just an expamle that reverses a string */
  function _ExampleReverseString($args)
    {
    if( !array_key_exists('myparam', $args) )
      {
      throw new Exception('Parameter myparam is not defined', -150);
      }
    return strrev( $args['myparam'] );
    }

  /** Return the information */
  function _Info( $args )
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
      if(empty($userapiDao))
        {
        return $data;
        }
      }
    else
      {
      if(!array_key_exists('apikey', $args))
        {
        throw new Zend_Exception('Parameter apikey is not defined');
        }
      }

    $email = $args['email'];
    $appname = $args['appname'];
    $apikey = $args['apikey'];
    $tokenDao = $this->Api_Userapi->getToken($email, $apikey, $appname);
    if(empty($tokenDao))
        {
        return $data;
        }
    $data['token'] = $tokenDao->getToken();
    return $data;
    }

  /** Return the user id given the arguments */
  function _getUserId( $args )
    {
    if(!array_key_exists('token', $args))
      {
      return 0;
      }
    $token = $args['token'];
    $userapiDao=$this->Api_Userapi->getUserapiFromToken($token);
    if(!$userapiDao)
      {
      return 0;
      }
    return $userapiDao->getUserId();
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

    $userid = $this->_getUserId($args);
    
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }
    $uuid = $args['uuid'];

    if(!array_key_exists('itemid', $args))
      {
      throw new Exception('Parameter itemid is not defined', -150);
      }
    $itemid = $args['itemid'];

    if(!$this->User->isPolicyValid($itemid, $userid, MIDAS_RESOURCE_ITEM, MIDAS_POLICY_ADD))
      {
      throw new Exception('Invalid policy', -151);
      }

    // mode : ["multipart" | "stream"]
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

    // Forward parameter to the item controller
    $ret = $this->requestAction(
        array( 'controller' => 'item', 'action' => 'upload' ),
        array( 'pass' => array( $itemid, $uuid, $filesize ),
               'url' => array(
                        'authmethod' => 'api',
                        'key'        => $args['token'],
                        'name'       => $filename,
                        'path'       => $filepath,
                        'sessionid'  => ''
                        )
        ),
        'return'
    );

    if ( empty($ret) )
      {
      throw new Exception('Upload failed', -850);
      }

    $data['id']   = $ret;
    $data['size'] = $filesize;

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
    if(!$this->User->isPolicyValid($parentid, $userid, MIDAS_RESOURCE_COMMUNITY, MIDAS_POLICY_ADD))
      {
      throw new Exception('Invalid policy', -151);
      }

    // Get the name
    $Name = $args['name'];
    $Description = "";
    if(isset($args['description']))
      {
      $Description = $args['description'];
      }
    $IntroductoryText = "";
    if(isset($args['introductorytext']))
      {
      $IntroductoryText = $args['introductorytext'];
      }
    $Copyright = "";
    if(isset($args['copyright']))
      {
      $Copyright = $args['copyright'];
      }
    $Links = "";
    if(isset($args['links']))
      {
      $Links = $args['links'];
      }

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';

    $record = $this->Api->getResourceForUuid($uuid);
    if(!empty($record))
      {
      if(!$this->User->isPolicyValid($record['id'], $userid, MIDAS_RESOURCE_COMMUNITY, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', -151);
        }
      if($this->Community->updateCommunity($record['id'], $Name, $Description, $IntroductoryText,
         $Copyright, $Links, $this->getMidasBaseHandle()) === false)
        {
        throw new Exception('Community name already exists', -201);
        }
      return $record;
      }
    else
      {
      $communityid = $this->Community->createCommunity($userid,$parentid,$Name,$Description,
                                                       $IntroductoryText,$Copyright,$Links,$this->getMidasBaseHandle(), $uuid);

      if($communityid === false)
        {
        throw new Exception('Request failed', -200);
        }

      $data['id'] = $communityid;

      return $data;
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
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($communityid,$userid,MIDAS_RESOURCE_COMMUNITY,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $data['id'] = $communityid;
    $data['name'] = $this->Community->getName($communityid);
    $data['description'] = $this->Community->getDescription($communityid);
    $data['uuid'] = $this->Community->getUuid($communityid);
    $data['copyright'] = $this->Community->getCopyright($communityid);
    $data['introductory'] = $this->Community->getIntroductory($communityid);
    $data['parent'] = $this->Community->getParentCommunity($communityid);
    $data['hasAgreement'] = $this->Community->hasAgreement($communityid) ? '1' : '0';
    $data['size'] = $this->Resourcelog->getFileSize($communityid, MIDAS_RESOURCE_COMMUNITY);
    return $data;
    }

  /** Get the list of communities */
  function _CommunityList( $args )
    {
    $id = array_key_exists('id', $args) ? $args['id'] : '0';
    $communities = $this->Community->getCommunities($id);

    $userid = $this->_getUserId($args);
    $communityids = array();
    foreach($communities as $communityid)
      {
      if(!$this->User->isPolicyValid($communityid,$userid,MIDAS_RESOURCE_COMMUNITY,MIDAS_POLICY_READ))
        {
        continue;
        }
      $communityids[] = $communityid;
      }
    return $communityids;
    }

  /** Get the full tree from a community */
  function _CommunityTree( $args )
    {
    App::import("Component", "communitytree");
    $userid = $this->_getUserId($args);
    $tree = new communitytreeComponent();
    return $tree->getTree(0,$userid);
    } //_CommunityTree

  /** Get information about the collection */
  function _CollectionGet( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $collectionid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($collectionid,$userid,MIDAS_RESOURCE_COLLECTION,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $data['id'] = $collectionid;
    $data['name'] = $this->Collection->getName($collectionid);
    $data['description'] = $this->Collection->getDescription($collectionid);
    $data['introductory'] = $this->Collection->getIntroductory($collectionid);
    $data['copyright'] = $this->Collection->getCopyright($collectionid);
    $data['uuid'] = $this->Collection->getUuid($collectionid);
    $data['parent'] = $this->Collection->getMainParent($collectionid);
    $data['hasAgreement'] = $this->Collection->hasAgreement($collectionid) ? '1' : '0';
    $data['size'] = $this->Resourcelog->getFileSize($collectionid, MIDAS_RESOURCE_COLLECTION);
    $itemids = $this->Collection->getItems($collectionid);

    foreach($itemids as $itemid)
      {
      $title = $this->Item->getTitle($itemid);
      $uuid = $this->Item->getUuid($itemid);
      $data['items'][] = array('id'=>$itemid, 'title'=>$title, 'uuid'=>$uuid);
      }
    return $data;
    }

  /** Create a collection */
  function _CollectionCreate( $args )
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
    if(!$this->User->isPolicyValid($parentid, $userid, MIDAS_RESOURCE_COMMUNITY, MIDAS_POLICY_ADD))
      {
      throw new Exception('Invalid policy', -151);
      }

    // Get the name
    $Name = $args['name'];
    $Description = "";
    if(isset($args['description']))
      {
      $Description = $args['description'];
      }
    $IntroductoryText = "";
    if(isset($args['introductorytext']))
      {
      $IntroductoryText = $args['introductorytext'];
      }
    $Copyright = "";
    if(isset($args['copyright']))
      {
      $Copyright = $args['copyright'];
      }
    $Links = "";
    if(isset($args['links']))
      {
      $Links = $args['links'];
      }
    $License = "";
    if(isset($args['license']))
      {
      $License = $args['license'];
      }
    $Admin = "";
    if(isset($args['admin']))
      {
      $Admin = $args['admin'];
      }

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';

    $record = $this->Api->getResourceForUuid($uuid);
    if(!empty($record))
      {
      if(!$this->User->isPolicyValid($record['id'], $userid, MIDAS_RESOURCE_COLLECTION, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', -151);
        }

      if($this->Collection->updateInfos($record['id'], $Name, $Description, $IntroductoryText,'',$License, $Copyright) === false)
        {
        throw new Exception('Collection name already exists', -201);
        }
      return $record;
      }
    else
      {
      $collectionid = $this->Collection->createCollection($parentid,$userid,$Name,$Description,$IntroductoryText,
                    $Copyright,$License,$Links,$this->getMidasBaseHandle(), false, $uuid);

      if($collectionid === false)
        {
        throw new Exception('Request failed', -200);
        }

      $data['id'] = $collectionid;

      return $data;
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

  /** Download a collection */
  function _CollectionDownload( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -150);
      }

    $collectionid = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($collectionid,$userid,MIDAS_RESOURCE_COLLECTION,MIDAS_POLICY_READ))
      {
      throw new Exception('Invalid policy', -151);
      }

    $this->requestAction('/collection/download/'.$collectionid,array('return'));
    exit();
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
    $uuid = $this->Api->getUUID($id,$type);

    if($uuid == '')
      {
      throw new Exception('Invalid resource type or id.', -151);
      }

    $data['id'] = $id;
    $data['type'] = $type;
    $data['uuid'] = $uuid;
    return $data;
    }

  /** Get resource given a uuid */
  function _ResourceGet( $args )
    {
    if(!array_key_exists('uuid', $args))
      {
      throw new Exception('Parameter uuid is not defined', -150);
      }

    $uuid = $args['uuid'];
    $resource = $this->Api->getResourceForUuid($uuid);

    if(!isset($resource['type']))
      {
      throw new Exception('No resource for the given UUID.', -151);
      }

    return $resource;
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
    $id = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($id, $userid, MIDAS_RESOURCE_COMMUNITY, MIDAS_POLICY_DELETE))
      {
      throw new Exception('Invalid policy', -151);
      }
    if(!$this->Community->delete($id))
      {
      throw new Exception("Failed to delete community $id", -100);
      }
    }

  function _CollectionDelete( $args )
    {
    if(!array_key_exists('id', $args))
      {
      throw new Exception('Parameter id is not defined', -155);
      }
    $id = $args['id'];
    $userid = $this->_getUserId($args);
    if(!$this->User->isPolicyValid($id, $userid, MIDAS_RESOURCE_COLLECTION, MIDAS_POLICY_DELETE))
      {
      throw new Exception('Invalid policy', -151);
      }
    if(!$this->Collection->delete($id))
      {
      throw new Exception("Failed to delete collection $id", -100);
      }
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
