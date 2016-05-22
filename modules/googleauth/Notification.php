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

/**
 * Notification manager for the googleauth module.
 *
 * @property Googleauth_UserModel $Googleauth_User
 */
class Googleauth_Notification extends MIDAS_Notification
{
    /** @var string */
    public $moduleName = 'googleauth';

    /** @var array */
    public $_models = array('Setting', 'User', 'Userapi');

    /** @var array */
    public $_moduleComponents = array('Cookie');

    /** @var array */
    public $_moduleModels = array('User');

    /** init notification process */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_LOGIN_EXTRA_HTML', 'googleAuthLink');
        $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
        $this->addCallBack('CALLBACK_CORE_USER_COOKIE', 'checkUserCookie');
        $this->addCallBack('CALLBACK_CORE_USER_LOGOUT', 'handleUserLogout');
    }

    /**
     * Constructs the link that is used to initiate a google oauth authentication.
     * This link redirects the user to google so they can approve of the requested
     * oauth scopes, and in turn google will redirect them back to our callback
     * url with an authorization code.
     *
     * @return string
     */
    public function googleAuthLink()
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        $clientId = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_ID_KEY, $this->moduleName);
        $redirectUri = UtilityComponent::getServerURL().$baseUrl.'/'.$this->moduleName.'/callback';
        $additionalScopes = preg_split('/\n|\r/', $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_ADDITIONAL_SCOPES_KEY, $this->moduleName), -1, PREG_SPLIT_NO_EMPTY);

        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $csrf = $randomComponent->generateString(32);

        $client = new Google_Client();
        $client->setAccessType('offline');
        $client->setClientId($clientId);
        $client->setRedirectUri($redirectUri);
        $client->setScopes(array_merge(array('email', 'profile'), $additionalScopes));
        $client->setState($csrf);

        $namespace = new Zend_Session_Namespace('Auth_User');
        $namespace->oauthToken = $csrf;
        session_write_close();

        $tempAuthUrl = $client->createAuthUrl();
        // 'force' needs to be set in combination with 'offline' to get a refresh token.
        $client->setApprovalPrompt('force');
        $permanentAuthUrl = $client->createAuthUrl();

        $authLinksDiv = '<div style="margin-top: 10px; display: inline-block;">Or ';
        $authLinksDiv .= '<span><a class="googleauth-login" style="text-decoration: underline;" href="'.htmlspecialchars($tempAuthUrl, ENT_QUOTES, 'UTF-8').'">'.'Login with your Google account</a></span>';
        $authLinksDiv .= '<span style="padding-left: 15px"><a class="googleauth-login" style="text-decoration: underline;" href="'.htmlspecialchars($permanentAuthUrl, ENT_QUOTES, 'UTF-8').'">'.'Permanently login with your Google account</a></span>';
        $authLinksDiv .= '</div><script type="text/javascript"'.' src="'.UtilityComponent::getServerURL().$baseUrl.'/modules/'.$this->moduleName.'/public/js/login/googleauth.login.js"></script>';
        return $authLinksDiv;
    }

    /**
     * If a user is deleted, we must delete any corresponding google auth user.
     *
     * @param array $args
     */
    public function handleUserDeleted($args)
    {
        $this->Googleauth_User->deleteByUser($args['userDao']);
    }

    /**
     * Check user cookie.
     *
     * @param array $args
     * @return false|UserDao
     * @throws Zend_Exception
     */
    public function checkUserCookie($args)
    {
        $cookie = $args['value'];

        if (strpos($cookie, 'googleauth') === 0) {
            list(, $userId, $apiKey) = preg_split('/:/', $cookie);

            $userDao = $this->User->load($userId);
            if ($userDao === false) {
                return false;
            }

            $userApiDao = $this->Userapi->getByAppAndUser('Default', $userDao);
            if ($userApiDao === false || md5($userApiDao->getApikey()) !== $apiKey) {
                return false;
            }

            /** @var Zend_Controller_Request_Http $request */
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $accessToken = $request->getCookie(GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME, false);

            if ($accessToken !== false) {
                $clientId = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_ID_KEY, $this->moduleName);
                $clientSecret = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_SECRET_KEY, $this->moduleName);

                $client = new Google_Client();
                $client->setAccessToken($accessToken);
                $client->setAccessType('offline');
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);

                if ($client->isAccessTokenExpired()) {
                    $refreshToken = $client->getRefreshToken();
                    if ($refreshToken) {
                        $client->refreshToken($refreshToken);

                        $date = new DateTime();
                        $interval = new DateInterval('P1M');
                        $expires = $date->add($interval);

                        $this->ModuleComponent->Cookie->setAccessTokenCookie($request, $client, $expires);
                    } else {
                        return false;
                    }
                }
            }

            return $userDao;
        }

        return false;
    }

    /**
     * Handle the core CALLBACK_CORE_USER_LOGOUT notification.
     *
     * @param array $args
     */
    public function handleUserLogout($args)
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = Zend_Controller_Front::getInstance()->getRequest();

        $date = new DateTime();
        $interval = new DateInterval('P1M');
        $expires = $date->sub($interval);

        $this->ModuleComponent->Cookie->setAccessTokenCookie($request, false, $expires);
    }
}
