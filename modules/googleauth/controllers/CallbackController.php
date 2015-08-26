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

/** Callback controller for the googleauth module */
class Googleauth_CallbackController extends Googleauth_AppController
{
    public $_models = array('Setting', 'User', 'Userapi');
    public $_moduleModels = array('User');

    /**
     * This action gets called into as the OAuth callback after the user
     * successfully authenticates with Google and approves the scope. A code
     * is passed that can be used to make authorized requests later.
     */
    public function indexAction()
    {
        $this->disableLayout();
        $this->disableView();

        $code = $this->getParam('code');
        $state = $this->getParam('state');

        if (strpos($state, ' ') !== false) {
            list($csrfToken, $redirect) = preg_split('/ /', $state);
        } else {
            $redirect = null;
        }

        if (!$code) {
            $error = $this->getParam('error');
            throw new Zend_Exception('Failed to log in with Google OAuth: '.$error);
        }

        $info = $this->_getUserInfo($code);

        $user = $this->_createOrGetUser($info);

        session_start();
        $this->userSession->Dao = $user;

        $userNs = new Zend_Session_Namespace('Auth_User');
        $sessionToken = $userNs->oauthToken;
        session_write_close();

        if ($redirect && $csrfToken === $sessionToken) {
            $this->redirect($redirect);
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Use the authorization code to get an access token, then use that access
     * token to request the user's email and profile info. Returns the necessary
     * user info in an array.
     */
    protected function _getUserInfo($code)
    {
        $clientId = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_ID_KEY, $this->moduleName);
        $clientSecret = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_SECRET_KEY, $this->moduleName);
        $scheme = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
        $redirectUri = $scheme.$_SERVER['HTTP_HOST'].Zend_Controller_Front::getInstance()->getBaseUrl(
            ).'/'.$this->moduleName.'/callback';
        $headers = "Content-Type: application/x-www-form-urlencoded;charset=UTF-8\r\nConnection: Keep-Alive";
        $content = implode(
            '&',
            array(
                'grant_type=authorization_code',
                'code='.$code,
                'client_id='.$clientId,
                'client_secret='.$clientSecret,
                'redirect_uri='.$redirectUri,
            )
        );

        // Make the request for the access token
        if (extension_loaded('curl')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, GOOGLE_AUTH_OAUTH2_URL);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_PORT, 443);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                throw new Zend_Exception('Access token request failed: '.$response);
            }
        } else {
            $context = array('http' => array('method' => 'POST', 'header' => $headers, 'content' => $content));
            $context = stream_context_create($context);
            $response = file_get_contents(GOOGLE_AUTH_OAUTH2_URL, false, $context);

            if ($response === false) {
                throw new Zend_Exception('Access token request failed.');
            }
        }

        $response = json_decode($response);
        $accessToken = $response->access_token;
        $tokenType = $response->token_type;

        // Use the access token to request info about the user
        $headers = 'Authorization: '.$tokenType.' '.$accessToken;

        if (extension_loaded('curl')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, GOOGLE_AUTH_PLUS_URL);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_PORT, 443);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                throw new Zend_Exception('Get Google user info request failed: '.$response);
            }
        } else {
            $context = array('http' => array('header' => $headers));
            $context = stream_context_create($context);
            $response = file_get_contents(GOOGLE_AUTH_PLUS_URL, false, $context);

            if ($response === false) {
                throw new Zend_Exception('Get Google user info request failed.');
            }
        }

        $response = json_decode($response);

        if (isset($response->error)) {
            throw new Zend_Exception('Get Google user info request failed.');
        }

        // Extract the relevant user information from the response.
        return array(
            'googlePersonId' => $response->id,
            'firstName' => $response->name->givenName,
            'lastName' => $response->name->familyName,
            'email' => strtolower($response->emails[0]->value),
        );
    }

    /** Create or return a user */
    protected function _createOrGetUser($info)
    {
        $personId = $info['googlePersonId'];
        $existing = $this->Googleauth_User->getByGooglePersonId($personId);

        if (!$existing) {
            $user = $this->User->getByEmail($info['email']);
            if (!$user) {
                // Only create new user this way if registration is not closed.
                $closeRegistration = (int) $this->Setting->getValueByNameWithDefault('close_registration', 1);
                if ($closeRegistration === 1) {
                    throw new Zend_Exception(
                        'Access to this instance is by invitation '.'only, please contact an administrator.'
                    );
                }
                $user = $this->User->createUser($info['email'], null, $info['firstName'], $info['lastName'], 0, '');
            } else {
                $user->setFirstname($info['firstName']);
                $user->setLastname($info['lastName']);
                $this->User->save($user);
            }

            $this->Googleauth_User->createGoogleUser($user, $personId);
        } else {
            $user = $this->User->load($existing->getUserId());
            $user->setFirstname($info['firstName']);
            $user->setLastname($info['lastName']);
            $this->User->save($user);
        }

        $userapi = $this->Userapi->getByAppAndUser('Default', $user);
        $request = $this->getRequest();
        $date = new DateTime();
        $interval = new DateInterval('P1M');
        setcookie(
            MIDAS_USER_COOKIE_NAME,
            'googleauth:'.$user->getKey().':'.md5($userapi->getApikey()),
            $date->add($interval)->getTimestamp(),
            '/',
            $request->getHttpHost(),
            (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 1) === 1,
            true
        );

        return $user;
    }
}
