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

// Exception Messages
define('MIDAS_ADMIN_PRIVILEGES_REQUIRED', "Administrative privileges required.");
define('MIDAS_AJAX_REQUEST_ONLY', "This page should only be requested by ajax.");
define('MIDAS_LOGIN_REQUIRED', "User should be logged in to access this page.");

/**
 * @class AppController
 * @package Core
 * Provides global functions to the controllers
 */
class AppController extends MIDAS_GlobalController
  {
  /**
   * Pre-dispatch routines
   *
   * @return void
   */
  public function preDispatch()
    {
    parent::preDispatch();
    $this->view->setEncoding('UTF-8');

    $this->view->setScriptPath(BASE_PATH."/core/views");

    $fc = Zend_Controller_Front::getInstance();
    $module = $fc->getRequest()->getModuleName();
    if($module == 'default')
      {
      $module = 'core';
      }
    $this->getLogger()->setEventItem('module', $module);
    $this->view->webroot = $fc->getBaseUrl();
    $this->coreWebroot = $this->view->webroot.'/core';
    $this->view->coreWebroot = $this->coreWebroot;

    Zend_Registry::set('webroot', $this->view->webroot);
    Zend_Registry::set('coreWebroot', $this->view->coreWebroot);

    $this->view->demoMode = $this->isDemoMode();

    $this->view->title = Zend_Registry::get('configGlobal')->application->name;
    $this->view->metaDescription = Zend_Registry::get('configGlobal')->application->description;
    $this->view->metaKeywords = Zend_Registry::get('configGlobal')->application->keywords;

    // Set the version
    $this->view->version = '3.2.8';
    if(isset(Zend_Registry::get('configDatabase')->version))
      {
      $this->view->version = Zend_Registry::get('configDatabase')->version;
      }

    require_once BASE_PATH.'/core/models/dao/UserDao.php';
    require_once BASE_PATH.'/core/models/dao/ItemDao.php';
    //Init Session
    if($fc->getRequest()->getActionName() != 'login' || $fc->getRequest()->getControllerName() != 'user')
      {
      if(isset($_POST['sid']))
        {
        Zend_Session::setId($_POST['sid']);
        }
      Zend_Session::start();

      // log in when testing
      $testingUserId = $this->_getParam('testingUserId');
      if(Zend_Registry::get('configGlobal')->environment == 'testing' && isset($testingUserId))
        {
        $user = new Zend_Session_Namespace('Auth_User_Testing');
        $userModel = MidasLoader::loadModel('User');
        $user->Dao = $userModel->load($testingUserId);
        if($user->Dao == false)
          {
          throw new Zend_Exception('Unable to find user');
          }
        }
      else
        {
        $user = new Zend_Session_Namespace('Auth_User');
        $user->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
        }

      if($user->Dao == null && $fc->getRequest()->getControllerName() != 'install')
        {
        $userModel = MidasLoader::loadModel('User');
        $cookieData = $this->getRequest()->getCookie('midasUtil');

        if(!empty($cookieData))
          {
          $notifier = new MIDAS_Notifier(false, null);
          $notifications = $notifier->callback('CALLBACK_CORE_USER_COOKIE', array('value' => $cookieData));
          $cookieOverride = false;
          foreach($notifications as $module => $result)
            {
            if($result)
              {
              $cookieOverride = true;
              $userDao = $result;
              $user->Dao = $userDao;
              break;
              }
            }
          if(!$cookieOverride)
            {
            $tmp = explode('-', $cookieData);
            if(count($tmp) == 2)
              {
              $userDao = $userModel->load($tmp[0]);
              if($userDao != false)
                {
                // authenticate valid users in the appropriate method for the
                // current application version
                if(version_compare(Zend_Registry::get('configDatabase')->version, '3.2.12', '>='))
                  {
                  $auth = $userModel->hashExists($tmp[1]);
                  }
                else
                  {
                  $auth = $userModel->legacyAuthenticate($userDao, '', '', $tmp[1]);
                  }
                // if authenticated, set the session user to be this user
                if($auth)
                  {
                  $user->Dao = $userDao;
                  }
                }
              }
            }
          }
        }

      session_write_close();
      $controllerName = $fc->getRequest()->getControllerName();
      $actionName = $fc->getRequest()->getActionName();

      $this->userSession = $user;
      $this->view->recentItems = array();
      $this->view->needUpgrade = false;
      $this->view->highNumberError = false;
      if($user->Dao != null && $user->Dao instanceof UserDao)
        {
        if($user->Dao->isAdmin() && $fc->getRequest()->getControllerName() != 'install' && $fc->getRequest()->getControllerName() != 'error')
          {
          if($this->isUpgradeNeeded())
            {
            $this->view->needUpgrade = true;
            }
          $errorlogModel = MidasLoader::loadModel('Errorlog');
          $count = $errorlogModel->countSince(date("Y-m-d H:i:s", strtotime('-24 hour')), array(MIDAS_PRIORITY_CRITICAL, MIDAS_PRIORITY_WARNING));

          if($count > 5)
            {
            $this->view->highNumberError = true;
            }
          }

        $this->logged = true;
        $this->view->logged = true;

        $this->view->userDao = $user->Dao;
        $cookieData = $this->getRequest()->getCookie('recentItems'.$this->userSession->Dao->user_id);
        $this->view->recentItems = array();
        if(isset($cookieData) && file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')) //check if midas installed
          {
          $itemModel = MidasLoader::loadModel('Item');
          $tmpRecentItems = unserialize($cookieData);
          $recentItems = array();
          if(!empty($tmpRecentItems) && is_array($tmpRecentItems))
            {
            foreach($tmpRecentItems as $key => $t)
              {
              if(is_numeric($t))
                {
                $item = $itemModel->load($t);
                if($item !== false)
                  {
                  $recentItems[] = $item->toArray();
                  }
                }
              }
            }

          $this->view->recentItems = $recentItems;
          }
        }
      else
        {
        $this->view->logged = false;
        $this->logged = false;
        }
      }
    else
      {
      $this->userSession = null;
      $this->view->logged = false;
      $this->logged = false;
      }

    if(isset($user))
      {
      Zend_Registry::set('userSession', $user);
      }
    else
      {
      Zend_Registry::set('userSession', null);
      $user = null;
      }

    // init notifier
    Zend_Registry::set('notifier', new MIDAS_Notifier($this->logged, $this->userSession));

    $this->view->lang = Zend_Registry::get('configGlobal')->application->lang;

    $this->view->isStartingGuide = $this->isStartingGuide();
    $this->view->isDynamicHelp = $this->isDynamicHelp();

    //create a global javascript json array
    $jsonGlobal = array(
      "webroot" => $this->view->webroot,
      "coreWebroot" => $this->view->coreWebroot,
      "logged" => $this->logged,
      "needToLog" => false,
      "currentUri" => $this->getRequest()->REQUEST_URI,
      "lang" => Zend_Registry::get('configGlobal')->application->lang,
      "demomode" => $this->isDemoMode(),
      "dynamichelp" => $this->isDynamicHelp(),
      "dynamichelpAnimate" => $this->isDynamicHelp() && isset($_GET['first']),
      "startingGuide" => $this->isStartingGuide(),
      "Yes" => $this->t('Yes'),
      "No" => $this->t('No'));

    $login = array(
      "titleUploadLogin" => $this->t('Please log in'),
      "contentUploadLogin" => $this->t('You need to be logged in to be able to upload files.'));

    $browse = array(
      'view' => $this->t('View'),
      'uploadIn' => $this->t('Upload here'),
      'createFolder' => $this->t('Create a new Folder'),
      'preview' => $this->t('Preview'),
      'metadata' => $this->t('Metadata'),
      'download' => $this->t('Download'),
      'downloadLatest' => $this->t('Download latest revision'),
      'manage' => $this->t('Manage'),
      'edit' => $this->t('Edit'),
      'editItem' => $this->t('Edit item'),
      'editBitstream' => $this->t('Edit bitstream'),
      'delete' => $this->t('Delete'),
      'deleteSelected' => $this->t('Delete all selected'),
      'duplicateSelected' => $this->t('Copy all selected'),
      'shareSelected' => $this->t('Share all selected'),
      'ignoreSelectedFolders' => $this->t('(Folder type does not support this action; all selected folders are ignored.)'),
      'deleteSelectedMessage' => $this->t('Do you really want to delete all selected resources?'),
      'removeItem' => $this->t('Remove Item from Folder'),
      'deleteMessage' => $this->t('Do you really want to delete the folder?'),
      'removeMessage' => $this->t('Do you really want to remove the item?'),
      'share' => $this->t('Permissions'),
      'shared' => $this->t('Shared'),
      'public' => $this->t('Public'),
      'private' => $this->t('Private'),
      'rename' => $this->t('Rename'),
      'move' => $this->t('Move'),
      'copy' => $this->t('Copy'),
      'element' => $this->t('element'),
      'community' => array(
          'invit' => $this->t('Invite collaborators'),
          'advanced' => $this->t('Advanced properties')));

    $feed = array(
      "deleteFeed" => $this->t('Do you really want to delete the feed?'));

    $this->view->json = array(
      'global' => $jsonGlobal, 'login' => $login, 'feed' => $feed, 'browse' => $browse);

    // Init Dynamic Help (the order makes sense for the animation)
    if($this->view->isDynamicHelp)
      {
      if($this->isDemoMode())
        {
        $this->addDynamicHelp('.loginLink', "<b>Authenticate.</b><br/><br/>Demo Administrator:<br/>- Login: admin@kitware.com<br/>- Password: admin<br/><br/>
                              Demo User:<br/>-Login: user@kitware.com<br/>-Password: user", 'bottom left', 'top right');
        }

      if($this->logged)
        {
        $this->addDynamicHelp('#startingGuideLink', 'Show the <b>Starting Guide</b>. You can disable these messages from this panel.');
        }
      else
        {
        $this->addDynamicHelp('.HeaderLogo', 'The <b>Midas Platform</b> integrates multimedia server technology with open-source data analysis and visualization clients.');
        }

      $this->addDynamicHelp('.HeaderSearch', '<b>Quick search</b>. Use this tool to quickly find information and data.');
      $this->addDynamicHelp('li.uploadFile a', '<b>Upload</b> files, data using this button.');

      if($this->logged)
        {
        $this->addDynamicHelp('#topUserName', '<b>Manage</b> your information.', 'bottom left', 'top right');
        }
      else
        {
        $this->addDynamicHelp('.registerLink', '<b>Register</b> to create your personal space.', 'bottom left', 'top right');
        }

      $this->addDynamicHelp('.SideBar ul:first', '<b>Navigation menu</b>. Browse, explore and manage data.');
      }

    Zend_Loader::loadClass('JsonComponent', BASE_PATH.'/core/controllers/components');

    // init layout
    if($this->_helper->hasHelper('layout'))
      {
      // layout explicitly declared as a parameter
      $layoutParam = $this->_getParam('layout');
      if(isset($layoutParam) && file_exists($this->_helper->layout->getLayoutPath().'/'.$layoutParam.'.phtml'))
        {
        $this->_helper->layout->setLayout($layoutParam);
        }
      else
        {
        $modulesConfig = Zend_Registry::get('configsModules');
        foreach($modulesConfig as $key => $module)
          {
          if(file_exists(BASE_PATH.'/modules/'.$key.'/layouts/layout-core.phtml'))
            {
            $this->_helper->layout->setLayoutPath(BASE_PATH.'/modules/'.$key.'/layouts/');
            $this->_helper->layout->setLayout('layout-core');
            }
          if(file_exists(BASE_PATH.'/privateModules/'.$key.'/layouts/layout-core.phtml'))
            {
            $this->_helper->layout->setLayoutPath(BASE_PATH.'/privateModules/'.$key.'/layouts/');
            $this->_helper->layout->setLayout('layout-core');
            }
          }
        }
      $this->view->json['layout'] = $this->_helper->layout->getLayout();
      }

    // Handle progress tracking if client specifies a progressId parameter
    $progressId = $this->_getParam('progressId');
    if(isset($progressId) && $fc->getRequest()->getControllerName() != 'progress')
      {
      $progressModel = MidasLoader::loadModel('Progress');
      $this->progressDao = $progressModel->load($progressId);
      }
    else
      {
      $this->progressDao = null;
      }

    // If there is an outbound HTTP proxy configured on this server, set it up here
    $httpProxy = Zend_Registry::get('configGlobal')->httpproxy;
    if($httpProxy)
      {
      $opts = array('http' => array('proxy' => $httpProxy));
      stream_context_set_default($opts);
      }

    // For Logging
    $logTrace = Zend_Registry::get('configGlobal')->logtrace;
    if(isset($logTrace) && $logTrace)
      {
      $this->_logRequest();
      }
    } // end preDispatch()

  /**
   * Call this to log the request parameters to logs/trace.log
   */
  private function _logRequest()
    {
    $fc = Zend_Controller_Front::getInstance();

    $entry  = date("Y-m-d H:i:s")."\n";
    if(isset($_SERVER['REMOTE_ADDR']))
      {
      $entry .= 'IP='.$_SERVER['REMOTE_ADDR']."\n";
      }
    $entry .= 'Action=';
    $module = $fc->getRequest()->getModuleName();
    if($module != 'default')
      {
      $entry .= $module.'/';
      }
    $entry .= $fc->getRequest()->getControllerName();
    $entry .= '/'.$fc->getRequest()->getActionName().' ';
    $entry .= $fc->getRequest()->getMethod()."\n";

    $entry .= "Params=\n";
    $params = $this->_getAllParams();
    foreach($params as $key => $value)
      {
      if(strpos(strtolower($key), 'password') === false && is_scalar($value))
        {
        $entry .= '  '.$key.'='.$value."\n";
        }
      }

    $entry .= 'User=';
    if($this->userSession && $this->userSession->Dao)
      {
      $entry .= $this->userSession->Dao->getKey();
      }
    $entry .= "\n\n";

    $fh = fopen(LOGS_PATH.'/trace.log', 'a');
    fwrite($fh, $entry);
    fclose($fh);
    }

  /** show dynamic help ? */
  function isDynamicHelp()
    {
    if($this->isDemoMode())
      {
      return true;
      }
    try
      {
      $dynamichelp = Zend_Registry::get('configGlobal')->dynamichelp;
      if($dynamichelp && $this->userSession != null && $this->userSession->Dao != null)
        {
        return $this->userSession->Dao->getDynamichelp() == 1;
        }
      return $dynamichelp == 1;
      }
    catch(Zend_Exception $exc)
      {
      $this->getLogger()->warn($exc->getMessage());
      return false;
      }
    }

  /** show starting guide ? */
  function isStartingGuide()
    {
    try
      {
      if($this->userSession != null && $this->userSession->Dao != null && isset($_GET['first']))
        {
        return $this->userSession->Dao->getDynamichelp() == 1;
        }
      return false;
      }
    catch(Zend_Exception $exc)
      {
      $this->getLogger()->warn($exc->getMessage());
      return false;
      }
    }

  /** get server's url */
  function getServerURL()
    {
    return UtilityComponent::getServerURL();
    }

  /** check if testing environement is set */
  public function isTestingEnv()
    {
    return Zend_Registry::get('configGlobal')->environment == 'testing';
    }

  /** Add a qtip help in the page
   *
   * @param type $selector (javascript selector)
   * @param type $text
   * @param type $location
   * @param type $arrow
   */
  public function addDynamicHelp($selector, $text, $location = 'bottom right', $arrow = 'top left')
    {
    $this->view->json['dynamicHelp'][] = array('selector' => $selector, 'text' => htmlspecialchars($text), 'my' => $arrow, 'at' => $location);
    }
  /** check if demo mode is set */
  public function isDemoMode()
    {
    return Zend_Registry::get('configGlobal')->demomode == 1;
    }

  /** disable layout */
  public function disableLayout()
    {
    if($this->_helper->hasHelper('layout'))
      {
      $this->_helper->layout->disableLayout();
      }
    }
  /** disable view */
  public function disableView()
    {
    $this->_helper->viewRenderer->setNoRender();
    }

  /** Show a jgrowl Message */
  public function showNotificationMessage($message)
    {
    if(!isset($this->view->json['triggerNotification']))
      {
      $this->view->json['triggerNotification'] = array();
      }
    $this->view->json['triggerNotification'][] = $message;
    }

  /** check if midas needs to be upgraded */
  public function isUpgradeNeeded()
    {
    require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
    $upgradeComponent = new UpgradeComponent();
    $db = Zend_Registry::get('dbAdapter');
    $dbtype = Zend_Registry::get('configDatabase')->database->adapter;

    $upgradeComponent->initUpgrade('core', $db, $dbtype);
    if($upgradeComponent->getNewestVersion() > $upgradeComponent->transformVersionToNumeric(Zend_Registry::get('configDatabase')->version))
      {
      return true;
      }
    $modules = array();
    $modulesConfig = Zend_Registry::get('configsModules');
    foreach($modulesConfig as $key => $module)
      {
      $upgradeComponent->initUpgrade($key, $db, $dbtype);
      if($upgradeComponent->getNewestVersion() != 0 && $upgradeComponent->getNewestVersion() > $upgradeComponent->transformVersionToNumeric($module->version))
        {
        return true;
        }
      }
    return false;
    }

  /** zend post dispatch*/
  public function postDispatch()
    {
    parent::postDispatch();
    $this->view->json = JsonComponent::encode($this->view->json);
    $this->view->generatedTimer = round((microtime(true) - START_TIME), 3);
    if(Zend_Registry::get('configGlobal')->environment != 'testing')
      {
      header('Content-Type: text/html; charset=UTF-8');
      }
    if($this->progressDao != null)
      {
      // delete progress object since execution is complete
      $progressModel = MidasLoader::loadModel('Progress');
      $progressModel->delete($this->progressDao);
      }
    }

  /** trigger logging (javascript) */
  public function haveToBeLogged()
    {
    $this->view->header = $this->t(MIDAS_LOGIN_REQUIRED);
    $this->view->json['global']['needToLog'] = true;
    $this->_helper->viewRenderer->setNoRender();
    }

  /** ensure the request is ajax */
  public function requireAjaxRequest()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception(MIDAS_AJAX_REQUEST_ONLY);
      }
    }

  /** ensure that the user is logged in and has admin privileges */
  public function requireAdminPrivileges()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception(MIDAS_ADMIN_PRIVILEGES_REQUIRED, 403);
      }
    }

  /** translation */
  protected function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    } //end method t

  /**completion eclipse*/
  /**
  * Assetstrore Model
  * @var AssetstoreModelBase
  */
  var $Assetstore;
  /**
  * Bitstream Model
  * @var BitstreamModelBase
  */
  var $Bitstream;
  /**
  * Community Model
  * @var CommunityModelBase
  */
  var $Community;
  /**
  * Errorlog Model
  * @var ErrorlogModelBase
  */
  var $Errorlog;
  /**
  * Feed Model
  * @var FeedModelBase
  */
  var $Feed;
  /**
  * Feedpolicygroup Model
  * @var FeedpolicygroupModelBase
  */
  var $Feedpolicygroup;
  /**
  * Feedpolicyuser Model
  * @var FeedpolicyuserModelBase
  */
  var $Feedpolicyuser;
  /**
  * Folder Model
  * @var FolderModelBase
  */
  var $Folder;
  /**
  * Folderpolicygroup Model
  * @var FolderpolicygroupModelBase
  */
  var $Folderpolicygroup;
  /**
  * Folderpolicyuser Model
  * @var FolderpolicyuserModelBase
  */
  var $Folderpolicyuser;
  /**
  * Group Model
  * @var GroupModelBase
  */
  var $Group;
  /**
  * Item Model
  * @var ItemModelBase
  */
  var $Item;
  /**
  * Itempolicygroup Model
  * @var ItempolicygroupModelBase
  */
  var $Itempolicygroup;
  /**
  * Itempolicyuser Model
  * @var ItempolicyuserModelBase
  */
  var $Itempolicyuser;
  /**
  * ItemRevision Model
  * @var ItemRevisionModelBase
  */
  var $ItemRevision;
  /**
  * User Model
  * @var UserModelBase
  */
  var $User;
  /**
  * Setting Model
  * @var SettingModelBase
  */
  var $Setting;

  /**end completion eclipse */
  } // end class
