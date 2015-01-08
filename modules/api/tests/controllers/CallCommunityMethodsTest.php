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

require_once BASE_PATH.'/modules/api/tests/controllers/CallMethodsTestCase.php';

/** Tests the functionality of the web API methods */
class Api_CallCommunityMethodsTest extends Api_CallMethodsTestCase
{
    /** Test creation of a new community */
    public function testCommunityCreate()
    {
        /** @var CommunityModel $communityModel */
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
        // check default privacy is Public
        $createdComm = $communityModel->load($resp->data->community_id);
        $this->assertEquals(
            $createdComm->getPrivacy(),
            MIDAS_COMMUNITY_PUBLIC,
            'created community has wrong default privacy'
        );

        // Attempt to create a duplicate community
        $this->resetAll();
        $this->params['token'] = $this->_loginAsAdministrator();
        $this->params['method'] = 'midas.community.create';
        $this->params['name'] = 'testNewComm';
        $resp = $this->_callJsonApi();
        $this->_assertStatusFail($resp, 0);

        // create a comm with privacy Public
        $this->resetAll();
        $this->params['token'] = $this->_loginAsAdministrator();
        $this->params['method'] = 'midas.community.create';
        $this->params['name'] = 'testNewCommPublic';
        $this->params['privacy'] = 'Public';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $createdComm = $communityModel->load($resp->data->community_id);
        $this->assertEquals(
            $createdComm->getPrivacy(),
            MIDAS_COMMUNITY_PUBLIC,
            'created community has wrong default privacy'
        );

        // create a comm with privacy Private
        $this->resetAll();
        $this->params['token'] = $this->_loginAsAdministrator();
        $this->params['method'] = 'midas.community.create';
        $this->params['name'] = 'testNewCommPrivate';
        $this->params['privacy'] = 'Private';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $createdComm = $communityModel->load($resp->data->community_id);
        $this->assertEquals(
            $createdComm->getPrivacy(),
            MIDAS_COMMUNITY_PRIVATE,
            'created community has wrong default privacy'
        );

        $comm2001 = $communityModel->load('2001');

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $commMember = $userModel->load('4');
        $commModerator = $userModel->load('5');
        $commAdmin = $userModel->load('6');
        $nonModerators = array($commMember);
        $nonAdmins = array($commMember, $commModerator);
        $moderators = array($commModerator, $commAdmin);

        // try to set name as as member, should fail
        foreach ($nonModerators as $userDao) {
            $this->resetAll();
            $this->params['token'] = $this->_loginAsUser($userDao);
            $this->params['method'] = 'midas.community.create';
            $this->params['name'] = '2001';
            $this->params['uuid'] = $comm2001->getUuid();
            $resp = $this->_callJsonApi();
            $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
        }

        // try to set name as moderator and admin, should pass
        foreach ($moderators as $userDao) {
            // get the current community name
            $freshcommunity = $communityModel->load($comm2001->getCommunityId());
            $communityName = $freshcommunity->getName();
            $newcommunityName = $communityName."suffix";
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

        // try to set privacy as a nonAdmin, should fail
        foreach ($nonAdmins as $userDao) {
            $this->resetAll();
            $this->params['token'] = $this->_loginAsUser($userDao);
            $this->params['method'] = 'midas.community.create';
            $this->params['name'] = '2001';
            $this->params['uuid'] = $comm2001->getUuid();
            $this->params['privacy'] = 'Public';
            $resp = $this->_callJsonApi();
            $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
        }

        // try to set privacy to an invalid string
        $this->resetAll();
        $this->params['token'] = $this->_loginAsUser($commAdmin);
        $this->params['method'] = 'midas.community.create';
        $this->params['name'] = '2001';
        $this->params['uuid'] = $comm2001->getUuid();
        $this->params['privacy'] = 'El Duderino';
        $resp = $this->_callJsonApi();
        $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

        // want to test changing privacy using this api method on an extant community
        // test cases   Public -> Public
        //              Public -> Private
        //              Private -> Private
        //              Private -> Public
        $privacyStatuses = array(MIDAS_COMMUNITY_PUBLIC, MIDAS_COMMUNITY_PRIVATE);
        $privacyStrings = array(MIDAS_COMMUNITY_PUBLIC => "Public", MIDAS_COMMUNITY_PRIVATE => "Private");
        foreach ($privacyStatuses as $initialStatus) {
            foreach ($privacyStatuses as $finalStatus) {
                // initialize privacy
                $comm2001->setPrivacy($initialStatus);
                $communityModel->save($comm2001);

                // try to set privacy with admin, should pass
                $this->resetAll();
                $this->params['token'] = $this->_loginAsUser($commAdmin);
                $this->params['method'] = 'midas.community.create';
                $this->params['name'] = '2001';
                $this->params['uuid'] = $comm2001->getUuid();
                $this->params['privacy'] = $privacyStrings[$finalStatus];
                $resp = $this->_callJsonApi();
                $this->_assertStatusOk($resp);

                $comm2001 = $communityModel->load($comm2001->getCommunityId());
                $this->assertEquals($comm2001->getPrivacy(), $finalStatus, 'changed community has wrong privacy value');
            }
        }

        // try to set canjoin as a nonAdmin, should fail
        foreach ($nonAdmins as $userDao) {
            $this->resetAll();
            $this->params['token'] = $this->_loginAsUser($userDao);
            $this->params['method'] = 'midas.community.create';
            $this->params['name'] = '2001';
            $this->params['uuid'] = $comm2001->getUuid();
            $this->params['canjoin'] = 'Everyone';
            $resp = $this->_callJsonApi();
            $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
        }

        // try to set canjoin to an invalid string
        $this->resetAll();
        $this->params['token'] = $this->_loginAsUser($commAdmin);
        $this->params['method'] = 'midas.community.create';
        $this->params['name'] = '2001';
        $this->params['uuid'] = $comm2001->getUuid();
        $this->params['canjoin'] = 'Some of the people Some of the time';
        $resp = $this->_callJsonApi();
        $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

        // want to test changing canjoin using this api method on an extant community
        // test cases   Everyone -> Everyone
        //              Everyone -> Invitation
        //              Invitation -> Invitation
        //              Invitation -> Everyone
        $canjoinStatuses = array(MIDAS_COMMUNITY_CAN_JOIN, MIDAS_COMMUNITY_INVITATION_ONLY);
        $canjoinStrings = array(
            MIDAS_COMMUNITY_CAN_JOIN => "Everyone",
            MIDAS_COMMUNITY_INVITATION_ONLY => "Invitation",
        );
        foreach ($canjoinStatuses as $initialStatus) {
            foreach ($canjoinStatuses as $finalStatus) {
                // initialize privacy
                $comm2001->setCanJoin($initialStatus);
                $communityModel->save($comm2001);

                // try to set privacy with admin, should pass
                $this->resetAll();
                $this->params['token'] = $this->_loginAsUser($commAdmin);
                $this->params['method'] = 'midas.community.create';
                $this->params['name'] = '2001';
                $this->params['uuid'] = $comm2001->getUuid();
                $this->params['canjoin'] = $canjoinStrings[$finalStatus];
                $resp = $this->_callJsonApi();
                $this->_assertStatusOk($resp);

                $comm2001 = $communityModel->load($comm2001->getCommunityId());
                $this->assertEquals($comm2001->getCanJoin(), $finalStatus, 'changed community has wrong canjoin value');
            }
        }
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
        $this->assertEquals($resp->data[1]->community_id, 2000);
        $this->assertEquals($resp->data[1]->folder_id, 1003);
        $this->assertEquals($resp->data[1]->name, 'Community test User 1');

        // TODO test that a private community is not returned (requires another community in the data set)
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

    /** Test listing the groups in a community */
    public function testCommunityListGroups()
    {
        $validCommunityId = 2001;
        $invalidCommunityId = -10;

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $commMemberId = '4';
        $commModeratorId = '5';
        $commAdminId = '6';
        $commMember = $userModel->load($commMemberId);
        $commModerator = $userModel->load($commModeratorId);
        $commAdmin = $userModel->load($commAdminId);

        // add in an anonymous user to non admins
        $invalidUsers = array($commMember, $commModerator, false);

        // community list groups

        $communityListMethod = "midas.community.list.groups";
        $requiredParams = array(
            array(
                'name' => 'community_id',
                'valid' => $validCommunityId,
                'invalid' => $invalidCommunityId,
            ),
        );

        $this->exerciseInvalidCases($communityListMethod, $commAdmin, $invalidUsers, $requiredParams);

        $this->resetAll();
        $this->params['token'] = $this->_loginAsUser($commAdmin);
        $this->params['method'] = $communityListMethod;
        $this->params['community_id'] = $validCommunityId;
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $groups = (array) $resp->data->groups;
        $this->assertEquals(3, count($groups), 'groups should have 3 entries');

        $expectedGroups = array('3003', '3004', '3005');
        foreach ($groups as $id => $name) {
            $this->assertTrue(in_array($id, $expectedGroups), 'id should have been in expectedGroups');
        }
    }
}
