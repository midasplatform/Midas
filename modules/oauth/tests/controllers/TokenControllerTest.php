<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** test oauth token controller */
class OauthTokenControllerTest extends ControllerTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'oauth');
    $this->enabledModules = array('api', 'oauth');
    $this->_models = array('User');
    $this->_components = array('Json');

    parent::setUp();
    }

  /**
   * Helper function for asserting error responses from the token endpoint
   * @param errorName The error name as specified in the IETF spec draft
   */
  private function _assertErrorResponse($errorName)
    {
    $json = JsonComponent::decode($this->getBody());
    $this->assertEquals($json['error'], $errorName);
    }

  /**
   * This tests the token endpoint, used by clients for
   * 1. Exchanging an authorization code for access/refresh tokens
   * 2. Using a refresh token to get a new access token
   */
  public function testIndexAction()
    {
    $clientModel = MidasLoader::loadModel('Client', 'oauth');
    $codeModel = MidasLoader::loadModel('Code', 'oauth');

    // I. Test exchanging a code for tokens
    $client = $clientModel->load(1000);
    $user = $this->User->load(1);
    $codeDao = $codeModel->create($user, $client, array(1, 2, 3));
    $otherClient = $clientModel->create($user, 'other client');

    // 1. Test failure conditions
    // a. No secret passed
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_client');
    $this->resetAll();
    // b. Correct secret passed, but missing grant type
    $this->params['client_secret'] = $client->getSecret();
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('unsupported_grant_type');
    $this->resetAll();
    // c. Missing client_id
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'authorization_code';
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_request');
    $this->resetAll();
    // d. Missing code
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $client->getKey();
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_request');
    $this->resetAll();
    // e. Incorrect secret passed
    $this->params['client_secret'] = 'wrong';
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $client->getKey();
    $this->params['code'] = $codeDao->getCode();
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_client');
    $this->resetAll();
    // f. Incorrect code
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $client->getKey();
    $this->params['code'] = 'wrong';
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_grant');
    $this->resetAll();
    // g. Expired code
    $codeDao->setExpirationDate(date("Y-m-d H:i:s", strtotime('-1 hour')));
    $codeModel->save($codeDao);
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $client->getKey();
    $this->params['code'] = $codeDao->getCode();
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_grant');
    $codeDao->setExpirationDate(date("Y-m-d H:i:s", strtotime('+1 hour')));
    $codeModel->save($codeDao);
    $this->resetAll();
    // h. Client id mismatch
    $this->params['client_secret'] = $otherClient->getSecret();
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $otherClient->getKey();
    $this->params['code'] = $codeDao->getCode();
    $this->dispatchUri('/oauth/token');
    $this->assertNotEquals($otherClient->getKey(), $client->getKey());
    $this->_assertErrorResponse('invalid_grant');
    $this->resetAll();

    // 2. Test success conditions
    // a. With secret passed in Authorization header
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $client->getKey();
    $this->params['code'] = $codeDao->getCode();
    $this->dispatchUri('/oauth/token');
    $json = JsonComponent::decode($this->getBody());
    $this->assertEquals($json['token_type'], 'bearer');
    $this->assertNotEmpty($json['access_token']);
    $this->assertNotEmpty($json['refresh_token']);
    $this->assertNotEmpty($json['expires_in']);
    $this->assertTrue(is_numeric($json['expires_in']));
    $this->resetAll();
    // b. With secret passed in params
    $codeDao = $codeModel->create($user, $client, array(1, 2, 3)); // have to re-create the code since last call deleted it
    $this->getRequest()->setHeader('Authorization', 'Basic '.$client->getSecret());
    $this->params['grant_type'] = 'authorization_code';
    $this->params['client_id'] = $client->getKey();
    $this->params['code'] = $codeDao->getCode();
    $this->dispatchUri('/oauth/token');
    $json = JsonComponent::decode($this->getBody());
    $this->assertEquals($json['token_type'], 'bearer');
    $this->assertNotEmpty($json['access_token']);
    $this->assertNotEmpty($json['refresh_token']);
    $this->assertNotEmpty($json['expires_in']);
    $this->assertTrue(is_numeric($json['expires_in']));
    $this->resetAll();

    // II. Test refreshing an access token with a refresh token
    $refreshToken = $json['refresh_token'];
    $accessToken = $json['access_token'];
    // 1. Test failure conditions
    // a. Trying to refresh using an access token
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'refresh_token';
    $this->params['refresh_token'] = $accessToken;
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_grant');
    $this->resetAll();
    // b. Trying to refresh as wrong client
    $this->params['client_secret'] = $otherClient->getSecret();
    $this->params['grant_type'] = 'refresh_token';
    $this->params['refresh_token'] = $refreshToken;
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_client');
    $this->resetAll();
    // c. Trying to refresh using something that isn't a token at all
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'refresh_token';
    $this->params['refresh_token'] = 'not_a_real_token';
    $this->dispatchUri('/oauth/token');
    $this->_assertErrorResponse('invalid_grant');
    $this->resetAll();

    // 2. Test success conditions
    $this->params['client_secret'] = $client->getSecret();
    $this->params['grant_type'] = 'refresh_token';
    $this->params['refresh_token'] = $refreshToken;
    $this->dispatchUri('/oauth/token');
    $json = JsonComponent::decode($this->getBody());
    $this->assertEquals($json['token_type'], 'bearer');
    $this->assertNotEmpty($json['access_token']);
    $this->assertNotEmpty($json['expires_in']);
    $this->assertTrue(is_numeric($json['expires_in']));
    $this->resetAll();
    }

  /**
   * Test actually using access tokens to authenticate when calling API methods
   */
  public function testApiAccess()
    {
    $adminUser = $this->User->load(3);
    $clientModel = MidasLoader::loadModel('Client', 'oauth');
    $codeModel = MidasLoader::loadModel('Code', 'oauth');
    $tokenModel = MidasLoader::loadModel('Token', 'oauth');
    $client = $clientModel->load(1000);
    $codeDao = $codeModel->create($adminUser, $client, array(1, 2, 3));

    // Create an expired access token
    $accessToken = $tokenModel->createAccessToken($codeDao, '-1 hour');

    // Calling community.create without authentication should fail since admin is required
    $uri = '/api/json?method=midas.community.create&name=hello';
    $this->dispatchUri($uri);
    $this->_assertApiFailure();
    $this->resetAll();

    // Calling with an expired token should fail
    $uri .= '&oauth_token='.urlencode($accessToken->getToken());
    $this->dispatchUri($uri);
    $this->_assertApiFailure();
    $this->resetAll();

    // Test with valid token but incorrect scope
    $accessToken->setExpirationDate(date("Y-m-d H:i:s", strtotime('+1 hour')));
    $tokenModel->save($accessToken);
    $this->dispatchUri($uri);
    $this->_assertApiFailure();
    $this->resetAll();

    // Set scope to ALL; should now work
    $accessToken->setScopes(JsonComponent::encode(array(0)));
    $tokenModel->save($accessToken);
    $this->dispatchUri($uri);
    $json = JsonComponent::decode($this->getBody());
    $this->assertEquals($json['stat'], 'ok');
    $this->assertEquals($json['code'], 0);
    $this->assertNotEmpty($json['data']);
    $this->assertNotEmpty($json['data']['community_id']);
    $this->assertEquals($json['data']['name'], 'Hello');
    }

  /**
   * Helper method to make sure that a web api call failed
   */
  private function _assertApiFailure()
    {
    $json = JsonComponent::decode($this->getBody());
    $this->assertEquals($json['stat'], 'fail');
    $this->assertTrue($json['code'] != 0);
    }

  /**
   * Test deletion of tokens (done via the OAuth user settings tab)
   */
  public function testDeauthorize()
    {
    $adminUser = $this->User->load(3);
    $normalUser = $this->User->load(2);
    $clientModel = MidasLoader::loadModel('Client', 'oauth');
    $codeModel = MidasLoader::loadModel('Code', 'oauth');
    $tokenModel = MidasLoader::loadModel('Token', 'oauth');
    $client = $clientModel->load(1000);
    $codeDao = $codeModel->create($adminUser, $client, array(1, 2, 3));

    $accessToken = $tokenModel->createAccessToken($codeDao, '+1 hour');
    $refreshToken = $tokenModel->createRefreshToken($codeDao);

    // 1. Test failure conditions
    // a. Missing tokenId parameter
    $this->dispatchUri('/oauth/token/delete', null, true);
    $this->resetAll();
    // b. Invalid tokenId parameter
    $this->dispatchUri('/oauth/token/delete?tokenId=895698', null, true, false);
    $this->resetAll();
    // c. Not logged in
    $this->dispatchUri('/oauth/token/delete?tokenId='.$accessToken->getKey(), null, true);
    $this->resetAll();
    // d. Non-admin user attempting to delete another user's token
    $this->dispatchUri('/oauth/token/delete?tokenId='.$accessToken->getKey(), $normalUser, true);
    $this->resetAll();

    // 2. Test success conditions
    // a. Deleting access token
    $this->dispatchUri('/oauth/token/delete?tokenId='.$accessToken->getKey(), $adminUser);
    $this->resetAll();
    // b. Deleting refresh token
    $this->dispatchUri('/oauth/token/delete?tokenId='.$refreshToken->getKey(), $adminUser);
    $this->resetAll();
    }
}
