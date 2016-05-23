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
        $rememberedAuthUrl = $client->createAuthUrl();

        $authLinksDiv = '<div style="margin-top: 10px; display: inline-block;">Or ';
        $authLinksDiv .= '<span><a class="googleauth-login" style="text-decoration: underline;" href="'.htmlspecialchars($tempAuthUrl, ENT_QUOTES, 'UTF-8').'">'.'Login with your Google account</a></span>';
        // We say "one month", see the note in checkUserCookie for more detail.
        $authLinksDiv .= '<span style="padding-left: 15px"><a class="googleauth-login" style="text-decoration: underline;" href="'.htmlspecialchars($rememberedAuthUrl, ENT_QUOTES, 'UTF-8').'">'.'Login with your Google account for one month</a></span>';
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
        // This function (checkUserCookie) should only be called when the current
        // PHP Midas session has expired, and the MIDAS_USER_COOKIE_NAME cookie
        // has not expired.  It should only return a valid Midas userDao if all
        // of the following conditions are true:
        // (1) The MIDAS_USER_COOKIE_NAME contains Googleauth info for a valid user
        // (2) The GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME has not expired
        // (3) The GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME contains a valid Googleauth access token
        // (4) Either the access token has not expired or the access token could be renewed
        //
        // How long will the user stay logged in to Midas with a valid Googleauth?
        //
        // Note that this is slightly separate from how long the user will stay
        // logged into Midas. The user could have a valid Midas login but
        // that could be attached to a currently invalid Google login, though
        // in most cases they will be the same.
        //
        // This is determined by an interaction of the following factors:
        //
        // (1) Duration of the PHP session.  The PHP session used in Googleauth
        // is started by CallbackController.indexAction, which does not set
        // an expiration time, so uses the default PHP session timeout which
        // comes from php.ini .  This function (checkUserCookie) is invoked by
        // AppController.preDispatch, only when $user->Dao is null, which can
        // happen when the PHP session has timed out.  AppController.preDispatch
        // will have set an expiration time on the Zend_Session_Namespace object
        // by that point, and the Zend_Session_Namespace object interacts with
        // the PHP session and likely overrides the PHP session's expiration time.
        //
        // (2) Expiry time on the MIDAS_USER_COOKIE_NAME.  This expiry time
        // is first set when the user is logged in via Googleauth by
        // CallbackController._createOrGetUser, to be one month from the login
        // auth callback time.  This function (checkUserCookie) will not be
        // called later than one month from this original time, as it is only
        // invoked when the MIDAS_USER_COOKIE_NAME cookie has not expired.
        //
        // (3) Expiry time on the GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME.  This
        // expiry time is first set when the user is logged in via Googleauth by
        // CallbackController._createOrGetUser, to be one month from the login
        // auth callback time.  When this function (checkUserCookie) is invoked,
        // it can renew the PHP session only if this cookie has not expired.
        // When this function (checkUserCookie) renews the Google access token,
        // the GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME cookie expiry time is set
        // one month into the future.
        //
        // (4) Googleauth access token expires_in value.  This is the value of
        // the expires_in field held within the GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME
        // cookie.  This value is provided by Google to the CallbackController.indexAction,
        // and has been observed to be 3600 seconds (1 hour).  This is the length
        // of time from when the Google access token was obtained or refreshed that
        // the access token will be valid, and is used to determine the result of
        // the below $client->isAccessTokenExpired() value, i.e., abstractly
        // isAccessTokenExpired = now < access_token.created + access_token.expires_in
        //
        // (5) Presence of a refresh token in the access token due to the original
        // Googleauth login params, determined by the function googleAuthLink above.
        // When the user is first authenticated through Googleauth, if a refresh token
        // is returned in the access token, then that access token can be refreshed
        // from Google.  If the access token is refreshed in this function (checkUserCookie),
        // the access token created will be set to the current time and the
        // access token expires_in will be set to 1 hour in the future.  The cookie
        // holding the access token, GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME, will
        // have its expiry time set one month into the future.
        //
        // (6) Google renewal policy for access tokens.  Access tokens obtained
        // with a refresh token appear to be indefinitely refreshable
        // on their own, though this is mitigated by the Google account settings,
        // but the refresh token mechanism does not appear to set any limit
        // on the number of refreshes.
        //
        // (7) Google account settings.  Currently this seems to expire a Google
        // account login every 30 days, and the Google account can also be logged
        // out of. As the Googleauth is tied to a Google account, that Googleauth account
        // will likely be invalid, even if the Midas session remains valid and logged in.
        // If a user attempts to login to Midas via Googleauth for a logged out
        // Google account, they will be prompted to login to their Google account
        // first.  It is unclear whether a currently invalid/logged out
        // Google account will allow an access token to be refreshed via oauth.
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

                return $userDao;
            }
            // No point in returning a valid Midas session when the Googleauth
            // session is inaccessible or invalid.
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
