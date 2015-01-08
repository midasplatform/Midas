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
 * Generic controller class.
 *
 * @package Core\Controller
 */
class AppController extends MIDAS_GlobalController
{

    /** @var string */
    protected $coreWebroot;

    /** @var bool */
    protected $logged = false;

    /** @var null|ProgressDao */
    protected $progressDao = null;

    /** @var Zend_Session_Namespace */
    protected $userSession;

    /**
     * Pre-dispatch routines.
     *
     * @return void
     * @throws Zend_Exception
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->view->setEncoding('UTF-8');

        $this->view->setScriptPath(BASE_PATH."/core/views");

        $fc = Zend_Controller_Front::getInstance();
        $module = $fc->getRequest()->getModuleName();
        if ($module == 'default') {
            $module = 'core';
        }
        $this->getLogger()->setEventItem('module', $module);
        $this->view->webroot = $fc->getBaseUrl();
        $this->coreWebroot = $this->view->webroot.'/core';
        $this->view->coreWebroot = $this->coreWebroot;

        Zend_Registry::set('webroot', $this->view->webroot);
        Zend_Registry::set('coreWebroot', $this->view->coreWebroot);

        $this->view->title = Zend_Registry::get('configGlobal')->application->name;
        $this->view->metaDescription = Zend_Registry::get('configGlobal')->application->description;

        // Set the version
        $this->view->version = '3.2.8';
        if (isset(Zend_Registry::get('configDatabase')->version)) {
            $this->view->version = Zend_Registry::get('configDatabase')->version;
        }

        require_once BASE_PATH.'/core/models/dao/UserDao.php';
        require_once BASE_PATH.'/core/models/dao/ItemDao.php';
        // Init Session
        if ($fc->getRequest()->getActionName() != 'login' || $fc->getRequest()->getControllerName() != 'user'
        ) {
            if (isset($_POST['sid'])) {
                Zend_Session::setId($_POST['sid']);
            }
            Zend_Session::start();

            // log in when testing
            $testingUserId = $this->getParam('testingUserId');
            if (Zend_Registry::get('configGlobal')->environment == 'testing' && isset($testingUserId)
            ) {
                $user = new Zend_Session_Namespace('Auth_User_Testing');

                /** @var UserModel $userModel */
                $userModel = MidasLoader::loadModel('User');
                $user->Dao = $userModel->load($testingUserId);
                if ($user->Dao == false) {
                    throw new Zend_Exception('Unable to find user');
                }
            } else {
                $user = new Zend_Session_Namespace('Auth_User');
                $user->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
            }

            /** @var Zend_Controller_Request_Http $request */
            $request = $this->getRequest();

            if ($user->Dao == null && $fc->getRequest()->getControllerName() != 'install'
            ) {
                /** @var UserModel $userModel */
                $userModel = MidasLoader::loadModel('User');
                $cookieData = $request->getCookie(MIDAS_USER_COOKIE_NAME);

                if (!empty($cookieData)) {
                    $notifier = new MIDAS_Notifier(false, null);
                    $notifications = $notifier->callback('CALLBACK_CORE_USER_COOKIE', array('value' => $cookieData));
                    $cookieOverride = false;
                    foreach ($notifications as $result) {
                        if ($result) {
                            $cookieOverride = true;
                            $userDao = $result;
                            $user->Dao = $userDao;
                            break;
                        }
                    }
                    if (!$cookieOverride) {
                        $tmp = explode('-', $cookieData);
                        if (count($tmp) == 2) {
                            $userDao = $userModel->load($tmp[0]);
                            if ($userDao != false) {
                                // authenticate valid users in the appropriate method for the
                                // current application version
                                if (version_compare(Zend_Registry::get('configDatabase')->version, '3.2.12', '>=')) {
                                    $auth = $userModel->hashExists($tmp[1]);
                                } else {
                                    $auth = $userModel->legacyAuthenticate($userDao, '', '', $tmp[1]);
                                }
                                // if authenticated, set the session user to be this user
                                if ($auth) {
                                    $user->Dao = $userDao;
                                }
                            }
                        }
                    }
                }
            }

            session_write_close();

            $this->userSession = $user;
            $this->view->recentItems = array();
            if ($user->Dao != null && $user->Dao instanceof UserDao) {
                $this->logged = true;
                $this->view->logged = true;
                $this->view->userDao = $user->Dao;
                $cookieName = hash('sha1', MIDAS_ITEM_COOKIE_NAME.$this->userSession->Dao->user_id);
                $cookieData = $request->getCookie($cookieName);
                $this->view->recentItems = array();
                if (isset($cookieData) && file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')
                ) { // check if midas installed
                    /** @var ItemModel $itemModel */
                    $itemModel = MidasLoader::loadModel('Item');
                    $tmpRecentItems = unserialize($cookieData);
                    $recentItems = array();
                    if (!empty($tmpRecentItems) && is_array($tmpRecentItems)) {
                        foreach ($tmpRecentItems as $t) {
                            if (is_numeric($t)) {
                                $item = $itemModel->load($t);
                                if ($item !== false) {
                                    $recentItems[] = $item->toArray();
                                }
                            }
                        }
                    }

                    $this->view->recentItems = $recentItems;
                }
            } else {
                $this->view->logged = false;
                $this->logged = false;
            }
        } else {
            $this->userSession = null;
            $this->view->logged = false;
            $this->logged = false;
        }

        if (isset($user)) {
            Zend_Registry::set('userSession', $user);
        } else {
            Zend_Registry::set('userSession', null);
            $user = null;
        }

        // init notifier
        Zend_Registry::set('notifier', new MIDAS_Notifier($this->logged, $this->userSession));

        $this->view->lang = Zend_Registry::get('configGlobal')->application->lang;

        $this->view->isStartingGuide = $this->isStartingGuide();
        $this->view->isDynamicHelp = $this->isDynamicHelp();

        // create a global javascript json array
        $jsonGlobal = array(
            "webroot" => $this->view->webroot,
            "coreWebroot" => $this->view->coreWebroot,
            "logged" => $this->logged,
            "needToLog" => false,
            "currentUri" => $this->getRequest()->REQUEST_URI,
            "lang" => Zend_Registry::get('configGlobal')->application->lang,
            "dynamichelp" => $this->isDynamicHelp(),
            "dynamichelpAnimate" => $this->isDynamicHelp() && isset($_GET['first']),
            "startingGuide" => $this->isStartingGuide(),
            "Yes" => $this->t('Yes'),
            "No" => $this->t('No'),
        );

        $login = array(
            "titleUploadLogin" => $this->t('Please log in'),
            "contentUploadLogin" => $this->t('You need to be logged in to be able to upload files.'),
        );

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
            'ignoreSelectedFolders' => $this->t(
                '(Folder type does not support this action; all selected folders are ignored.)'
            ),
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
                'advanced' => $this->t('Advanced properties'),
            ),
        );

        $feed = array("deleteFeed" => $this->t('Do you really want to delete the feed?'));

        $this->view->json = array('global' => $jsonGlobal, 'login' => $login, 'feed' => $feed, 'browse' => $browse);

        // Init Dynamic Help (the order makes sense for the animation)
        if ($this->view->isDynamicHelp) {
            if ($this->isDemoMode()) {
                $this->addDynamicHelp('.loginLink', MIDAS_DEMO_DYNAMIC_HELP, 'bottom left', 'top right');
            }

            if ($this->logged) {
                $this->addDynamicHelp(
                    '#startingGuideLink',
                    'Show the Starting Guide. You can disable these messages from this panel.'
                );
            } else {
                $this->addDynamicHelp(
                    '.HeaderLogo',
                    'The Midas Platform integrates multimedia server technology with open-source data analysis and visualization clients.'
                );
            }

            $this->addDynamicHelp(
                '.HeaderSearch',
                'Quick search. Use this tool to quickly find information and data.'
            );
            $this->addDynamicHelp('li.uploadFile a', 'Upload files, data using this button.');

            if ($this->logged) {
                $this->addDynamicHelp('#topUserName', 'Manage your information.', 'bottom left', 'top right');
            } else {
                $this->addDynamicHelp(
                    '.registerLink',
                    'Register to create your personal space.',
                    'bottom left',
                    'top right'
                );
            }

            $this->addDynamicHelp('.SideBar ul:first', 'Navigation menu. Browse, explore and manage data.');
        }

        Zend_Loader::loadClass('JsonComponent', BASE_PATH.'/core/controllers/components');

        // init layout
        if ($this->_helper->hasHelper('layout')) {
            // layout explicitly declared as a parameter
            $layoutParam = $this->getParam('layout');
            if (isset($layoutParam) && file_exists(
                    $this->_helper->layout->getLayoutPath().'/'.$layoutParam.'.phtml'
                )
            ) {
                $this->_helper->layout->setLayout($layoutParam);
            } else {
                $enabledModules = Zend_Registry::get('modulesEnable');
                foreach ($enabledModules as $enabledModule) {
                    if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/layouts/layout-core.phtml')) {
                        $this->_helper->layout->setLayoutPath(BASE_PATH.'/modules/'.$enabledModule.'/layouts/');
                        $this->_helper->layout->setLayout('layout-core');
                    }
                    if (file_exists(BASE_PATH.'/privateModules/'.$enabledModule.'/layouts/layout-core.phtml')) {
                        $this->_helper->layout->setLayoutPath(BASE_PATH.'/privateModules/'.$enabledModule.'/layouts/');
                        $this->_helper->layout->setLayout('layout-core');
                    }
                }
            }
            $this->view->json['layout'] = $this->_helper->layout->getLayout();
        }

        // Handle progress tracking if client specifies a progressId parameter
        $progressId = $this->getParam('progressId');
        if (isset($progressId) && $fc->getRequest()->getControllerName() != 'progress'
        ) {
            /** @var ProgressModel $progressModel */
            $progressModel = MidasLoader::loadModel('Progress');
            $this->progressDao = $progressModel->load($progressId);
        } else {
            $this->progressDao = null;
        }

        // If there is an outbound HTTP proxy configured on this server, set it up here
        $httpProxy = Zend_Registry::get('configGlobal')->httpproxy;
        if ($httpProxy) {
            $opts = array('http' => array('proxy' => $httpProxy));
            stream_context_set_default($opts);
        }
    }

    /**
     * Show dynamic help?
     *
     * @return bool
     */
    public function isDynamicHelp()
    {
        try {
            $dynamichelp = Zend_Registry::get('configGlobal')->dynamichelp;
            if ($dynamichelp && $this->userSession != null) {
                $userDao = $this->userSession->Dao;
                if ($userDao != null && $userDao instanceof UserDao) {
                    return $userDao->getDynamichelp() == 1;
                }
            }

            return $dynamichelp == 1;
        } catch (Zend_Exception $exc) {
            $this->getLogger()->warn($exc->getMessage());

            return false;
        }
    }

    /**
     * Show starting guide?
     *
     * @return bool
     */
    public function isStartingGuide()
    {
        try {
            if ($this->userSession != null && $this->userSession->Dao != null && isset($_GET['first'])) {
                return $this->userSession->Dao->getDynamichelp() == 1;
            }

            return false;
        } catch (Zend_Exception $exc) {
            $this->getLogger()->warn($exc->getMessage());

            return false;
        }
    }

    /**
     * Return the URL of the server.
     *
     * @return string
     */
    public function getServerURL()
    {
        return UtilityComponent::getServerURL();
    }

    /**
     * Check whether the testing environment is set.
     *
     * @return bool
     */
    public function isTestingEnv()
    {
        return Zend_Registry::get('configGlobal')->environment == 'testing';
    }

    /**
     * Add a help tooltip to the page.
     *
     * @param string $selector JavaScript selector
     * @param string $text
     * @param string $location
     * @param string $arrow
     */
    public function addDynamicHelp($selector, $text, $location = 'bottom right', $arrow = 'top left')
    {
        $this->view->json['dynamicHelp'][] = array(
            'selector' => $selector,
            'text' => htmlspecialchars($text),
            'my' => $arrow,
            'at' => $location,
        );
    }

    /**
     * Check if demo mode is set.
     *
     * @return bool
     */
    public function isDemoMode()
    {
        if (in_array('demo', Zend_Registry::get('modulesEnable'))) {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');

            return $settingModel->getValueByName('enabled', 'demo');
        }

        return false;
    }

    /** Disable the layout. */
    public function disableLayout()
    {
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout();
        }
    }

    /** Disable the view. */
    public function disableView()
    {
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Show a notification message.
     *
     * @param string $message
     */
    public function showNotificationMessage($message)
    {
        if (!isset($this->view->json['triggerNotification'])) {
            $this->view->json['triggerNotification'] = array();
        }
        $this->view->json['triggerNotification'][] = $message;
    }

    /** Post-dispatch routines. */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->view->json = JsonComponent::encode($this->view->json);
        if (Zend_Registry::get('configGlobal')->environment != 'testing') {
            header('Content-Type: text/html; charset=UTF-8');
        }
        if ($this->progressDao != null) {
            // delete progress object since execution is complete
            /** @var ProgressModel $progressModel */
            $progressModel = MidasLoader::loadModel('Progress');
            $progressModel->delete($this->progressDao);
        }
    }

    /** Trigger logging (JavaScript). */
    public function haveToBeLogged()
    {
        $this->view->header = $this->t(MIDAS_LOGIN_REQUIRED);
        $this->view->json['global']['needToLog'] = true;
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Ensure the request is AJAX.
     *
     * @throws Zend_Exception
     */
    public function requireAjaxRequest()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new Zend_Exception(MIDAS_AJAX_REQUEST_ONLY);
        }
    }

    /**
     * Ensure that the user is logged in and has admin privileges.
     *
     * @throws Zend_Exception
     */
    public function requireAdminPrivileges()
    {
        if (!$this->logged || !$this->userSession->Dao->getAdmin() == 1) {
            throw new Zend_Exception(MIDAS_ADMIN_PRIVILEGES_REQUIRED, 403);
        }
    }

    /**
     * Return the translation of a given string.
     *
     * @param string $text string to translate
     * @return string translated string or the input string if there is no translation
     */
    protected function t($text)
    {
        Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');

        return InternationalizationComponent::translate($text);
    }

    /** @var ActivedownloadModel */
    public $Activedownload;

    /** @var AssetstoreModel */
    public $Assetstore;

    /** @var BitstreamModel */
    public $Bitstream;

    /** @var CommunityModel */
    public $Community;

    /** @var CommunityInvitationModel */
    public $CommunityInvitation;

    /** @var object */
    public $Component;

    /** @var ErrorlogModel */
    public $Errorlog;

    /** @var FeedModel */
    public $Feed;

    /** @var FeedpolicygroupModel */
    public $Feedpolicygroup;

    /** @var FeedpolicyuserModel */
    public $Feedpolicyuser;

    /** @var FolderModel */
    public $Folder;

    /** @var FolderpolicygroupModel */
    public $Folderpolicygroup;

    /** @var FolderpolicyuserModel */
    public $Folderpolicyuser;

    /** @var object */
    public $Form;

    /** @var GroupModel */
    public $Group;

    /** @var ItemModel */
    public $Item;

    /** @var ItempolicygroupModel */
    public $Itempolicygroup;

    /** @var ItempolicyuserModel */
    public $Itempolicyuser;

    /** @var ItemRevisionModel */
    public $ItemRevision;

    /** @var LicenseModel */
    public $License;

    /** @var MetadataModel */
    public $Metadata;

    /** @var NewUserInvitationModel */
    public $NewUserInvitation;

    /** @var PendingUserModel */
    public $PendingUser;

    /** @var ProgressModel */
    public $Progress;

    /** @var SettingModel */
    public $Setting;

    /** @var TokenModel */
    public $Token;

    /** @var UserModel */
    public $User;

    /** @var UserapiModel */
    public $Userapi;
}
