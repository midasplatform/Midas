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

/** Handles issuing new tokens and refreshing tokens for a registered client */
class Oauth_TokenController extends Oauth_AppController
{
  public $_models = array('User');
  public $_moduleModels = array('Client', 'Code', 'Token');

  /**
   * The token endpoint.
   * Clients should call this with grant_type=authorization_code to exchange an auth code for an access and refresh token,
   * or call it with grant_type=refresh_token to refresh an expired access token.
   * See http://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-4.1.3
   * @param grant_type Must be set to authorization_code or refresh_token
   * @param [client_id] The id of the client (Only in the case of authorization_code)
   * @param [code] The authorization code obtained by the user for the client (Only in the case of authorization_code)
   * @param [refresh_token] The refresh token (Only when grant_type=refresh_token)
   * @param [client_secret] The client's secret key for authentication. May be passed using Authentication: Basic header also
   */
  function indexAction()
    {
    $this->disableLayout();
    $this->disableView();

    $grantType = $this->_getParam('grant_type');
    $secret = $this->_getParam('client_secret');

    if(!isset($secret))
      {
      $authHeader = $this->getRequest()->getHeader('Authorization');
      if(!$authHeader)
        {
        $this->_doOutput(array('error' => 'invalid_client',
                               'error_description' => 'Must pass client_secret parameter or pass client secret using Authorization header'));
        return;
        }
      list($mode, $secret) = explode(' ', $authHeader);
      if($mode !== 'Basic' || !$secret)
        {
        $this->_doOutput(array('error' => 'invalid_client',
                               'error_description' => 'Must use header form Authorization: Basic <client_secret>'));
        return;
        }
      }

    switch($grantType)
      {
      case 'authorization_code':
        $this->_authorizationCode($secret);
        break;
      case 'refresh_token':
        $this->_refreshToken($secret);
        break;
      default:
        $this->_doOutput(array('error' => 'unsupported_grant_type',
                               'error_description' => 'Use grant type authorization_code or refresh_token'));
        return;
      }
    }

  /**
   * When a user de-authorizes a token, this action is called
   * @param tokenId The id of the token
   */
  function deleteAction()
    {
    $this->disableLayout();
    $this->disableView();

    $tokenId = $this->_getParam('tokenId');
    if(!isset($tokenId))
      {
      throw new Zend_Exception('Must pass a tokenId parameter', 400);
      }
    $token = $this->Oauth_Token->load($tokenId);
    if(!$token)
      {
      throw new Zend_Exception('Invalid tokenId', 404);
      }

    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in', 401);
      }
    if(!$this->userSession->Dao->isAdmin() && $token->getUserId() != $this->userSession->Dao->getKey())
      {
      throw new Zend_Exception('Admin permission required', 403);
      }

    $this->Oauth_Token->delete($token);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Token deleted'));
    }

  /**
   * Client calls this to exchange an authorization code granted by user login for an access and refresh token
   */
  private function _authorizationCode($secret)
    {
    $code = $this->_getParam('code');
    $clientId = $this->_getParam('client_id');

    if(!isset($clientId))
      {
      $this->_doOutput(array('error' => 'invalid_request', 'error_description' => 'Must pass a client_id parameter'));
      return;
      }

    if(!isset($code))
      {
      $this->_doOutput(array('error' => 'invalid_request', 'error_description' => 'Must pass a code parameter'));
      return;
      }

    $clientDao = $this->Oauth_Client->load($clientId);
    if(!$clientDao)
      {
      $this->_doOutput(array('error' => 'invalid_client',
                             'error_description' => 'Invalid client_id'));
      return;
      }
    if($clientDao->getSecret() !== $secret)
      {
      $this->_doOutput(array('error' => 'invalid_client',
                             'error_description' => 'Client authentication failed'));
      return;
      }
    $codeDao = $this->Oauth_Code->getByCode($code);
    if(!$codeDao)
      {
      $this->_doOutput(array('error' => 'invalid_grant',
                             'error_description' => 'Invalid authorization code'));
      return;
      }
    if($codeDao->isExpired())
      {
      $this->_doOutput(array('error' => 'invalid_grant',
                             'error_description' => 'Authorization code has expired'));
      return;
      }
    if($codeDao->getClientId() != $clientDao->getKey())
      {
      $this->_doOutput(array('error' => 'invalid_grant',
                             'error_description' => 'Code does not correspond to this client'));
      return;
      }

    // We should expire any other valid tokens that exist for this user and client
    $this->Oauth_Token->expireTokens($codeDao->getUser(), $clientDao);

    // Pad the time acceptably to leave room for latency delays
    $accessToken = $this->Oauth_Token->createAccessToken($codeDao, '+25 hours');
    $refreshToken = $this->Oauth_Token->createRefreshToken($codeDao);
    $this->Oauth_Code->delete($codeDao);

    $obj = array('token_type' => 'bearer');
    $obj['access_token'] = $accessToken->getToken();
    $obj['refresh_token'] = $refreshToken->getToken();
    $obj['expires_in'] = 3600 * 24; // 24 hour token

    $this->_doOutput($obj);
    $this->getLogger()->info('New tokens issued to client '.$clientId.' for user '.$accessToken->getUserId());
    }

  /**
   * Allows a client to use its refresh token to get a fresh access token
   */
  private function _refreshToken($secret)
    {
    $refreshTokenValue = $this->_getParam('refresh_token');
    $refreshToken = $this->Oauth_Token->getByToken($refreshTokenValue);

    if(!$refreshToken || $refreshToken->getType() != MIDAS_OAUTH_TOKEN_TYPE_REFRESH)
      {
      $this->_doOutput(array('error' => 'invalid_grant',
                             'error_description' => 'Invalid refresh token'));
      return;
      }
    $client = $refreshToken->getClient();
    if(!$client || $client->getSecret() !== $secret)
      {
      $this->_doOutput(array('error' => 'invalid_client',
                             'error_description' => 'Client authentication failed'));
      return;
      }
    // We should expire any other valid tokens that exist for this user and client
    $this->Oauth_Token->expireTokens($refreshToken->getUser(), $client);
    $accessToken = $this->Oauth_Token->createAccessToken($refreshToken, '+25 hours');

    $obj = array('token_type' => 'bearer');
    $obj['access_token'] = $accessToken->getToken();
    $obj['expires_in'] = 3600 * 24; // 24 hour token

    $this->_doOutput($obj);
    $this->getLogger()->info('Access token refreshed to client '.$accessToken->getClientId().' for user '.$accessToken->getUserId());
    }

  /**
   * Helper function for outputting the expected JSON response
   */
  private function _doOutput($array)
    {
    if(!headers_sent())
      {
      header('Content-Type: application/json;charset=UTF-8');
      header('Cache-Control: no-store');
      header('Pragma: no-cache');
      if(array_key_exists('error', $array))
        {
        $this->getResponse()->setHttpResponseCode(400);
        $this->getLogger()->crit('Access token denied ('.$array['error_description'].') '.print_r($this->_getAllParams(), true));
        }
      else
        {
        $this->getResponse()->setHttpResponseCode(200);
        }
      }
    echo JsonComponent::encode($array);
    }
  } // end class
?>
