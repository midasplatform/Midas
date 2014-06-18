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

/** notification manager*/
class Googleauth_Notification extends MIDAS_Notification
  {
  public $moduleName = 'googleauth';
  public $_models = array('Setting');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_LOGIN_EXTRA_HTML', 'googleAuthLink');
    }//end init

  /**
   * Constructs the link that is used to initiate a google oauth authentication.
   * This link redirects the user to google so they can approve of the requested
   * oauth scopes, and in turn google will redirect them back to our callback
   * url with an authorization code.
   */
  public function googleAuthLink()
    {
    $clientId = $this->Setting->getValueByName('client_id', $this->moduleName);
    $scheme = (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
    $redirectUri = $scheme.$_SERVER['HTTP_HOST'].
                   Zend_Controller_Front::getInstance()->getBaseUrl().
                   '/'.$this->moduleName.'/callback';
    $scopes = array('profile', 'email');

    $href = 'https://accounts.google.com/o/oauth2/auth?response_type=code'.
            '&client_id='.urlencode($clientId).
            '&redirect_uri='.urlencode($redirectUri).
            '&scope='.urlencode(join(' ', $scopes));

    return '<div style="margin-top: 10px; display: inline-block;">Or '.
           '<a style="text-decoration: underline;" href="'.$href.'">'.
           'Login with your Google account</a></div>';
    }
  } // end class
