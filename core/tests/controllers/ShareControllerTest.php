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

/** Test for the Share Controller */
class ShareControllerTest extends ControllerTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('policies'));
    $this->_models = array('Item', 'Folder', 'User', 'Group');
    parent::setUp();
    }

  /** Test managing permissions on a resource */
  public function testDialogAction()
    {
    $usersFile = $this->loadData('User', 'policies');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $user2 = $this->User->load($usersFile[1]->getKey());

    // should throw an exception due to missing parameters
    $this->dispatchUrI('/share/dialog', null, true);

    // should throw an exception due to invalid element id
    $this->resetAll();
    $this->dispatchUrI('/share/dialog?type=folder&id=834', null, true);

    // should throw an exception due to invalid permissions
    $this->resetAll();
    $this->dispatchUrI('/share/dialog?type=folder&element=1000', null, true);

    // should throw an exception due to invalid permissions (write permissions)
    $this->resetAll();
    $this->dispatchUrI('/share/dialog?type=folder&element=1002', $user2, true);

    // should render the dialog since the user has admin privileges
    $this->resetAll();
    $this->dispatchUrI('/share/dialog?type=folder&element=1002', $user1);
    $this->assertController('share');
    $this->assertAction('dialog');
    $this->assertQuery('div#permissionPrivate');
    $this->assertQuery('td.changePermissionSelectBox');
    $this->assertQueryContentContains('div.jsonShareContent', json_encode(array('type' => 'folder', 'element' => '1002')));

    // -------------- Folder policy user ------------------------
    // user 2 should not have read privileges yet
    $folder = $this->Folder->load(1002);
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));

    // now create a new privilege entry for user2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1002&createPolicy&newPolicyType=user';
    $url .= '&newPolicyId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but not any higher
    $folder = $this->Folder->load(1002);
    $this->assertTrue($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_WRITE));

    // now change permissions for user 2 to add edit privileges
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1002&changePolicy&changeType=user';
    $url .= '&changeId='.$user2->getKey().'&changeVal='.MIDAS_POLICY_WRITE;
    $this->dispatchUrI($url, $user1);

    // user 2 should now have write privileges, but not any higher
    $folder = $this->Folder->load(1002);
    $this->assertTrue($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_WRITE));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_ADMIN));

    // now remove permissions for user 2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1002&removePolicy&removeType=user';
    $url .= '&removeId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should no longer have any permissions
    $folder = $this->Folder->load(1002);
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_WRITE));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_ADMIN));

    // set the permissions to public
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1002&setPublic';
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but no higher
    $folder = $this->Folder->load(1002);
    $this->assertTrue($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_WRITE));

    // set the permissions to private
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1002&setPrivate';
    $this->dispatchUrI($url, $user1);

    // user 2 should now have no privileges
    $folder = $this->Folder->load(1002);
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));

    // -------------- Item policy user ------------------------
    // user 2 should not have read privileges yet
    $item = $this->Item->load(3);
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));

    // now create a new privilege entry for user2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=item&element=3&createPolicy&newPolicyType=user';
    $url .= '&newPolicyId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but not any higher
    $item = $this->Item->load(3);
    $this->assertTrue($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_WRITE));

    // now change permissions for user 2 to add edit privileges
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=item&element=3&changePolicy&changeType=user';
    $url .= '&changeId='.$user2->getKey().'&changeVal='.MIDAS_POLICY_WRITE;
    $this->dispatchUrI($url, $user1);

    // user 2 should now have write privileges, but not any higher
    $item = $this->Item->load(3);
    $this->assertTrue($this->Item->policyCheck($item, $user2, MIDAS_POLICY_WRITE));
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_ADMIN));

    // now remove permissions for user 2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=item&element=3&removePolicy&removeType=user';
    $url .= '&removeId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should no longer have any permissions
    $item = $this->Item->load(3);
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_WRITE));
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_ADMIN));

    // set the permissions to public
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=item&element=3&setPublic';
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but no higher
    $item = $this->Item->load(3);
    $this->assertTrue($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_WRITE));

    // set the permissions to private
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=item&element=3&setPrivate';
    $this->dispatchUrI($url, $user1);

    // user 2 should now have no privileges
    $item = $this->Item->load(3);
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));
    }

  /** Test recursive policy application */
  public function testApplyPoliciesRecursive()
    {
    $usersFile = $this->loadData('User', 'policies');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $user2 = $this->User->load($usersFile[1]->getKey());

    // should throw an exception due to invalid folder id
    $this->dispatchUrI('/share/dialog?folderId=834', null, true);

    // render the dialog
    $this->resetAll();
    $this->dispatchUrI('/share/applyrecursivedialog?folderId=1002', $user1);
    $this->assertController('share');
    $this->assertAction('applyrecursivedialog');
    $this->assertQuery('form#applyPoliciesRecursiveForm');
    $this->assertQuery('input[type="hidden"][name="folderId"][value="1002"]');

    // make sure user 2 has no privileges on the item or folder
    $folder = $this->Folder->load(1002);
    $subfolder = $this->Folder->load(1007);
    $item = $this->Item->load(1);
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Folder->policyCheck($subfolder, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));

    // add read permission to user 2 on the top level folder
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1002&createPolicy&newPolicyType=user';
    $url .= '&newPolicyId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // apply recursive policies
    $this->resetAll();
    $this->request->setMethod('POST');
    $this->dispatchUrI('/share/applyrecursivedialog?folderId=1002', $user1);

    // user 2 should now be able to read the whole subtree
    $folder = $this->Folder->load(1002);
    $subfolder = $this->Folder->load(1007);
    $item = $this->Item->load(1);
    $this->assertTrue($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));
    $this->assertTrue($this->Folder->policyCheck($subfolder, $user2, MIDAS_POLICY_READ));
    $this->assertTrue($this->Item->policyCheck($item, $user2, MIDAS_POLICY_READ));
    }

  /** Test that creating a folder or item gives admin access to the creator */
  public function testPoliciesOnCreation()
    {
    $usersFile = $this->loadData('User', 'policies');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $user2 = $this->User->load($usersFile[1]->getKey());

    // user 2 should not have read privileges yet
    $folder = $this->Folder->load(1007);
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));

    // now create a new privilege entry for user2
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1007&createPolicy&newPolicyType=user';
    $url .= '&newPolicyId='.$user2->getKey();
    $this->dispatchUrI($url, $user1);

    // user 2 should now have read privileges, but not any higher
    $folder = $this->Folder->load(1007);
    $this->assertTrue($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_READ));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_WRITE));

    // now change permissions for user 2 to add edit privileges
    $this->resetAll();
    $this->request->setMethod('POST');
    $url = '/share/dialog?type=folder&element=1007&changePolicy&changeType=user';
    $url .= '&changeId='.$user2->getKey().'&changeVal='.MIDAS_POLICY_WRITE;
    $this->dispatchUrI($url, $user1);

    // user 2 should now have write privileges, but not any higher
    $folder = $this->Folder->load(1007);
    $this->assertTrue($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_WRITE));
    $this->assertFalse($this->Folder->policyCheck($folder, $user2, MIDAS_POLICY_ADMIN));

    // Create a folder inside the parent where we have write access
    $this->resetAll();
    $this->request->setMethod('POST');
    $this->dispatchUrI('/folder/createfolder?folderId=1007&createFolder&name=HelloWorld', $user2);
    $resp = json_decode($this->getBody());
    $this->assertTrue($resp[0] != false);
    $this->assertNotEmpty($resp[2]);
    $this->assertNotEmpty($resp[3]);
    $this->assertEquals($resp[2]->folder_id, '1007');
    $this->assertEquals($resp[3]->parent_id, '1007');

    // The user should have admin access to the child, but not the parent
    $parentFolder = $this->Folder->load($resp[2]->folder_id);
    $childFolder = $this->Folder->load($resp[3]->folder_id);
    $this->assertTrue($this->Folder->policyCheck($childFolder, $user2, MIDAS_POLICY_ADMIN));
    $this->assertFalse($this->Folder->policyCheck($parentFolder, $user2, MIDAS_POLICY_ADMIN));

    // Create an item inside the parent where we have write access
    $this->resetAll();
    $this->params = array();
    $this->params['parent'] = '1007';
    $this->params['license'] = 0;
    $this->params['testpath'] = BASE_PATH.'/tests/testfiles/search.png'; //testing mode param
    $this->dispatchUrI('/upload/saveuploaded', $user2);
    $search = $this->Item->getItemsFromSearch('search.png', $user2);
    $this->assertNotEmpty($search, 'Unable to find uploaded item');

    // The user should have admin access to the item
    $item = $this->Item->load($search[0]->item_id);
    $this->assertTrue($this->Item->policyCheck($item, $user2, MIDAS_POLICY_ADMIN));
    }
  }
