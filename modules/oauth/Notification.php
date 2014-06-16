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
class Oauth_Notification extends MIDAS_Notification
  {
  public $moduleName = 'oauth';
  public $_models = array('User');
  public $_moduleModels = array('Code', 'Token');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_API_AUTH_INTERCEPT', 'handleAuth');
    $this->addCallBack('CALLBACK_API_REQUIRE_PERMISSIONS', 'requirePermissions');
    $this->addCallBack('CALLBACK_CORE_GET_CONFIG_TABS', 'getUserTabs');

    $this->addTask('TASK_CLEANUP_PERFORM_CLEANUP', 'cleanExpired', 'Delete expired codes and tokens');
    }//end init

  /**
   * Remove expired auth codes and access tokens from the database
   */
  public function cleanExpired()
    {
    $this->Oauth_Code->cleanExpired();
    $this->Oauth_Token->cleanExpired();
    }

  /**
   * Set the required permissions in global registry for use later
   */
  public function requirePermissions($params)
    {
    Zend_Registry::set('oauthRequiredScopes', $params['scopes']);
    }

  /** Handle web API authentication with an OAuth token */
  public function handleAuth($params)
    {
    $apiArgs = $params['args'];
    if(array_key_exists('oauth_token', $apiArgs))
      {
      if(Zend_Registry::isRegistered('oauthRequiredScopes'))
        {
        $requiredScopes = Zend_Registry::get('oauthRequiredScopes');
        }
      else
        {
        $requiredScopes = array(MIDAS_API_PERMISSION_SCOPE_ALL);
        }

      $tokenDao = $this->Oauth_Token->getByToken($apiArgs['oauth_token']);
      if(!$tokenDao || $tokenDao->getType() != MIDAS_OAUTH_TOKEN_TYPE_ACCESS)
        {
        throw new Zend_Exception('Invalid OAuth access token', 400);
        }
      if($tokenDao->isExpired())
        {
        throw new Zend_Exception('Token has expired', 403);
        }
      $grantedScopes = JsonComponent::decode($tokenDao->getScopes());
      if(!in_array(MIDAS_API_PERMISSION_SCOPE_ALL, $grantedScopes))
        {
        foreach($requiredScopes as $requiredScope)
          {
          if(!in_array($requiredScope, $grantedScopes))
            {
            return array('userDao' => null); // Missing required scope, let caller determine permission failure
            }
          }
        }
      return array('userDao' => $tokenDao->getUser());
      }
    else
      {
      return false; // fall through to normal authentication
      }
    }

  /** User account tabs */
  public function getUserTabs($params)
    {
    $user = $params['user'];
    $fc = Zend_Controller_Front::getInstance();
    $moduleWebroot = $fc->getBaseUrl().'/'.$this->moduleName;
    return array('OAuth' => $moduleWebroot.'/client?userId='.$user->getKey());
    }
  } // end class
