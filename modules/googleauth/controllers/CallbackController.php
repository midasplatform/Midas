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

class Googleauth_CallbackController extends Googleauth_AppController
{
  public $_models = array('Setting', 'User', 'Userapi');
  public $_moduleModels = array('User');

  /**
   * This action gets called into as the OAuth callback after the user
   * successfully authenticates with Google and approves the scope. A code
   * is passed that can be used to make authorized requests later.
   */
  function indexAction()
    {
    $this->disableLayout();
    $this->disableView();

    $code = $this->getParam('code');
    $state = $this->getParam('state');

    if(strpos($state, ' ') !== false)
      {
      list($csrfToken, $redirect) = preg_split('/ /', $state);
      }
    else
      {
      $redirect = null;
      }

    if(!$code)
      {
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

    if($redirect && $csrfToken === $sessionToken)
      {
      $this->redirect($redirect);
      }
    else
      {
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
    $clientId = $this->Setting->getValueByName('client_id', $this->moduleName);
    $clientSecret = $this->Setting->getValueByName('client_secret', $this->moduleName);
    $scheme = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS']) ?
        'https://' : 'http://';
    $redirectUri = $scheme.$_SERVER['HTTP_HOST'].
                   Zend_Controller_Front::getInstance()->getBaseUrl().
                   '/'.$this->moduleName.'/callback';
    $headers = array(
      'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
      'Connection: Keep-Alive'
    );
    $postData = join('&', array(
      'grant_type=authorization_code',
      'code='.$code,
      'client_id='.$clientId,
      'client_secret='.$clientSecret,
      'redirect_uri='.$redirectUri
    ));

    // Make the request for the access token
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_PORT, 443);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($curl);

    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($httpStatus != 200)
      {
      throw new Zend_Exception('Access token request failed: '.$resp);
      }

    $resp = json_decode($resp);
    $accessToken = $resp->access_token;
    $tokenType = $resp->token_type;

    // Use the access token to request info about the user
    $headers = array(
      'Authorization: '.$tokenType.' '.$accessToken
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/plus/v1/people/me');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_PORT, 443);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($httpStatus != 200)
      {
      throw new Zend_Exception('Get Google user info request failed: '.$resp);
      }
    $resp = json_decode($resp);

    // Extract the relevant user information from the response.
    return array(
      'googlePersonId' => $resp->id,
      'firstName' => $resp->name->givenName,
      'lastName' => $resp->name->familyName,
      'email' => strtolower($resp->emails[0]->value));
    }

  protected function _createOrGetUser($info)
    {
    $personId = $info['googlePersonId'];
    $existing = $this->Googleauth_User->getByGooglePersonId($personId);

    if (!$existing)
      {
      $user = $this->User->getByEmail($info['email']);
      if(!$user)
        {
        // Only create new user this way if registration is not closed.
        if(isset(Zend_Registry::get('configGlobal')->closeregistration) &&
           Zend_Registry::get('configGlobal')->closeregistration == "1")
          {
          throw new Zend_Exception('Access to this instance is by invitation '.
                                   'only, please contact an administrator.');
          }
        $user = $this->User->createUser(
          $info['email'], null, $info['firstName'], $info['lastName'], 0, '');
        }
      else
        {
        $user->setFirstname($info['firstName']);
        $user->setLastname($info['lastName']);
        $this->User->save($user);
        }

      $this->Googleauth_User->createGoogleUser($user, $personId);
      }
    else
      {
      $user = $this->User->load($existing->getUserId());
      $user->setFirstname($info['firstName']);
      $user->setLastname($info['lastName']);
      $this->User->save($user);
      }

    $userapi = $this->Userapi->getByAppAndUser('Default', $user);
    setcookie('midasUtil','googleauth:'.$user->getKey().':'.md5($userapi->getApikey()), time() + 60 * 60 * 24 * 30, '/');
    return $user;
    }
}
