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

/** User Controller */
class UserController extends AppController
{
    public $_models = array(
        'User',
        'NewUserInvitation',
        'PendingUser',
        'Folder',
        'Folderpolicygroup',
        'Folderpolicyuser',
        'Group',
        'Feed',
        'Feedpolicygroup',
        'Feedpolicyuser',
        'Group',
        'Item',
        'Community',
        'Setting',
    );
    public $_daos = array('User', 'Folder', 'Folderpolicygroup', 'Folderpolicyuser', 'Group');
    public $_components = array('Breadcrumb', 'Date', 'Filter', 'Random', 'Sortdao');
    public $_forms = array('User');

    /** Init Controller */
    public function init()
    {
        $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
        if (isset($actionName) && is_numeric($actionName)) {
            $this->forward('userpage', null, null, array('user_id' => $actionName));
        }
    }

    /** Index */
    public function indexAction()
    {
        $this->view->header = $this->t("Users");
        $this->view->activemenu = 'user'; // set the active menu

        $order = $this->getParam('order');
        $offset = $this->getParam('offset');

        if (!isset($order)) {
            $order = 'view';
        }
        if (!isset($offset)) {
            $offset = 0;
        }

        if ($this->logged && $this->userSession->Dao->isAdmin()) {
            $users = $this->User->getAll(false, 100, $order, $offset);
            $this->view->isAdmin = true;
        } else {
            $users = $this->User->getAll(true, 100, $order, $offset, $this->userSession->Dao);
            $this->view->isAdmin = false;
        }

        $this->view->order = $order;
        $this->view->offset = $offset;
        $this->view->users = $users;
        $this->view->nUsers = $this->User->getCountAll();
    }

    /** Recover the password (ajax) */
    public function recoverpasswordAction()
    {
        if ($this->logged) {
            throw new Zend_Exception('Shouldn\'t be logged in');
        }

        if ((int) Zend_Registry::get('configGlobal')->get('allow_password_reset', 0) === 0) {
            throw new Zend_Exception('Password reset is disabled');
        }

        $this->disableLayout();
        $email = $this->getParam('email');
        if (isset($email)) {
            $this->disableView();
            $user = $this->User->getByEmail($email);

            // Check if the email is already registered
            if (!$user) {
                echo JsonComponent::encode(array(false, $this->t('No user registered with that email.')));
                exit;
            }

            $notifications = Zend_Registry::get('notifier')->callback(
                'CALLBACK_CORE_RESET_PASSWORD',
                array('user' => $user)
            );
            foreach ($notifications as $result) {
                if ($result['status'] === true) {
                    echo JsonComponent::encode(array(true, $result['message']));

                    return;
                }
            }

            $pass = $this->Component->Random->generateString(32);
            $this->User->changePassword($user, $pass);

            $url = $this->getServerURL().$this->view->webroot;

            $subject = "Password Request";
            $body = "You have requested a new password for Midas Platform.<br />";
            $body .= "Please go to this page to log into Midas Platform and change your password:<br />";
            $body .= "<a href=\"".$url."\">".$url."</a><br />";
            $body .= "Your new password is: ".$pass."<br />";

            $result = Zend_Registry::get('notifier')->callback(
                'CALLBACK_CORE_SEND_MAIL_MESSAGE',
                array(
                    'to' => $email,
                    'subject' => $subject,
                    'html' => $body,
                    'event' => 'user_reset_password',
                )
            );

            if ($result) {
                $this->User->save($user);
                echo JsonComponent::encode(array(true, 'Password request sent.'));
            } else {
                echo JsonComponent::encode(array(false, 'Could not send password request.'));
            }
        }
    }

    /**
     * Logout a user.
     *
     * @param noRedirect Set this parameter if you are calling logout with ajax
     *                   and do not want the controller to redirect
     */
    public function logoutAction()
    {
        session_start(); // we closed session before, must restart it to logout
        $this->userSession->Dao = null;
        Zend_Session::ForgetMe();
        $request = $this->getRequest();
        $date = new DateTime();
        $interval = new DateInterval('P1M');
        setcookie(
            MIDAS_USER_COOKIE_NAME,
            null,
            $date->sub($interval)->getTimestamp(),
            '/',
            $request->getHttpHost(),
            (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
            true
        );
        $noRedirect = $this->getParam('noRedirect');
        if (isset($noRedirect)) {
            $this->disableView();
            $this->disableLayout();

            return;
        }
        $this->redirect('/');
    }

    /** Set user's starting guide value */
    public function startingguideAction()
    {
        $this->disableLayout();
        $this->disableView();
        if ($this->logged && isset($_POST['value'])) {
            $value = 0;
            if ($_POST['value'] == 1) {
                $value = 1;
            }
            $this->userSession->Dao->setDynamichelp($value);
            $user = $this->User->load($this->userSession->Dao->getKey());
            $user->setDynamichelp($value);
            $this->User->save($user);
        }
    }

    /**
     * Function for registering a new user; provides an ajax response; does not attempt to redirect,
     * instead post-register action is left up to the client
     */
    public function ajaxregisterAction()
    {
        $adminCreate = $this->getParam('adminCreate');
        $adminCreate = isset($adminCreate);

        if ($adminCreate) {
            $this->requireAdminPrivileges();
        }
        $this->disableView();
        $this->disableLayout();
        if (!$adminCreate && isset(Zend_Registry::get('configGlobal')->closeregistration) && Zend_Registry::get(
                'configGlobal'
            )->closeregistration == "1"
        ) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => 'New user registration is disabled.'));

            return;
        }
        $form = $this->Form->User->createRegisterForm();
        if ($this->_request->isPost()) {
            $nopass = (bool) $this->getParam('nopassword');
            if ($adminCreate && $nopass) {
                $form->populate($this->getRequest()->getPost());
                $passwd = $this->Component->Random->generateString(32);
                $form->getElement('password1')->setValue($passwd);
                $form->getElement('password2')->setValue($passwd);

                if (!$form->getValue('firstname') && !$form->getValue('lastname')
                ) {
                    $form->getElement('firstname')->setValue('[Invited');
                    $form->getElement('lastname')->setValue('User]');
                }
            } elseif (!$form->isValid($this->getRequest()->getPost())) {
                echo JsonComponent::encode(
                    array(
                        'status' => 'error',
                        'message' => 'Registration failed',
                        'validValues' => $form->getValidValues($this->getRequest()->getPost()),
                    )
                );

                return;
            }

            if ($this->User->getByEmail(strtolower($form->getValue('email'))) !== false
            ) {
                echo JsonComponent::encode(
                    array(
                        'status' => 'error',
                        'message' => 'That email is already registered',
                        'alreadyRegistered' => true,
                    )
                );

                return;
            }

            $email = strtolower(trim($form->getValue('email')));
            $addressVerification = $this->Setting->getValueByName('address_verification', 'mail');

            if ($adminCreate || $addressVerification != '1') {
                $newUser = $this->User->createUser(
                    $email,
                    $form->getValue('password1'),
                    $form->getValue('firstname'),
                    $form->getValue('lastname')
                );

                if ($adminCreate) {
                    $subject = 'User Registration';
                    $url = $this->getServerURL().$this->view->webroot;
                    $body = "An administrator has created a user account for you at the following Midas Platform instance:<br/><br/>";
                    $body .= '<a href="'.$url.'">'.$url.'</a><br /><br />';

                    if (!$nopass) {
                        $body .= "Log in using this email address (".$email.") and your initial password:<br/><br/>";
                        $body .= '<b>'.$form->getValue('password1').'</b><br/><br/>';
                    }

                    $result = Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_SEND_MAIL_MESSAGE',
                        array(
                            'to' => $email,
                            'subject' => $subject,
                            'html' => $body,
                            'event' => 'user_create',
                        )
                    );

                    if ($result) {
                        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'User created successfully'));
                    } else {
                        echo JsonComponent::encode(
                            array(
                                'status' => 'warning',
                                'message' => 'User created, but sending of email failed',
                                'validValues' => $form->getValidValues($this->getRequest()->getPost()),
                            )
                        );
                    }
                } else {
                    if (!headers_sent()) {
                        session_start();
                    }
                    $this->userSession->Dao = $newUser;
                    session_write_close();
                    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'User registered successfully'));
                }
            } else {
                $pendingUser = $this->PendingUser->createPendingUser(
                    $email,
                    $form->getValue('firstname'),
                    $form->getValue('lastname'),
                    $form->getValue('password1')
                );

                $subject = 'User Registration';
                $url = $this->getServerURL().$this->view->webroot.'/user/verifyemail?email='.$email;
                $url .= '&authKey='.$pendingUser->getAuthKey();
                $body = "You have created an account on Midas Platform.<br/><br/>";
                $body .= '<a href="'.$url.'">Click here</a> to verify your email and complete registration.<br/><br/>';
                $body .= 'If you did not initiate this registration, please disregard this email.<br/><br/>';

                $result = Zend_Registry::get('notifier')->callback(
                    'CALLBACK_CORE_SEND_MAIL_MESSAGE',
                    array(
                        'to' => $email,
                        'subject' => $subject,
                        'html' => $body,
                        'event' => 'user_verify',
                    )
                );

                if ($result) {
                    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Verification email sent'));
                } else {
                    echo JsonComponent::encode(
                        array('status' => 'warning', 'message' => 'Failed to send verification email')
                    );
                }
            }
        }
    }

    /** Register a user */
    public function registerAction()
    {
        if (isset(Zend_Registry::get('configGlobal')->closeregistration) && Zend_Registry::get(
                'configGlobal'
            )->closeregistration == "1"
        ) {
            throw new Zend_Exception('New user registration is disabled.');
        }
        $form = $this->Form->User->createRegisterForm();
        if ($this->_request->isPost() && $form->isValid($this->getRequest()->getPost())
        ) {
            if ($this->User->getByEmail(strtolower($form->getValue('email'))) !== false
            ) {
                throw new Zend_Exception("User already exists.");
            }

            $addressVerification = $this->Setting->getValueByName('address_verification', 'mail');

            if ($addressVerification != '1') {
                if (!headers_sent()) {
                    session_start();
                }
                $this->userSession->Dao = $this->User->createUser(
                    trim($form->getValue('email')),
                    $form->getValue('password1'),
                    trim($form->getValue('firstname')),
                    trim($form->getValue('lastname'))
                );
                session_write_close();

                $this->redirect('/feed?first=true');
            } else {
                $email = strtolower(trim($form->getValue('email')));
                $pendingUser = $this->PendingUser->createPendingUser(
                    $email,
                    $form->getValue('firstname'),
                    $form->getValue('lastname'),
                    $form->getValue('password1')
                );

                $subject = 'User Registration';
                $url = $this->getServerURL().$this->view->webroot.'/user/verifyemail?email='.$email;
                $url .= '&authKey='.$pendingUser->getAuthKey();
                $body = "You have created an account on Midas Platform.<br/><br/>";
                $body .= '<a href="'.$url.'">Click here</a> to verify your email and complete registration.<br/><br/>';
                $body .= 'If you did not initiate this registration, please disregard this email.<br/><br/>';

                $result = Zend_Registry::get('notifier')->callback(
                    'CALLBACK_CORE_SEND_MAIL_MESSAGE',
                    array(
                        'to' => $email,
                        'subject' => $subject,
                        'html' => $body,
                        'event' => 'user_verify',
                    )
                );

                if ($result) {
                    $this->redirect('/user/emailsent');
                }
            }
        }
        $this->view->form = $this->getFormAsArray($form);
        $this->disableLayout();
        $this->view->jsonRegister = JsonComponent::encode(
            array(
                'MessageNotValid' => $this->t('The email is not valid'),
                'MessageNotAvailable' => $this->t('That email is already registered'),
                'MessagePassword' => $this->t('Password too short'),
                'MessagePasswords' => $this->t('The passwords are not the same'),
                'MessageLastname' => $this->t('Please set your lastname'),
                'MessageTerms' => $this->t('Please validate the terms of service'),
                'MessageFirstname' => $this->t('Please set your firstname'),
            )
        );
    }

    /**
     * Simple page to tell the user that an email has been sent to them
     */
    public function emailsentAction()
    {
        $this->disableView();
        echo '<div style="margin-top: 10px; padding-left: 10px;">'.'An email with a link to complete registration has been sent to the '.'specified address. You may close this page.</div>';
    }

    /**
     * User will receive an email with a link to this action, which will move
     * their pending user record into a user record if the authKey is correct
     */
    public function verifyemailAction()
    {
        $email = $this->getParam('email');
        $authKey = $this->getParam('authKey');
        if (!isset($email) || !isset($authKey)) {
            throw new Zend_Exception('Must pass email and authKey parameters');
        }
        $pendingUser = $this->PendingUser->getByParams(array('email' => $email, 'auth_key' => $authKey));
        if (!$pendingUser) {
            throw new Zend_Exception('Invalid authKey or email');
        }

        if (!headers_sent()) {
            session_start();
        }
        $this->userSession->Dao = $this->User->createUser(
            $email,
            null,
            $pendingUser->getFirstname(),
            $pendingUser->getLastname(),
            0,
            $pendingUser->getSalt()
        );
        session_write_close();

        $this->PendingUser->delete($pendingUser);
        $this->redirect('/user/userpage');
    }

    /**
     * Function for logging in; provides an ajax response; does not attempt to redirect,
     * instead post-login action is left up to the client.
     * Does not currently support LDAP login.
     */
    public function ajaxloginAction()
    {
        $this->disableView();
        $this->disableLayout();

        $form = $this->Form->User->createLoginForm();
        if (!$form->isValid($this->getRequest()->getPost())) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => 'Invalid login form'));

            return;
        }
        $userDao = $this->User->getByEmail($form->getValue('email'));
        if ($userDao === false) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => 'Invalid username or password'));

            return;
        }
        $instanceSalt = Zend_Registry::get('configGlobal')->password->prefix;
        $passwordHash = hash($userDao->getHashAlg(), $instanceSalt.$userDao->getSalt().$form->getValue('password'));

        if ($this->User->hashExists($passwordHash)) {
            $notifications = Zend_Registry::get('notifier')->callback(
                'CALLBACK_CORE_AUTH_INTERCEPT',
                array('user' => $userDao)
            );
            foreach ($notifications as $value) {
                if ($value['override'] && $value['response']) {
                    echo $value['response'];

                    return;
                }
            }
            if ($userDao->getSalt() == '') {
                $passwordHash = $this->User->convertLegacyPasswordHash($userDao, $form->getValue('password'));
            }
            $request = $this->getRequest();
            $date = new DateTime();
            $interval = new DateInterval('P1M');
            setcookie(
                MIDAS_USER_COOKIE_NAME,
                $userDao->getKey().'-'.$passwordHash,
                $date->add($interval)->getTimestamp(),
                '/',
                $request->getHttpHost(),
                (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
                true
            );
            Zend_Session::start();
            $user = new Zend_Session_Namespace('Auth_User');
            $user->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
            $user->Dao = $userDao;
            $user->lock();
            $this->getLogger()->debug(__METHOD__." Log in : ".$userDao->getFullName());
            echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Login successful'));
        } else {
            echo JsonComponent::encode(array('status' => 'error', 'message' => 'Invalid username or password'));
        }
    }

    /** Login action */
    public function loginAction()
    {
        $request = $this->getRequest();
        $this->Form->User->uri = $request->getRequestUri();
        $form = $this->Form->User->createLoginForm();
        $this->view->form = $this->getFormAsArray($form);
        $this->disableLayout();
        if ($this->_request->isPost()) {
            $this->disableView();
            $previousUri = $this->getParam('previousuri');
            if ($form->isValid($request->getPost())) {
                try {
                    $notifications = array(); // initialize first in case of exception
                    $notifications = Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_AUTHENTICATION',
                        array('email' => $form->getValue('email'), 'password' => $form->getValue('password'))
                    );
                } catch (Zend_Exception $exc) {
                    $this->getLogger()->crit($exc->getMessage());
                }
                $authModule = false;
                foreach ($notifications as $user) {
                    if ($user) {
                        $userDao = $user;
                        $authModule = true;
                        break;
                    }
                }

                if (!$authModule) {
                    $userDao = $this->User->getByEmail($form->getValue('email'));
                    if ($userDao === false) {
                        echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));

                        return;
                    }
                }

                $instanceSalt = Zend_Registry::get('configGlobal')->password->prefix;
                $currentVersion = Zend_Registry::get('configDatabase')->version;
                // We have to have this so that an admin can log in to upgrade from version < 3.2.12 to >= 3.2.12.
                // Version 3.2.12 introduced the new password hashing and storage system.
                if (!$authModule && version_compare($currentVersion, '3.2.12', '>=')
                ) {
                    $passwordHash = hash(
                        $userDao->getHashAlg(),
                        $instanceSalt.$userDao->getSalt().$form->getValue('password')
                    );
                    $coreAuth = $this->User->hashExists($passwordHash);
                } elseif (!$authModule) {
                    $passwordHash = md5($instanceSalt.$form->getValue('password'));
                    $coreAuth = $this->User->legacyAuthenticate($userDao, $instanceSalt, $form->getValue('password'));
                }

                if ($authModule || $coreAuth) {
                    $notifications = Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_AUTH_INTERCEPT',
                        array('user' => $userDao)
                    );
                    foreach ($notifications as $value) {
                        if ($value['override'] && $value['response']) {
                            echo $value['response'];

                            return;
                        }
                    }
                    if (!$authModule && version_compare($currentVersion, '3.2.12', '>=') && $userDao->getSalt() == ''
                    ) {
                        $passwordHash = $this->User->convertLegacyPasswordHash($userDao, $form->getValue('password'));
                    }
                    $remember = $form->getValue('remerberMe');
                    if (!$this->isTestingEnv()) {
                        $date = new DateTime();
                        $interval = new DateInterval('P1M');
                        if (!$authModule && isset($remember) && $remember == 1) {
                            setcookie(
                                MIDAS_USER_COOKIE_NAME,
                                $userDao->getKey().'-'.$passwordHash,
                                $date->add($interval)->getTimestamp(),
                                '/',
                                $request->getHttpHost(),
                                (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
                                true
                            );
                        } else {
                            setcookie(
                                MIDAS_USER_COOKIE_NAME,
                                null,
                                $date->sub($interval)->getTimestamp(),
                                '/',
                                $request->getHttpHost(),
                                (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
                                true
                            );
                            Zend_Session::start();
                            $user = new Zend_Session_Namespace('Auth_User');
                            $user->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
                            $user->Dao = $userDao;
                            $user->lock();
                        }
                    }
                    $this->getLogger()->debug(__METHOD__." Log in : ".$userDao->getFullName());

                    if (isset($previousUri) && !empty($previousUri) && (!empty($this->view->webroot)) && strpos(
                            $previousUri,
                            'logout'
                        ) === false
                    ) {
                        $redirect = $previousUri;
                    } else {
                        $redirect = $this->view->webroot.'/feed?first=true';
                    }
                    echo JsonComponent::encode(array('status' => true, 'redirect' => $redirect));
                } else {
                    echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));
                }
            } else {
                echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));
            }
        }

        $this->view->allowPasswordReset = (int) Zend_Registry::get('configGlobal')->get('allow_password_reset', 0) === 1;
        $this->view->closeRegistration = (int) Zend_Registry::get('configGlobal')->get('closeregistration', 0) === 1;
    }

    /** Term of service */
    public function termofserviceAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout->disableLayout();
        }
    }

    /**
     * Test whether a given user already exists or not.
     *
     * @param entry The email/login to test.
     * @return Echoes "true" or "false".
     */
    public function userexistsAction()
    {
        $this->disableLayout();
        $this->disableView();
        $entry = $this->getParam('entry');
        if (!is_string($entry)) {
            echo 'false';

            return;
        }

        $notifications = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_CHECK_USER_EXISTS',
            array('entry' => $entry)
        );
        foreach ($notifications as $value) {
            if ($value === true) {
                echo 'true';

                return;
            }
        }

        $userDao = $this->User->getByEmail(strtolower($entry));
        if ($userDao) {
            echo 'true';
        } else {
            echo 'false';
        }
    }

    /** Settings page action */
    public function settingsAction()
    {
        if (!$this->logged || $this->isDemoMode()) {
            $this->disableView();

            return false;
        }

        $userId = $this->getParam('userId');
        if (isset($userId) && $userId != $this->userSession->Dao->getKey() && !$this->userSession->Dao->isAdmin()
        ) {
            throw new Zend_Exception(MIDAS_ADMIN_PRIVILEGES_REQUIRED);
        } elseif (isset($userId)) {
            $userDao = $this->User->load($userId);
        } else {
            $userDao = $this->userSession->Dao;
        }

        if (empty($userDao) || $userDao == false) {
            throw new Zend_Exception("Unable to load user");
        }

        $notifications = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_ALLOW_PASSWORD_CHANGE',
            array('user' => $userDao, 'currentUser' => $this->userSession->Dao)
        );
        $this->view->allowPasswordChange = true;

        foreach ($notifications as $allow) {
            if ($allow['allow'] === false) {
                $this->view->allowPasswordChange = false;
                break;
            }
        }

        $defaultValue = array();
        $defaultValue['email'] = $userDao->getEmail();
        $defaultValue['firstname'] = $userDao->getFirstname();
        $defaultValue['lastname'] = $userDao->getLastname();
        $defaultValue['company'] = $userDao->getCompany();
        $defaultValue['privacy'] = $userDao->getPrivacy();
        $defaultValue['city'] = $userDao->getCity();
        $defaultValue['country'] = $userDao->getCountry();
        $defaultValue['website'] = $userDao->getWebsite();
        $defaultValue['biography'] = $userDao->getBiography();
        $accountForm = $this->Form->User->createAccountForm($defaultValue);
        $this->view->accountForm = $this->getFormAsArray($accountForm);
        $this->view->prependFields = array();
        $this->view->appendFields = array();

        $moduleFields = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_USER_PROFILE_FIELDS',
            array('user' => $userDao, 'currentUser' => $this->userSession->Dao)
        );
        foreach ($moduleFields as $field) {
            if (isset($field['position']) && $field['position'] == 'top') {
                $this->view->prependFields[] = $field;
            } else {
                $this->view->appendFields[] = $field;
            }
        }

        if ($this->_request->isPost()) {
            $this->disableView();
            $this->disableLayout();
            $submitPassword = $this->getParam('modifyPassword');
            $modifyAccount = $this->getParam('modifyAccount');
            $modifyPicture = $this->getParam('modifyPicture');
            $modifyPictureGravatar = $this->getParam('modifyPictureGravatar');
            if (isset($submitPassword) && $this->logged) {
                if (!$this->view->allowPasswordChange) {
                    throw new Zend_Exception('Changing password is disallowed for this user');
                }
                $oldPass = $this->getParam('oldPassword');
                if ($userDao->getSalt() == '') {
                    $this->User->convertLegacyPasswordHash($userDao, $oldPass);
                }
                $newPass = $this->getParam('newPassword');
                $instanceSalt = Zend_Registry::get('configGlobal')->password->prefix;
                $hashedPasswordOld = hash($userDao->getHashAlg(), $instanceSalt.$userDao->getSalt().$oldPass);

                if ((!$userDao->isAdmin() && $this->userSession->Dao->isAdmin()) || $this->User->hashExists(
                        $hashedPasswordOld
                    )
                ) {
                    $this->User->changePassword($userDao, $newPass);
                    if (!isset($userId)) {
                        $this->userSession->Dao = $userDao;
                    }
                    echo JsonComponent::encode(array(true, $this->t('Changes saved')));
                    Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_PASSWORD_CHANGED',
                        array('userDao' => $userDao, 'password' => $newPass)
                    );
                } else {
                    echo JsonComponent::encode(array(false, $this->t('The old password is incorrect')));

                    return;
                }
            }

            if (isset($modifyAccount) && $this->logged) {
                $newEmail = trim($this->getParam('email'));
                $firtname = trim($this->getParam('firstname'));
                $lastname = trim($this->getParam('lastname'));
                $company = trim($this->getParam('company'));
                $privacy = $this->getParam('privacy');
                $city = $this->getParam('city');
                $country = $this->getParam('country');
                $website = $this->getParam('website');
                $biography = $this->getParam('biography');

                if (!$accountForm->isValid($this->getRequest()->getPost())) {
                    echo JsonComponent::encode(array(false, 'Invalid form value'));

                    return;
                }

                $userDao = $this->User->load($userDao->getKey());

                if (!isset($privacy) || ($privacy != MIDAS_USER_PRIVATE && $privacy != MIDAS_USER_PUBLIC)) {
                    echo JsonComponent::encode(array(false, 'Error: invalid privacy flag'));

                    return;
                }
                if (!isset($lastname) || !isset($firtname) || empty($lastname) || empty($firtname)) {
                    echo JsonComponent::encode(array(false, 'Error: First and last name required'));

                    return;
                }
                if ($newEmail != $userDao->getEmail()) {
                    $existingUser = $this->User->getByEmail($newEmail);
                    if ($existingUser) {
                        echo JsonComponent::encode(
                            array(false, 'Error: that email address belongs to another account')
                        );

                        return;
                    }
                    $userDao->setEmail($newEmail);
                }
                $userDao->setFirstname($firtname);
                $userDao->setLastname($lastname);
                if (isset($company)) {
                    $userDao->setCompany($company);
                }
                if (isset($city)) {
                    $userDao->setCity($city);
                }
                if (isset($country)) {
                    $userDao->setCountry($country);
                }
                if (isset($website)) {
                    $userDao->setWebsite($website);
                }
                if (isset($biography)) {
                    $userDao->setBiography($biography);
                }
                $userDao->setPrivacy($privacy);
                if ($this->userSession->Dao->isAdmin() && $this->userSession->Dao->getKey() != $userDao->getKey()
                ) {
                    $adminStatus = (bool) $this->getParam('adminStatus');
                    $userDao->setAdmin($adminStatus ? 1 : 0);
                }
                $this->User->save($userDao);
                if (!isset($userId)) {
                    $this->userSession->Dao = $userDao;
                }
                try {
                    Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_USER_SETTINGS_CHANGED',
                        array(
                            'user' => $userDao,
                            'currentUser' => $this->userSession->Dao,
                            'fields' => $this->getAllParams(),
                        )
                    );
                } catch (Exception $e) {
                    echo JsonComponent::encode(array(false, $e->getMessage()));

                    return;
                }
                echo JsonComponent::encode(array(true, $this->t('Changes saved')));
            }
            if (isset($modifyPicture) && $this->logged) {
                if ($this->isTestingEnv()) {
                    // simulate file upload
                    $path = BASE_PATH.'/tests/testfiles/search.png';
                    $size = filesize($path);
                    $mime = 'image/png';
                } else {
                    $mime = $_FILES['file']['type'];
                    $upload = new Zend_File_Transfer();
                    $upload->receive();
                    $path = $upload->getFileName();
                    $size = $upload->getFileSize();
                }

                if (!empty($path) && file_exists($path) && $size > 0) {
                    if (file_exists($path) && $mime == 'image/jpeg') {
                        try {
                            $src = imagecreatefromjpeg($path);
                        } catch (Exception $exc) {
                            echo JsonComponent::encode(array(false, 'Error: Unable to read jpg file'));

                            return;
                        }
                    } elseif (file_exists($path) && $mime == 'image/png') {
                        try {
                            $src = imagecreatefrompng($path);
                        } catch (Exception $exc) {
                            echo JsonComponent::encode(array(false, 'Error: Unable to read png file'));

                            return;
                        }
                    } elseif (file_exists($path) && $mime == 'image/gif') {
                        try {
                            $src = imagecreatefromgif($path);
                        } catch (Exception $exc) {
                            echo JsonComponent::encode(array(false, 'Error: Unable to read gif file'));

                            return;
                        }
                    } else {
                        echo JsonComponent::encode(array(false, 'Error: wrong format'));

                        return;
                    }

                    $tmpPath = $this->getDataDirectory('thumbnail').'/'.$this->Component->Random->generateInt();
                    if (!file_exists($this->getDataDirectory('thumbnail'))) {
                        throw new Zend_Exception(
                            "Thumbnail path does not exist: ".$this->getDataDirectory('thumbnail')
                        );
                    }
                    if (!file_exists($tmpPath)) {
                        mkdir($tmpPath);
                    }
                    $tmpPath .= '/'.$this->Component->Random->generateInt();
                    if (!file_exists($tmpPath)) {
                        mkdir($tmpPath);
                    }
                    $destination = $tmpPath."/".$this->Component->Random->generateInt().'.jpg';
                    while (file_exists($destination)) {
                        $destination = $tmpPath."/".$this->Component->Random->generateInt().'.jpg';
                    }
                    $pathThumbnail = $destination;

                    list ($x, $y) = getimagesize($path);  //--- get size of img ---
                    $thumb = 32;  //--- max. size of thumb ---
                    if ($x > $y) {
                        $tx = $thumb;  //--- landscape ---
                        $ty = round($thumb / $x * $y);
                    } else {
                        $tx = round($thumb / $y * $x);  //--- portrait ---
                        $ty = $thumb;
                    }

                    $thb = imagecreatetruecolor($tx, $ty);  //--- create thumbnail ---
                    imagecopyresampled($thb, $src, 0, 0, 0, 0, $tx, $ty, $x, $y);
                    imagejpeg($thb, $pathThumbnail, 80);
                    imagedestroy($thb);
                    imagedestroy($src);
                    if (file_exists($pathThumbnail)) {
                        $userDao = $this->User->load($userDao->getKey());
                        $oldThumbnail = $userDao->getThumbnail();
                        if (!empty($oldThumbnail) && file_exists(BASE_PATH.'/'.$oldThumbnail)
                        ) {
                            unlink(BASE_PATH.'/'.$oldThumbnail);
                        }
                        $userDao->setThumbnail(substr($pathThumbnail, strlen(BASE_PATH) + 1));
                        $this->User->save($userDao);
                        if (!isset($userId)) {
                            $this->userSession->Dao = $userDao;
                        }
                        echo JsonComponent::encode(
                            array(
                                true,
                                $this->t('Changes saved'),
                                $this->view->webroot.'/'.$userDao->getThumbnail(),
                            )
                        );
                    } else {
                        echo JsonComponent::encode(array(false, 'Error'));

                        return;
                    }
                }
                if (isset($modifyPictureGravatar) && $this->logged) {
                    $gravatarUrl = $this->User->getGravatarUrl($userDao->getEmail());
                    if ($gravatarUrl != false) {
                        $userDao = $this->User->load($userDao->getKey());
                        $oldThumbnail = $userDao->getThumbnail();
                        if (!empty($oldThumbnail) && file_exists(BASE_PATH.'/'.$oldThumbnail)
                        ) {
                            unlink(BASE_PATH.'/'.$oldThumbnail);
                        }
                        $userDao->setThumbnail($gravatarUrl);
                        $this->User->save($userDao);
                        if (!isset($userId)) {
                            $this->userSession->Dao = $userDao;
                        }
                        echo JsonComponent::encode(array(true, $this->t('Changes saved'), $userDao->getThumbnail()));
                    } else {
                        echo JsonComponent::encode(array(false, 'Error'));
                    }
                }
            }
        }

        $communities = array();
        $groups = $userDao->getGroups();
        foreach ($groups as $group) {
            $community = $group->getCommunity();
            if (!isset($communities[$community->getKey()])) {
                $community->groups = array();
                $communities[$community->getKey()] = $community;
            }
            $communities[$community->getKey()]->groups[] = $group;
        }
        $this->Component->Sortdao->field = 'name';
        $this->Component->Sortdao->order = 'asc';
        usort($communities, array($this->Component->Sortdao, 'sortByName'));

        $this->view->useGravatar = Zend_Registry::get('configGlobal')->gravatar;
        $this->view->isGravatar = $this->User->getGravatarUrl($userDao->getEmail());

        $this->view->communities = $communities;
        $this->view->user = $userDao;
        $this->view->currentUser = $this->userSession->Dao;
        $this->view->thumbnail = $userDao->getThumbnail();
        $this->view->jsonSettings = array();
        $this->view->jsonSettings['accountErrorFirstname'] = $this->t('Please set your firstname');
        $this->view->jsonSettings['accountErrorLastname'] = $this->t('Please set your lastname');
        $this->view->jsonSettings['passwordErrorShort'] = $this->t('Password too short');
        $this->view->jsonSettings['passwordErrorMatch'] = $this->t('The passwords are not the same');
        $this->view->jsonSettings = JsonComponent::encode($this->view->jsonSettings);

        $this->view->customTabs = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_CONFIG_TABS',
            array('user' => $userDao)
        );

        $breadcrumbs = array();
        $breadcrumbs[] = array('type' => 'user', 'object' => $userDao);
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => 'My Account',
            'icon' => $this->view->coreWebroot.'/public/images/icons/edit.png',
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);
    }

    /** User page action */
    public function userpageAction()
    {
        $this->view->Date = $this->Component->Date;
        $user_id = $this->getParam("user_id");

        if (!isset($user_id) && !$this->logged) {
            $this->view->header = $this->t(MIDAS_LOGIN_REQUIRED);
            $this->_helper->viewRenderer->setNoRender();

            return false;
        } elseif (!isset($user_id)) {
            $userDao = $this->userSession->Dao;
            $this->view->activemenu = 'myprofile'; // set the active menu
        } else {
            $userDao = $this->User->load($user_id);
            if ($userDao->getPrivacy() == MIDAS_USER_PRIVATE && (!$this->logged || $this->userSession->Dao->getKey(
                    ) != $userDao->getKey()) && (!isset($this->userSession->Dao) || !$this->userSession->Dao->isAdmin())
            ) {
                throw new Zend_Exception("Permission error");
            }
        }

        if (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Unable to find user", 404);
        }

        $this->view->user = $userDao;
        $userCommunities = $this->User->getUserCommunities($userDao);
        $filteredCommunities = array();
        foreach ($userCommunities as $community) {
            if ($this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_READ)
            ) {
                $filteredCommunities[] = $community;
            }
        }

        // If this is the user's own page (or admin user), show any pending community invitations
        if ($this->logged && ($this->userSession->Dao->getKey() == $userDao->getKey(
                ) || $this->userSession->Dao->isAdmin())
        ) {
            $invitations = $userDao->getInvitations();
            $communityInvitations = array();
            foreach ($invitations as $invitation) {
                $community = $this->Community->load($invitation->getCommunityId());
                if ($community) {
                    $communityInvitations[] = $community;
                }
            }
            $this->view->communityInvitations = $communityInvitations;
        }

        $this->view->userCommunities = $filteredCommunities;
        $this->view->folders = array();
        if (!empty($this->userSession->Dao) && ($userDao->getKey() == $this->userSession->Dao->getKey(
                ) || $this->userSession->Dao->isAdmin())
        ) {
            $this->view->ownedItems = $this->Item->getOwnedByUser($userDao);
            $this->view->shareItems = $this->Item->getSharedToUser($userDao);
        } else {
            $this->User->incrementViewCount($userDao);
        }

        $this->view->mainFolder = $userDao->getFolder();
        $this->view->folders = $this->Folder->getChildrenFoldersFiltered(
            $this->view->mainFolder,
            $this->userSession->Dao,
            MIDAS_POLICY_READ
        );
        $this->view->items = $this->Folder->getItemsFiltered(
            $this->view->mainFolder,
            $this->userSession->Dao,
            MIDAS_POLICY_READ
        );
        $this->view->feeds = $this->Feed->getFeedsByUser($this->userSession->Dao, $userDao);

        $this->view->isViewAction = ($this->logged && ($this->userSession->Dao->getKey() == $userDao->getKey(
                ) || $this->userSession->Dao->isAdmin()));
        $this->view->currentUser = $this->userSession->Dao;
        $this->view->isAdmin = $this->logged && $this->userSession->Dao->isAdmin();
        $this->view->information = array();

        $this->view->disableFeedImages = true;
        $this->view->moduleTabs = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_USER_TABS',
            array('user' => $userDao)
        );
        $this->view->moduleActions = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_USER_ACTIONS',
            array('user' => $userDao)
        );
    }

    /** Manage files page action */
    public function manageAction()
    {
        $this->view->Date = $this->Component->Date;
        $userId = $this->getParam('userId');

        if (!isset($userId) && !$this->logged) {
            $this->view->header = $this->t(MIDAS_LOGIN_REQUIRED);
            $this->_helper->viewRenderer->setNoRender();

            return false;
        } elseif (!isset($userId)) {
            $userDao = $this->userSession->Dao;
            $this->view->activemenu = 'user'; // set the active menu
        } else {
            $userDao = $this->User->load($userId);
            if (!$this->userSession->Dao->isAdmin() && $this->userSession->Dao->getKey() != $userId
            ) {
                throw new Zend_Exception("Permission error");
            }
        }

        if (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Unable to find user");
        }

        // Get all the communities this user can see
        if ($userDao->isAdmin()) {
            $communities = $this->Community->getAll();
        } else {
            $communities = $this->Community->getPublicCommunities();
        }
        // Get community folders this user can at least read
        $communityFolders = array();
        foreach ($communities as $communityDao) {
            $tmpfolders = $this->Folder->getChildrenFoldersFiltered(
                $communityDao->getFolder(),
                $userDao,
                MIDAS_POLICY_READ
            );
            $communityID = $communityDao->getKey();
            $communityFolders[$communityID] = $tmpfolders;
        }

        $this->view->user = $userDao;
        $this->view->mainFolder = $userDao->getFolder();
        $this->view->folders = $this->Folder->getChildrenFoldersFiltered(
            $this->view->mainFolder,
            $userDao,
            MIDAS_POLICY_READ
        );
        $this->view->items = $this->Folder->getItemsFiltered($this->view->mainFolder, $userDao, MIDAS_POLICY_READ);
        $this->view->userCommunities = $communities;
        $this->view->userCommunityFolders = $communityFolders;
    }

    /** Render the dialog related to user deletion */
    public function deletedialogAction()
    {
        $this->disableLayout();
        $userId = $this->getParam('userId');

        if (!$this->logged) {
            throw new Zend_Exception('Must be logged in');
        }
        if (!isset($userId)) {
            throw new Zend_Exception('Must set a userId parameter');
        }
        $user = $this->User->load($userId);
        if (!$user) {
            throw new Zend_Exception('Invalid user id');
        }
        if ($this->userSession->Dao->getKey() != $user->getKey()) {
            $this->requireAdminPrivileges();
            $this->view->deleteSelf = false;
        } else {
            $this->view->deleteSelf = true;
        }
        $this->view->user = $user;
    }

    /**
     * When a non-existent user is invited to join a community, they will be sent an email
     * with a link to this action that will allow them to complete registration.
     *
     * @param email The email that the registration was sent to
     * @param authKey The authKey parameter that will be passed on to the submit action
     * @param [firstName] User's first name
     * @param [lastName] User's last name
     * @param [password] User's password
     * @param [password2] User's password retyped
     * @throws Zend_Exception
     */
    public function emailregisterAction()
    {
        $email = $this->getParam('email');
        $authKey = $this->getParam('authKey');

        if (!isset($email) || !isset($authKey)) {
            throw new Zend_Exception('Must pass email and authKey parameters');
        }
        $email = strtolower($email);
        $invitation = $this->NewUserInvitation->getByParams(array('email' => $email, 'auth_key' => $authKey));
        if (!$invitation) {
            throw new Zend_Exception('Invalid email or authKey ('.$email.', '.$authKey.')');
        }

        if ($this->_request->isPost()) {
            $this->disableLayout();
            $this->disableView();
            $firstName = trim($this->getParam('firstName'));
            $lastName = trim($this->getParam('lastName'));
            $password = $this->getParam('password1');
            $password2 = $this->getParam('password2');

            if ($password !== $password2) {
                throw new Zend_Exception('Passwords do not match');
            }
            if (strlen($password) < 3) {
                throw new Zend_Exception('Password must be at least 3 characters');
            }
            if (empty($firstName) || empty($lastName)) {
                throw new Zend_Exception('First name and last name are required');
            }
            if ($this->User->getByEmail($email) !== false) {
                throw new Zend_Exception('User already exists.');
            }
            if (!isset($firstName) || !isset($lastName) || !isset($password)) {
                throw new Zend_Exception('Must pass firstName, lastName, and password parameters');
            }
            if (!headers_sent()) {
                session_start();
            }
            $this->userSession->Dao = $this->User->createUser($email, $password, $firstName, $lastName);
            session_write_close();

            $invitations = $this->NewUserInvitation->getAllByParams(array('email' => $email));
            foreach ($invitations as $invitation) {
                $this->Group->addUser($invitation->getGroup(), $this->userSession->Dao);
                $this->NewUserInvitation->delete($invitation);
            }
            echo JsonComponent::encode(array('status' => 'ok', 'redirect' => $this->view->webroot.'/user/userpage'));
        } else {
            $this->view->email = $email;
            $this->view->authKey = $authKey;
            $this->view->header = 'Accept email invitation';
        }
    }

    /** Delete a user */
    public function deleteAction()
    {
        ignore_user_abort(true);

        if (!$this->logged) {
            throw new Zend_Exception('Must be logged in');
        }
        $userId = $this->getParam('userId');

        if (!isset($userId)) {
            throw new Zend_Exception('Must set a userId parameter');
        }
        $user = $this->User->load($userId);
        if (!$user) {
            throw new Zend_Exception('Invalid user id');
        }
        if ($user->isAdmin()) {
            throw new Zend_Exception('Cannot delete an admin user');
        }

        if ($this->userSession->Dao->getKey() != $user->getKey()) {
            $this->requireAdminPrivileges();
        } else {
            // log out if user is deleting his or her own account
            if (!$this->isTestingEnv()) {
                session_start();
                $this->userSession->Dao = null;
                Zend_Session::ForgetMe();
                $request = $this->getRequest();
                $date = new DateTime();
                $interval = new DateInterval('P1M');
                setcookie(
                    MIDAS_USER_COOKIE_NAME,
                    null,
                    $date->sub($interval)->getTimestamp(),
                    '/',
                    $request->getHttpHost(),
                    (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
                    true
                );
            }
        }
        $this->_helper->viewRenderer->setNoRender();
        $this->disableLayout();

        $name = $user->getFirstname().' '.$user->getLastname();
        $this->User->delete($user);
        $this->getLogger()->debug('User '.$name.' successfully deleted');
        echo JsonComponent::encode(array(true, 'User '.$name.' successfully deleted'));
    }
}
