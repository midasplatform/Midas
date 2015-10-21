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
 * Callback controller for the googleauth module.
 *
 * @property Googleauth_UserModel $Googleauth_User
 */
class Googleauth_CallbackController extends Googleauth_AppController
{
    /** @var array */
    public $_models = array('Setting', 'User', 'Userapi');

    /** @var array */
    public $_moduleComponents = array('Cookie');

    /** @var array */
    public $_moduleModels = array('User');

    /**
     * This action gets called into as the OAuth callback after the user
     * successfully authenticates with Google and approves the scope. A code
     * is passed that can be used to make authorized requests later.
     */
    public function indexAction()
    {
        /** @var string $state */
        $state = $this->getParam('state');

        if (strpos($state, ' ') !== false) {
            list($csrf, $url) = preg_split('/ /', $state);
        } else {
            $csrf = false;
            $url = false;
        }

        $clientId = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_ID_KEY, $this->moduleName);
        $clientSecret = $this->Setting->getValueByName(GOOGLE_AUTH_CLIENT_SECRET_KEY, $this->moduleName);
        $redirectUri = UtilityComponent::getServerURL().$this->getFrontController()->getBaseUrl().'/'.$this->moduleName.'/callback';

        $client = new Google_Client();
        $client->setAccessType('offline');
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        /** @var string $code */
        $code = $this->getParam('code');
        $client->authenticate($code);
        $userDao = $this->_createOrGetUser($client);

        session_start();
        $this->userSession->Dao = $userDao;

        $namespace = new Zend_Session_Namespace('Auth_User');
        $token = $namespace->oauthToken;
        session_write_close();

        $this->disableLayout();
        $this->disableView();

        if ($url !== false && $csrf === $token) {
            $this->redirect($url);
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Create or return a user.
     *
     * @param Google_Client $client
     * @return false|UserDao
     * @throws Zend_Exception
     */
    protected function _createOrGetUser($client)
    {
        $plus = new Google_Service_Plus($client);

        /** @var Google_Service_Plus_Person $person */
        $me = $plus->people->get('me');
        $personId = $me['id'];
        $googleAuthUserDao = $this->Googleauth_User->getByGooglePersonId($personId);
        $givenName = $me['name']['givenName'];
        $familyName = $me['name']['familyName'];
        $email = strtolower($me['emails'][0]['value']);

        if ($googleAuthUserDao === false) {
            $userDao = $this->User->getByEmail($email);
            if ($userDao === false) {
                // Only create new user this way if registration is not closed.
                $closeRegistration = (int) $this->Setting->getValueByNameWithDefault('close_registration', 1);
                if ($closeRegistration === 1) {
                    throw new Zend_Exception(
                        'Access to this instance is by invitation only, please contact an administrator.'
                    );
                }
                $userDao = $this->User->createUser($email, null, $givenName, $familyName, 0, '');
            } else {
                $userDao->setFirstname($givenName);
                $userDao->setLastname($familyName);
                $this->User->save($userDao);
            }
            $this->Googleauth_User->createGoogleUser($userDao, $personId);
        } else {
            $userDao = $this->User->load($googleAuthUserDao->getUserId());
            $userDao->setFirstname($givenName);
            $userDao->setLastname($familyName);
            $this->User->save($userDao);
        }

        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();

        $date = new DateTime();
        $interval = new DateInterval('P1M');
        $expires = $date->add($interval);

        $this->ModuleComponent->Cookie->setUserCookie($request, $userDao, $expires);
        $this->ModuleComponent->Cookie->setAccessTokenCookie($request, $client, $expires);

        return $userDao;
    }
}
