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
    $nonModerators = array($commMember);
    $nonAdmins = array($commMember, $commModerator);
    $moderators = array($commModerator, $commAdmin);

    $validGroupId = '3004';
    $invalidGroupId = '-10';
    $validUserId = '2';
    $invalidUserId = '-10';
    
    // test all the failure cases
    foreach($methods as $method)
      {
      // Try anonymously first
      $this->resetAll();
      $this->params['method'] = $method;
      $this->params['group_id'] = $validGroupId;
      $this->params['user_id'] = $validUserId;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

      // an invalid group
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($commAdmin);
      $this->params['method'] = $method;
      $this->params['group_id'] = $invalidGroupId;
      $this->params['user_id'] = $validUserId;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);
      
      // an invalid user
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($commAdmin);
      $this->params['method'] = $method;
      $this->params['group_id'] = $validGroupId;
      $this->params['user_id'] = $invalidUserId;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);
      
      // as a non admin
      foreach($nonAdmins as $nonAdmin)
        {
        $this->resetAll();
        $this->params['token'] = $this->_loginAsUser($nonAdmin);
        $this->params['method'] = $method;
        $this->params['group_id'] = $validGroupId;
        $this->params['user_id'] = $validUserId;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
        }
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


  }
