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

  /**
   * helper function to test simple invalid cases:
   * will test all invalid users sending in all required valid params
   * will also test all combinations of invalid params with a valid user
   * for each required param
   * @param type $method
   * @param type $validUser
   * @param type $invalidUsers
   * @param type $requiredParams
   */
  protected function exerciseInvalidCases($method, $validUser, $invalidUsers, $requiredParams)
    {
    // test all invalid users with valid params
    foreach($invalidUsers as $invalidUser)
      {
      $this->resetAll();
      if($invalidUser != null)
        {
        $this->params['token'] = $this->_loginAsUser($invalidUser);
        }
      $this->params['method'] = $method;
      foreach($requiredParams as $requiredParam)
        {
        $this->params[$requiredParam['name']] = $requiredParam['valid'];
        }
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // test valid user with all combinations of missing/invalid/valid params
    // will not test a case of valid user and all valid params

    $numParams = sizeof($requiredParams);
    // create an int array that is initially all 0
    $requiredParamStates = array_fill(0, $numParams, 0);
    $allTwosSum = 2 * $numParams;

    while(array_sum($requiredParamStates) < $allTwosSum)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($validUser);
      $this->params['method'] = $method;
      $skipTestCase = false;
      foreach($requiredParams as $ind => $requiredParam)
        {
        // find the state corresponding to this param
        $state = $requiredParamStates[$ind];
        // 0s mean the param is missing (not sent)
        if($state == 1)
          {
          // 1s mean an invalid form of the param is sent
          if(!array_key_exists('invalid', $requiredParam))
            {
            // some params may not have an invalid form
            // skip this test case as it would repeat the case of the missing param
            $skipTestCase = true;
            break;
            }
          $this->params[$requiredParam['name']] = $requiredParam['invalid'];
          }
        elseif($state == 2)
          {
          // 2s mean a valid form of the param is sent
          $this->params[$requiredParam['name']] = $requiredParam['valid'];
          }
        elseif($state < 0 || $state > 2)
          {
          throw new Exception("left most param state is invalid value: ".$state);
          }
        }
      if(!$skipTestCase)
        {
        $resp = $this->_callJsonApi();
        $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);
        }

      // now increment the parameter states
      // add 1 to the right most value
      $incrementIndex = $numParams - 1;
      $rightMost = $requiredParamStates[$incrementIndex];
      $rightMost += 1;
      $requiredParamStates[$incrementIndex] = $rightMost;
      while($rightMost == 3)
        {
        // if the right most goes to 3, set it to 0
        // and repeat the process one index to the left, stop moving
        // to the left when the last increment doesn't go to 3,
        // i.e. there are no more carry bits
        $rightMost = 0;
        $requiredParamStates[$incrementIndex] = $rightMost;
        if($incrementIndex > 0)
          {
          $incrementIndex -= 1;
          $rightMost = $requiredParamStates[$incrementIndex];
          $rightMost += 1;
          $requiredParamStates[$incrementIndex] = $rightMost;
          }
        else
          {
          throw new Exception("left most param state is 3");
          }
        }
      }
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
  }
