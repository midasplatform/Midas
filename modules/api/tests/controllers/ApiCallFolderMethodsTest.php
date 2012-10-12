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

  /**
   * helper function to initialize the passed in resources to the given privacy state.
   */
  protected function initializePrivacyStatus($testFolders, $testItems, $desiredPrivacyStatus)
    {
    $groupModel = MidasLoader::loadModel('Group');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");

    foreach($testFolders as $folder)
      {
      if($desiredPrivacyStatus != $folderpolicygroupModel->computePolicyStatus($folder))
        {
        if($desiredPrivacyStatus == MIDAS_PRIVACY_PUBLIC)
          {
          $policyDao = $folderpolicygroupModel->createPolicy($anonymousGroup, $folder, MIDAS_POLICY_READ);
          }
        else
          {
          $policyDao = $folderpolicygroupModel->getPolicy($anonymousGroup, $folder);
          $folderpolicygroupModel->delete($policyDao);
          }
        }
      $this->assertEquals($folderpolicygroupModel->computePolicyStatus($folder), $desiredPrivacyStatus, $folder->getName() . " has wrong privacy value after initialization");
      }

    foreach($testItems as $item)
      {
      if($desiredPrivacyStatus != $itempolicygroupModel->computePolicyStatus($item))
        {
        if($desiredPrivacyStatus == MIDAS_PRIVACY_PUBLIC)
          {
          $policyDao = $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
          }
        else
          {
          $policyDao = $itempolicygroupModel->getPolicy($anonymousGroup, $item);
          $itempolicygroupModel->delete($policyDao);
          }
        }
      $this->assertEquals($itempolicygroupModel->computePolicyStatus($item), $desiredPrivacyStatus, $item->getName() . " has wrong privacy value after initialization");
      }
    }

  /**
   * helper function to ensure that passed in resources have the given privacy.
   */
  protected function assertPrivacyStatus($testFolders, $testItems, $expectedPrivacyStatus)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itemModel = MidasLoader::loadModel('Item');
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");
    foreach($testFolders as $folder)
      {
      $folder = $folderModel->load($folder->getFolderId());
      $this->assertEquals($folderpolicygroupModel->computePolicyStatus($folder), $expectedPrivacyStatus, $folder->getName() . " has wrong privacy value");
      }
    foreach($testItems as $item)
      {
      $item = $itemModel->load($item->getItemId());
      $this->assertEquals($itempolicygroupModel->computePolicyStatus($item), $expectedPrivacyStatus, $item->getName() . " has wrong privacy value");
      }
    }

  /** Test the folder.set.privacy.recursive method */
  public function testFolderSetPrivacyRecursive()
    {
    $userModel = MidasLoader::loadModel('User');
    $itemModel = MidasLoader::loadModel('Item');

    $folderpolicyuserModel = MidasLoader::loadModel("Folderpolicyuser");
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itempolicyuserModel = MidasLoader::loadModel("Itempolicyuser");
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");

    $userDao = $userModel->load('1');
    $folderModel = MidasLoader::loadModel('Folder');
    $readFolder = $folderModel->load('1012');
    $writeFolder = $folderModel->load('1013');
    $adminFolder = $folderModel->load('1014');
    $nonWrites = array($readFolder);
    $nonAdmins = array($readFolder, $writeFolder);
    $writes = array($writeFolder, $adminFolder);

    $params = array('method' => 'midas.folder.set.privacy.recursive',
                    'token' => $this->_loginAsUser($userDao));

    // try to list permissions without admin, should fail
    foreach($nonAdmins as $folder)
      {
      $this->resetAll();
      $params['folder_id'] = $folder->getFolderId();
      $params['privacy'] = 'Public';
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // now with admin perms

    // first create a folder structure
    // -testrootfolder
    // --childfolder1
    // ---childfolder11
    // ----item111
    // --childfolder2
    // ---item21
    $testrootfolder = $folderModel->createFolder('testrootfolder', 'testrootfolder', $adminFolder);
    $childfolder1 = $folderModel->createFolder('childfolder1', 'childfolder1', $testrootfolder);
    $childfolder11 = $folderModel->createFolder('childfolder11', 'childfolder11', $childfolder1);
    $childfolder2 = $folderModel->createFolder('childfolder2', 'childfolder2', $testrootfolder);

    $item111 = $itemModel->createItem('item111', 'item111', $childfolder11);
    $item21 = $itemModel->createItem('item21', 'item21', $childfolder2);

    $testFolders = array($testrootfolder, $childfolder1, $childfolder11, $childfolder2);
    $testItems = array($item111, $item21);

    // set the user as an Admin on these test resources
    foreach($testFolders as $folder)
      {
      $folderpolicyuserModel->createPolicy($userDao, $folder, MIDAS_POLICY_ADMIN);
      $folderModel->save($folder);
      }
    foreach($testItems as $item)
      {
      $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_ADMIN);
      $itemModel->save($item);
      }

    // want to test Public -> Public
    //              Public -> Private
    //              Private -> Private
    //              Private -> Public
    $privacyStatuses = array(MIDAS_PRIVACY_PUBLIC, MIDAS_PRIVACY_PRIVATE);
    $privacyStrings = array(MIDAS_PRIVACY_PUBLIC => "Public", MIDAS_PRIVACY_PRIVATE => "Private");
    foreach($privacyStatuses as $initialStatus)
      {
      foreach($privacyStatuses as $finalStatus)
        {
        $this->initializePrivacyStatus($testFolders, $testItems, $initialStatus);
        // change privacy through the API
        $this->resetAll();
        $params['folder_id'] = $testrootfolder->getFolderId();
        $params['privacy'] = $privacyStrings[$finalStatus];
        $this->params = $params;
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp->data->success, count($testItems) + count($testFolders), 'Have set the privacy on an incorrect number of resources');
        $this->assertPrivacyStatus($testFolders, $testItems, $finalStatus);
        }
      }
    }



  }
