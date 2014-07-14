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
/** Community model test*/
class CommunityModelTest extends DatabaseTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Community', 'Folder', 'User', 'Folderpolicygroup', 'Folderpolicyuser');
    $this->_daos = array();
    parent::setUp();
    }

  /** test create community */
  public function testCreateCommunity()
    {
    Zend_Registry::set('modulesEnable', array());
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    $prevCount = count($this->Community->getAll());
    $usersFile = $this->loadData('User', 'default');
    $user = $this->User->load($usersFile[0]->getKey());
    $community = $this->Community->createCommunity('test community', 'test description',
                                                   0, $user, null, '');
    $newCount = count($this->Community->getAll());
    $this->assertTrue($community != false);
    $this->assertEquals($prevCount + 1, $newCount, 'Community count did not increase');

    $folder = $this->Folder->load($community->getFolderId());
    $this->assertEquals($folder->getName(), 'community_'.$community->getKey());

    $community = $this->Community->createCommunity('0', 'test description',
                                                   0, $user, null, '');
    $this->assertTrue($community != false);
    }

  /** test delete community */
  public function testDeleteCommunity()
    {
    Zend_Registry::set('modulesEnable', array());
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    $prevCount = count($this->Community->getAll());
    $usersFile = $this->loadData('User', 'default');
    $adminUser = $this->User->load($usersFile[2]->getKey());
    $communitiesFile = $this->loadData('Community', 'default');
    $communityForDeletion = $this->Community->load($communitiesFile[1]->getKey());
    $stableCommunity =  $this->Community->load($communitiesFile[0]->getKey());

    // create a top level folder in the community
    $parentFolderId = $communityForDeletion->getFolderId();
    $parentFolder = $this->Folder->load($parentFolderId);
    $newFolderName = 'TEST_DELETE_COMMUNITY_MOVED_FOLDER';
    $createdFolder = $this->Folder->createFolder($newFolderName, "folder desc", $parentFolderId);
    // copy policies of parent folder, as this is what the Folder Controller does
    $policyGroup = $parentFolder->getFolderpolicygroup();
    $policyUser = $parentFolder->getFolderpolicyuser();
    foreach($policyGroup as $policy)
      {
      $group = $policy->getGroup();
      $policyValue = $policy->getPolicy();
      $this->Folderpolicygroup->createPolicy($group, $createdFolder, $policyValue);
      }
    foreach($policyUser as $policy)
      {
      $user = $policy->getUser();
      $policyValue = $policy->getPolicy();
      $this->Folderpolicyuser->createPolicy($user, $createdFolder, $policyValue);
      }

    // now move the created folder to a different community
    $stableParentFolderId = $stableCommunity->getFolderId();
    $stableParentFolder = $this->Folder->load($stableParentFolderId);
    $this->Folder->move($createdFolder, $stableParentFolder);

    $deletedCommunityMemberGroup = $communityForDeletion->getMemberGroup();
    $movedFolderDeletedCommunityMemberGroupPolicy = $this->Folderpolicygroup->getPolicy($deletedCommunityMemberGroup, $createdFolder);
    // ensure that the group policy still exists, from the original community
    $this->assertNotEquals(false, $movedFolderDeletedCommunityMemberGroupPolicy, "Expected a folderpolicygroup exists, but it does not");

    // delete the community
    $this->Community->delete($communityForDeletion);
    // ensure that the count of communities has decreased
    $newCount = count($this->Community->getAll());
    $this->assertEquals($prevCount - 1, $newCount, 'Community count did not decrease');

    $movedFolderDeletedCommunityMemberGroupPolicy = $this->Folderpolicygroup->getPolicy($deletedCommunityMemberGroup, $createdFolder);
    // ensure that any folderpolicygroups connected with the deleted community are deleted
    $this->assertFalse($movedFolderDeletedCommunityMemberGroupPolicy, "A moved folder still has folderpolicygroup references to a deleted community");
    }
  }
