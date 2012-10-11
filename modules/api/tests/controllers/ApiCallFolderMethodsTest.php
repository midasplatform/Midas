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
class ApiCallFolderMethodsTest extends ApiCallMethodsTest
  {
  /** set up tests */
  public function setUp()
    {
    parent::setUp();
    }

  /** Test creating a folder */
  public function testFolderCreate()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.folder.create';
    $this->params['name'] = 'testFolderCreate';
    $this->params['parentid'] = $userDao->getPublicfolderId();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Make sure folder was created correctly
    $this->assertNotEmpty($resp->data->uuid);
    $this->assertEquals($userDao->getPublicfolderId(), $resp->data->parent_id);
    $this->assertEquals('testFolderCreate', $resp->data->name);
    $this->assertEquals('', $resp->data->description);

    // try to create a folder where have read access on the parent folder
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.create';
    $this->params['name'] = 'testFolderCreate';
    $this->params['parentid'] = "1012";
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // now with write access
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.create';
    $this->params['name'] = 'testFolderCreate';
    $this->params['parentid'] = "1013";
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);


    $userModel = MidasLoader::loadModel('User');
    $userDao = $userModel->load('1');
    $folderModel = MidasLoader::loadModel('Folder');
    $readFolder = $folderModel->load('1012');
    $writeFolder = $folderModel->load('1013');
    $adminFolder = $folderModel->load('1014');
    $nonWrites = array($readFolder);
    $nonAdmins = array($readFolder, $writeFolder);
    $writes = array($writeFolder, $adminFolder);

    // try to set name with read, should fail
    foreach($nonWrites as $folder)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.folder.create';
      $this->params['name'] = 'readFolder';
      $this->params['uuid'] = $folder->getUuid();
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }
    // try to set name with writes, should pass
    foreach($writes as $folder)
      {
      // get the current folder name
      $freshfolder = $folderModel->load($folder->getFolderId());
      $folderName = $freshfolder->getName();
      $newfolderName = $folderName . "suffix";
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.folder.create';
      $this->params['name'] = $newfolderName;
      $this->params['uuid'] = $folder->getUuid();
      $resp = $this->_callJsonApi();
      $this->_assertStatusOk($resp);
      $refreshFolder = $folderModel->load($folder->getFolderId());
      // ensure that the name was properly updated
      $this->assertEquals($newfolderName, $refreshFolder->getName(), 'Folder name should have been changed');
      }
    // try to set privacy without admin, should fail
    foreach($nonAdmins as $folder)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.folder.create';
      $this->params['name'] = 'writeFolder';
      $this->params['uuid'] = $folder->getUuid();
      $this->params['privacy'] = 'Private';
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }
    // try to set privacy with admin, should pass
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($userDao);
    $this->params['method'] = 'midas.folder.create';
    $this->params['name'] = 'writeFolder';
    $this->params['uuid'] = $adminFolder->getUuid();
    $this->params['privacy'] = 'Private';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    // try to set privacy to an invalid string
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($userDao);
    $this->params['method'] = 'midas.folder.create';
    $this->params['name'] = 'writeFolder';
    $this->params['uuid'] = $adminFolder->getUuid();
    $this->params['privacy'] = 'El Duderino';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);
    }

  /** Test listing of child folders */
  public function testFolderChildren()
    {
    $this->resetAll();
    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.folder.children';
    $this->params['id'] = 1000;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Should contain 2 folders and 0 items
    $this->assertEquals(count($resp->data->folders), 2);
    $this->assertEquals(count($resp->data->items), 0);

    $this->assertEquals($resp->data->folders[0]->_model, 'Folder');
    $this->assertEquals($resp->data->folders[1]->_model, 'Folder');
    $this->assertEquals($resp->data->folders[0]->folder_id, 1001);
    $this->assertEquals($resp->data->folders[1]->folder_id, 1002);
    $this->assertEquals($resp->data->folders[0]->name, 'User 1 name Folder 2');
    $this->assertEquals($resp->data->folders[1]->name, 'User 1 name Folder 3');
    $this->assertEquals($resp->data->folders[0]->description, 'Description Folder 2');
    $this->assertEquals($resp->data->folders[1]->description, 'Description Folder 3');

    $this->resetAll();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.folder.children';
    $this->params['id'] = 1001;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Should contain 0 folders and 2 items
    $this->assertEquals(count($resp->data->folders), 0);
    $this->assertEquals(count($resp->data->items), 2);

    $this->assertEquals($resp->data->items[0]->item_id, 1000);
    $this->assertEquals($resp->data->items[1]->item_id, 1001);
    $this->assertEquals($resp->data->items[0]->name, 'name 1');
    $this->assertEquals($resp->data->items[1]->name, 'name 2');
    $this->assertEquals($resp->data->items[0]->description, 'Description 1');
    $this->assertEquals($resp->data->items[1]->description, 'Description 2');
    }

  /** Test the folder.move method */
  public function testFolderMove()
    {
    $foldersFile = $this->loadData('Folder', 'default');

    $this->resetAll();
    $folderDao = $this->Folder->load($foldersFile[4]->getKey());
    $this->assertEquals($folderDao->getParentId(), '1000');

    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.folder.move';
    $this->params['id'] = $foldersFile[4]->getKey();
    $this->params['dstfolderid'] = 1002;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertEquals($resp->data->parent_id, '1002');

    // check that user can't move a folder they only have read on
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.move';
    $this->params['id'] = '1012';
    $this->params['dstfolderid'] = '1002';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // check that user can't move a folder they only have read on
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.move';
    $this->params['id'] = '1012';
    $this->params['dstfolderid'] = '1002';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // check that user can't move a folder they only have write on
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.move';
    $this->params['id'] = '1013';
    $this->params['dstfolderid'] = '1002';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // check that user can't move to a folder they only have read on
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.move';
    $this->params['id'] = '1014';
    $this->params['dstfolderid'] = '1012';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // check that user can move from admin to write
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.folder.move';
    $this->params['id'] = '1014';
    $this->params['dstfolderid'] = '1013';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    }

  /** Test the folder.list.permissions method */
  public function testFolderListPermissions()
    {
    $userModel = MidasLoader::loadModel('User');
    $userDao = $userModel->load('1');
    $folderModel = MidasLoader::loadModel('Folder');
    $readFolder = $folderModel->load('1012');
    $writeFolder = $folderModel->load('1013');
    $adminFolder = $folderModel->load('1014');
    $nonWrites = array($readFolder);
    $nonAdmins = array($readFolder, $writeFolder);
    $writes = array($writeFolder, $adminFolder);

    $params = array('method' => 'midas.folder.list.permissions',
                    'token' => $this->_loginAsUser($userDao));

    // try to list permissions without admin, should fail
    foreach($nonAdmins as $folder)
      {
      $this->resetAll();
      $params['folder_id'] = $folder->getFolderId();
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // now with admin perms
    $this->resetAll();
    $params['folder_id'] = $adminFolder->getFolderId();
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // ensure privacy is correct
    $privacyCodes = array("Public" => MIDAS_PRIVACY_PUBLIC, "Private" => MIDAS_PRIVACY_PRIVATE);
    $this->assertEquals($adminFolder->getPrivacyStatus(), $privacyCodes[$resp->data->privacy], 'Unexepected privacy value');

    // ensure user perms are correct
    $privilegeCodes = array("Admin" => MIDAS_POLICY_ADMIN, "Write" => MIDAS_POLICY_WRITE, "Read" => MIDAS_POLICY_READ);
    $userPolicies = $adminFolder->getFolderpolicyuser();
    $apiUsers = $resp->data->user;
    foreach($userPolicies as $userPolicy)
      {
      $user = $userPolicy->getUser();
      $userId = (string)$user->getUserId();
      $this->assertObjectHasAttribute($userId, $apiUsers, 'API call missing a user');
      $apiPolicyCode = $privilegeCodes[$apiUsers->$userId->policy];
      $this->assertEquals($apiPolicyCode, $userPolicy->getPolicy());
      }
    // ensure group perms are correct
    $groupPolicies = $adminFolder->getFolderpolicygroup();
    $apiGroups = $resp->data->group;
    foreach($groupPolicies as $groupPolicy)
      {
      $group = $groupPolicy->getGroup();
      $groupId = (string)$group->getGroupId();
      $this->assertObjectHasAttribute($groupId, $apiGroups, 'API call missing a group');
      $apiPolicyCode = $privilegeCodes[$apiGroups->$groupId->policy];
      $this->assertEquals($apiPolicyCode, $groupPolicy->getPolicy());
      }
    }


  }
