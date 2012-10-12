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
require_once BASE_PATH . '/modules/api/tests/controllers/ApiCallMethodsTest.php';
/** Tests the functionality of the web API methods */
class ApiCallCommunityMethodsTest extends ApiCallMethodsTest
  {
  /** set up tests */
  public function setUp()
    {
    parent::setUp();
    }

  /** Test creation of a new community */
  public function testCommunityCreate()
    {
    $communityModel = MidasLoader::loadModel('Community');
    $communities = $communityModel->getAll();
    $originalCount = count($communities);

    // Normal user should not be able to create a community
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.community.create';
    $this->params['name'] = 'testNewComm';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY, 'Only admins can create communities');

    $communities = $communityModel->getAll();
    $this->assertEquals(count($communities), $originalCount);

    // Admin should be able to create the community
    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.community.create';
    $this->params['name'] = 'testNewComm';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $communities = $communityModel->getAll();
    $this->assertEquals(count($communities), $originalCount + 1);

    $comm2001 = $communityModel->load('2001');
    $userModel = MidasLoader::loadModel('User');
    $commMember = $userModel->load('4');
    $commModerator = $userModel->load('5');
    $commAdmin = $userModel->load('6');
    $nonModerators = array($commMember);
    $nonAdmins = array($commMember, $commModerator);
    $moderators = array($commModerator, $commAdmin);

    // try to set name as as member, should fail
    foreach($nonModerators as $userDao)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.community.create';
      $this->params['name'] = '2001';
      $this->params['uuid'] = $comm2001->getUuid();
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // try to set name as moderator and admin, should pass
    foreach($moderators as $userDao)
      {
      // get the current community name
      $freshcommunity = $communityModel->load($comm2001->getCommunityId());
      $communityName = $freshcommunity->getName();
      $newcommunityName = $communityName . "suffix";
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.community.create';
      $this->params['name'] = $newcommunityName;
      $this->params['uuid'] = $comm2001->getUuid();
      $resp = $this->_callJsonApi();
      $this->_assertStatusOk($resp);
      $refreshComm = $communityModel->load($comm2001->getCommunityId());
      // ensure that the name was properly updated
      $this->assertEquals($newcommunityName, $refreshComm->getName(), 'Community name should have been changed');
      }

    // try to set privacy as member and moderator, should fail
    foreach($nonAdmins as $userDao)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.community.create';
      $this->params['name'] = '2001';
      $this->params['uuid'] = $comm2001->getUuid();
      $this->params['privacy'] = 'Public';
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // try to set privacy as admin, should work
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = 'midas.community.create';
    $this->params['name'] = '2001';
    $this->params['uuid'] = $comm2001->getUuid();
    $this->params['privacy'] = 'Private';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $comm2001 = $communityModel->load('2001');
    $this->assertEquals($comm2001->getPrivacy(), MIDAS_PRIVACY_PRIVATE, 'Comm2001 should be private');

    // try to set privacy to an invalid string
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = 'midas.community.create';
    $this->params['name'] = '2001';
    $this->params['uuid'] = $comm2001->getUuid();
    $this->params['privacy'] = 'El Duderino';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);
    }

  /** Test listing of visible communities */
  public function testCommunityList()
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.community.list';

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertEquals(count($resp->data), 2);
    $this->assertEquals($resp->data[0]->_model, 'Community');
    $this->assertEquals($resp->data[1]->community_id, 2000);
    $this->assertEquals($resp->data[1]->folder_id, 1003);
    $this->assertEquals($resp->data[1]->publicfolder_id, 1004);
    $this->assertEquals($resp->data[1]->privatefolder_id, 1005);
    $this->assertEquals($resp->data[1]->name, 'Community test User 1');

    //TODO test that a private community is not returned (requires another community in the data set)
    }

  /** Test getting communities by id and name */
  public function testCommunityGet()
    {
    // Test getting a community by id
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.community.get';
    $this->params['id'] = '2000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->name, 'Community test User 1');

    // Test getting a community by name
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.community.get';
    $this->params['name'] = 'Community test User 1';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->community_id, '2000');
    }

  }