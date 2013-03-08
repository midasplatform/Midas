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
/** Tests the functionality of the web API Group methods */
class ApiCallGroupMethodsTest extends ApiCallMethodsTest
  {
  /** set up tests */
  public function setUp()
    {
    parent::setUp();
    }


  /** Test adding and removing a user from a group */
  public function testGroupUserAddRemove()
    {
    $addMethod = "midas.group.add.user";
    $removeMethod = "midas.group.remove.user";
    $methods = array($addMethod, $removeMethod);

    $communityModel = MidasLoader::loadModel('Community');
    $comm2001 = $communityModel->load('2001');
    $userModel = MidasLoader::loadModel('User');
    $commMember = $userModel->load('4');
    $commModerator = $userModel->load('5');
    $commAdmin = $userModel->load('6');

    $validGroupId = '3004';
    $invalidGroupId = '-10';
    $validUserId = '2';
    $invalidUserId = '-10';

    // add in an anonymous user to non admins
    $invalidUsers = array($commMember, $commModerator, false);

    // test all the invalid cases
    foreach($methods as $method)
      {
      $requiredParams = array(
        array('name' => 'group_id', 'valid' => $validGroupId, 'invalid' => $invalidGroupId),
        array('name' => 'user_id', 'valid' => $validUserId, 'invalid' => $invalidUserId));

      $this->exerciseInvalidCases($method, $commAdmin, $invalidUsers, $requiredParams);
      }

    // ensure the user isn't already in the group
    $groupModel = MidasLoader::loadModel('Group');
    $changedUser = $userModel->load($validUserId);
    $group = $groupModel->load($validGroupId);
    $this->assertFalse($groupModel->userInGroup($changedUser, $group), "This user is not expected to be in the group");

    // add the user to the group
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $addMethod;
    $this->params['group_id'] = $validGroupId;
    $this->params['user_id'] = $validUserId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // ensure the user is now in the group
    $this->assertTrue($groupModel->userInGroup($changedUser, $group), "This user is expected to be in the group");

    // remove the user from the group
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $removeMethod;
    $this->params['group_id'] = $validGroupId;
    $this->params['user_id'] = $validUserId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertFalse($groupModel->userInGroup($changedUser, $group), "This user is not expected to be in the group");
    }

  /** Test adding and removing a group */
  public function testGroupAddRemove()
    {
    $validCommunityId = 2001;
    $invalidCommunityId = -10;

    $communityModel = MidasLoader::loadModel('Community');
    $comm2001 = $communityModel->load('2001');
    $userModel = MidasLoader::loadModel('User');
    $commMember = $userModel->load('4');
    $commModerator = $userModel->load('5');
    $commAdmin = $userModel->load('6');

    // add in an anonymous user to non admins
    $invalidUsers = array($commMember, $commModerator, false);

    // group add

    $addMethod = "midas.group.add";
    $newGroupName = 'new group';
    $addMethodRequiredParams = array(
      array('name' => 'community_id', 'valid' => $validCommunityId, 'invalid' => $invalidCommunityId),
      array('name' => 'name', 'valid' => $newGroupName)); // no invalid name

    $this->exerciseInvalidCases($addMethod, $commAdmin, $invalidUsers, $addMethodRequiredParams);

    $groupModel = MidasLoader::loadModel('Group');
    $existingGroups = $groupModel->findByCommunity($comm2001);

    // add a group via the api call

    $addedGroupName = 'ApiCallGroupMethodsTest';
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $addMethod;
    $this->params['community_id'] = $validCommunityId;
    $this->params['name'] = $addedGroupName;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $addedGroupId = $resp->data->group_id;
    // check that the group didn't already exist for the community
    foreach($existingGroups as $existingGroup)
      {
      $this->assertNotEquals($addedGroupId, $existingGroup->getGroupId(), 'added group has the same id as an existing group');
      }
    $addedGroup = $groupModel->load($addedGroupId);
    // check that the added group has the correct values
    $this->assertEquals($addedGroup->getCommunityId(), $validCommunityId, 'added group has incorrect community id');
    $this->assertEquals($addedGroup->getName(), $addedGroupName, 'added group has incorrect community id');

    // group remove

    $invalidGroupId = -10;
    $removeMethod = "midas.group.remove";
    $removeMethodRequiredParams = array(
      array('name' => 'group_id', 'valid' => $addedGroupId, 'invalid' => $invalidGroupId));

    $this->exerciseInvalidCases($removeMethod, $commAdmin, $invalidUsers, $removeMethodRequiredParams);

    // remove the group via the api call

    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $removeMethod;
    $this->params['group_id'] = $addedGroupId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $success = $resp->data->success;
    $this->assertEquals($success, 'true', 'success value should have been true');

    // ensure that the group doesn't exist
    $addedGroup = $groupModel->load($addedGroupId);
    $this->assertFalse($addedGroup, "group should have been removed but remains");
    }

  /** Test listing the users in a group */
  public function testGroupListUsers()
    {
    $validCommunityId = 2001;
    $invalidCommunityId = -10;
    $commAdminGroupId = 3003;
    $invalidGroupId = -10;

    $communityModel = MidasLoader::loadModel('Community');
    $comm2001 = $communityModel->load('2001');
    $userModel = MidasLoader::loadModel('User');
    $commMemberId = '4';
    $commModeratorId = '5';
    $commAdminId = '6';
    $commMember = $userModel->load($commMemberId);
    $commModerator = $userModel->load($commModeratorId);
    $commAdmin = $userModel->load($commAdminId);
    $commUsers =
      array($commMemberId => $commMember, $commModeratorId =>  $commModerator, $commAdminId =>  $commAdmin);


    // add in an anonymous user to non admins
    $invalidUsers = array($commMember, $commModerator, false);

    // group list users

    $groupListMethod = "midas.group.list.users";
    $requiredParams = array(
      array('name' => 'group_id', 'valid' => $commAdminGroupId, 'invalid' => $invalidGroupId));

    $this->exerciseInvalidCases($groupListMethod, $commAdmin, $invalidUsers, $requiredParams);

    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $groupListMethod;
    $this->params['group_id'] = $commAdminGroupId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $users = $resp->data->users;
    $users = (array)$users;
    $this->assertEquals(1, sizeof($users), 'users should only have one entry');
    foreach($users as $id => $names)
      {
      $this->assertEquals($id, $commAdminId, 'users should have commAdminId as an entry');
      $this->assertEquals($commUsers[$commAdminId]->getFirstname(), $names->firstname);
      $this->assertEquals($commUsers[$commAdminId]->getLastname(), $names->lastname);
      }

    // add some users, test again

    $groupModel = MidasLoader::loadModel('Group');
    $commAdminGroup = $groupModel->load($commAdminGroupId);
    $groupModel->addUser($commAdminGroup, $commMember);
    $groupModel->addUser($commAdminGroup, $commModerator);

    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $groupListMethod;
    $this->params['group_id'] = $commAdminGroupId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $users = $resp->data->users;
    $users = (array)$users;
    $this->assertEquals(3, sizeof($users), 'users should have 3 entries');
    $members = array($commAdminId, $commMemberId, $commModeratorId);
    foreach($users as $id => $names)
      {
      $this->assertTrue(in_array($id, $members), 'users should have '.$id.' as an entry');
      $this->assertEquals($commUsers[$id]->getFirstname(), $names->firstname);
      $this->assertEquals($commUsers[$id]->getLastname(), $names->lastname);
      }

    // remove some users, test again
    $groupModel->removeUser($commAdminGroup, $commMember);
    $groupModel->removeUser($commAdminGroup, $commModerator);

    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($commAdmin);
    $this->params['method'] = $groupListMethod;
    $this->params['group_id'] = $commAdminGroupId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $users = $resp->data->users;
    $users = (array)$users;
    $this->assertEquals(1, sizeof($users), 'users should only have one entry');
    foreach($users as $id => $names)
      {
      $this->assertEquals($id, $commAdminId, 'users should have commAdminId as an entry');
      $this->assertEquals($commUsers[$id]->getFirstname(), $names->firstname);
      $this->assertEquals($commUsers[$id]->getLastname(), $names->lastname);
      }
    }

  }
