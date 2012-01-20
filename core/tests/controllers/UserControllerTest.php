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
/** test user controller*/
class UserControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User', 'Feed');
    $this->_daos = array('User');
    parent::setUp();
    }

  /** test index*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/user");
    $this->assertController("user");

    $this->assertQuery("div.userBlock");
    }

  /** test register*/
  public function testRegisterAction()
    {
    $this->dispatchUrI("/user/register");
    $this->assertController("user");
    $this->assertAction("register");

    $this->assertQuery("form#registerForm");

    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password1'] = 'test';
    $this->params['password2'] = 'test';
    $this->params['firstname'] = 'Firstname';
    $this->params['lastname'] = 'Lastname';
    $this->params['submit'] = 'Register';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/register", null, true);


    $this->params = array();
    $this->params['email'] = 'user2@user1.com';
    $this->params['password1'] = 'test';
    $this->params['password2'] = 'test';
    $this->params['firstname'] = 'Firstname';
    $this->params['lastname'] = 'Lastname';
    $this->params['submit'] = 'Register';

    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/register");

    $userDao = $this->User->getByEmail($this->params['email']);
    $this->assertTrue($userDao != false, 'Unable to register');
    }

  /** test login*/
  public function testLoginAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/login");
    $this->assertController("user");
    $this->assertAction("login");

    $this->assertQuery("form#loginForm");

    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password'] = 'wrong password';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/login");

    $this->assertRedirect();
    $this->assertFalse(Zend_Auth::getInstance()->hasIdentity());

    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password'] = 'test';
    $this->request->setMethod('POST');
    $this->dispatchUrI('/user/login');
    $this->assertTrue(strpos($this->getBody(), 'Test Pass') !== false, 'Unable to authenticate');
    }

  /** test terms */
  public function testTermofserviceAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/termofservice");
    $this->assertController("user");
    $this->assertAction("termofservice");
    }

  /** test terms */
  public function testRecoverpasswordAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/recoverpassword", null, false);

    $this->assertQuery("form#recoverPasswordForm");

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI("/user/recoverpassword", $userDao, true);

    $this->resetAll();
    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->request->setMethod('POST');
    $userDao = $this->User->getByEmail($this->params['email']);
    $this->dispatchUrI("/user/recoverpassword", null);

    $userDao2 = $this->User->getByEmail($this->params['email']);
    $this->assertNotEquals($userDao->getPassword(), $userDao2->getPassword(), 'Unable to change password');
    $this->setupDatabase(array('default'));
    }

  /** test settings */
  public function testSettingsAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/settings", null, false);
    $body = $this->getBody();
    $this->assertTrue(empty($body), 'Should return nothing');

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $user2Dao = $this->User->load($usersFile[1]->getKey());
    $adminDao = $this->User->load($usersFile[2]->getKey());

    // Non admin user should not be able to edit other user's profiles
    $this->resetAll();
    $this->dispatchUrI('/user/settings?userId='.$adminDao->getKey(), $userDao, true);
    $this->resetAll();
    $this->dispatchUrI('/user/settings?userId='.$user2Dao->getKey(), $userDao, true);

    $this->resetAll();
    $this->dispatchUrI("/user/settings", $userDao);
    $this->assertQuery("div#tabsSettings");
    $this->assertQuery("li.settingsCommunityList");

    $this->resetAll();
    $this->params = array();
    $this->params['modifyPassword'] = 'true';
    $this->params['oldPassword'] = 'test';
    $this->params['newPassword'] = 'newPassword';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/settings", $userDao);

    $userCheckDao = $this->User->getByEmail($userDao->getEmail());
    $this->assertNotEquals($userDao->getPassword(), $userCheckDao->getPassword(), 'Unable to change password');

    $this->setupDatabase(array('default'));

    $this->resetAll();
    $this->params = array();
    $this->params['firstname'] = 'new First Name';
    $this->params['lastname'] = 'new Last Name';
    $this->params['company'] = 'Compagny';
    $this->params['privacy'] = MIDAS_USER_PRIVATE;
    $this->params['modifyAccount'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/settings", $userDao);

    $userCheckDao = $this->User->load($userDao->getKey());
    $this->assertEquals($this->params['firstname'], $userCheckDao->getFirstname(), 'Unable to change account information');

    $this->resetAll();
    $this->params = array();
    $this->params['modifyPicture'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/settings", $userDao);

    $userCheckDao = $this->User->load($userDao->getKey());

    $thumbnail = $userCheckDao->getThumbnail();
    $this->assertTrue(!empty($thumbnail), 'Unable to change avatar');

    $this->setupDatabase(array('default'));
    }

  /** test manage */
  public function testManageAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/manage", null, false);

    $body = $this->getBody();
    $this->assertTrue(empty($body), 'The page should be empty');

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI("/user/manage", $userDao);

    $this->assertQuery('div.genericInfo');

    $folder = $userDao->getPublicFolder();
    $this->assertQuery("tr[element='".$folder->getKey()."']");
    }

  /** test userpage */
  public function testUserpageAction()
    {
    $this->resetAll();
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI('/user/userpage', $userDao);

    $this->assertQuery('div.genericInfo');

    $folder = $userDao->getPublicFolder();
    $this->assertQuery("tr[element='".$folder->getKey()."']");

    // Should be able to see this user page since user is public
    $this->resetAll();
    $this->dispatchUrI('/user/'.$userDao->getKey(), null);

    $userDao->setPrivacy(MIDAS_USER_PRIVATE);
    $this->User->save($userDao);

    // Should throw an exception since the user is now private
    $this->resetAll();
    $this->dispatchUrI('/user/'.$userDao->getKey(), null, true);

    // Private user should be able to view his own user page
    $this->resetAll();
    $this->dispatchUrI('/user/'.$userDao->getKey(), $userDao);
    $this->assertController('user');
    $this->assertAction('userpage');
    }

  /** test validentry */
  public function testValidentryAction()
    {
    $this->resetAll();
    $this->dispatchUrI('/user/validentry');
    $this->assertTrue(strpos($this->getBody(), 'false') !== false);

    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'user1@user1.com';
    $this->params['type'] = 'dbuser';
    $this->dispatchUrI('/user/validentry');
    $this->assertTrue(strpos($this->getBody(), 'true') !== false);

    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'test_email_not_in_db';
    $this->params['type'] = 'dbuser';
    $this->dispatchUrI('/user/validentry');
    $this->assertTrue(strpos($this->getBody(), 'false') !== false);

    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'user1@user1.com';
    $this->params['type'] = 'login';
    $this->params['password'] = 'wrong_password';
    $this->dispatchUrI('/user/validentry');
    $this->assertTrue(strpos($this->getBody(), 'false') !== false);

    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'user1@user1.com';
    $this->params['type'] = 'login';
    $this->params['password'] = 'test';
    $this->dispatchUrI('/user/validentry');
    $this->assertTrue(strpos($this->getBody(), 'true') !== false);
    }

  /** Test admin ability to delete a user */
  public function testDeleteUserAction()
    {
    $modelLoader = new MIDAS_ModelLoader();
    $settingModel = $modelLoader->loadModel('Setting');
    $communityModel = $modelLoader->loadModel('Community');
    $folderModel = $modelLoader->loadModel('Folder');
    $itemModel = $modelLoader->loadModel('Item');
    $adminuserSetting = $settingModel->getValueByName('adminuser');
    $usersFile = $this->loadData('User', 'default');
    $commFile = $this->loadData('Community', 'default');
    $itemFile = $this->loadData('Item', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $user2 = $this->User->load($usersFile[1]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());
    $comm = $communityModel->load($commFile[0]->getKey());
    $item = $itemModel->load($itemFile[0]->getKey());

    // Render the delete dialog and make sure it has correct text
    $this->resetAll();
    $this->dispatchUrI('/user/deletedialog?userId='.$user1->getKey(), $adminUser);
    $this->assertQuery('input[type="hidden"][name="userId"][value="'.$user1->getKey().'"]');
    $this->assertQueryContentContains('#deleteDialogUserName', $user1->getFirstname().' '.$user1->getLastname());

    // Should fail if we aren't logged in
    $this->resetAll();
    $this->dispatchUrI('/user/delete?userId='.$user1->getKey(), null, true);

    // Should fail if we try to delete an admin user
    $this->resetAll();
    $this->dispatchUrI('/user/delete?userId='.$adminUser->getKey(), $adminUser, true);

    // Should fail if a non admin user tries to delete a different user
    $this->resetAll();
    $this->dispatchUrI('/user/delete?userId='.$user2->getKey(), $user1, true);

    // Make the item exist outside of the user's tree
    $folderModel->addItem($comm->getPublicFolder(), $item);

    $oldRevisions = $user1->getItemrevisions();
    $this->assertTrue(count($oldRevisions) > 0);
    $revisionKeys = array();
    foreach($oldRevisions as $oldRevision)
      {
      $this->assertEquals($oldRevision->getUserId(), $user1->getKey());
      $revisionKeys[] = $oldRevision->getKey();
      }
    // Delete user 1 as administrator
    $key = $user1->getKey();
    $this->resetAll();
    $this->dispatchUrI('/user/delete?userId='.$user1->getKey(), $adminUser);

    // Make sure user record is now gone
    $this->assertFalse($this->User->load($key));

    // Make sure all of user's revisions that weren't removed are now listed as uploaded by superadmin
    $revisionModel = $modelLoader->loadModel('ItemRevision');
    $revisionNotDeleted = false;
    foreach($revisionKeys as $revisionKey)
      {
      $revision = $revisionModel->load($revisionKey);
      $this->assertTrue($revision === false || $revision->getUserId() == $adminuserSetting);
      if($revision !== false)
        {
        $revisionNotDeleted = true;
        }
      }
    $this->assertTrue($revisionNotDeleted, 'At least one revision should not have been deleted');
    }

  /** Test user's ability to delete himself */
  public function testDeleteSelfAction()
    {
    $modelLoader = new MIDAS_ModelLoader();
    $settingModel = $modelLoader->loadModel('Setting');
    $communityModel = $modelLoader->loadModel('Community');
    $folderModel = $modelLoader->loadModel('Folder');
    $itemModel = $modelLoader->loadModel('Item');
    $adminuserSetting = $settingModel->getValueByName('adminuser');
    $usersFile = $this->loadData('User', 'default');
    $commFile = $this->loadData('Community', 'default');
    $itemFile = $this->loadData('Item', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $comm = $communityModel->load($commFile[0]->getKey());
    $item = $itemModel->load($itemFile[0]->getKey());

    // Render the delete dialog and make sure it has correct text for self-deletion
    $this->resetAll();
    $this->dispatchUrI('/user/deletedialog?userId='.$user1->getKey(), $user1);
    $this->assertQuery('input[type="hidden"][name="userId"][value="'.$user1->getKey().'"]');
    $this->assertTrue(strpos($this->getBody(), 'Are you sure you want to delete your user account?') !== false);

    // Make item exist outside of user's tree
    $folderModel->addItem($comm->getPublicFolder(), $item);

    $oldRevisions = $user1->getItemrevisions();
    $this->assertTrue(count($oldRevisions) > 0);
    $revisionKeys = array();
    foreach($oldRevisions as $oldRevision)
      {
      $this->assertEquals($oldRevision->getUserId(), $user1->getKey());
      $revisionKeys[] = $oldRevision->getKey();
      }
    // Delete user 1 as user 1
    $key = $user1->getKey();
    $this->resetAll();
    $this->dispatchUrI('/user/delete?userId='.$user1->getKey(), $user1);

    // Make sure user record is now gone
    $this->assertFalse($this->User->load($key));

    // Make sure all of user's revisions that weren't removed are now listed as uploaded by superadmin
    $revisionModel = $modelLoader->loadModel('ItemRevision');
    $revisionNotDeleted = false;
    foreach($revisionKeys as $revisionKey)
      {
      $revision = $revisionModel->load($revisionKey);
      $this->assertTrue($revision === false || $revision->getUserId() == $adminuserSetting);
      if($revision !== false)
        {
        $revisionNotDeleted = true;
        }
      }
    $this->assertTrue($revisionNotDeleted, 'At least one revision should not have been deleted');
    }

  /** Test setting the admin status of users */
  public function testSetAdminStatus()
    {
    $usersFile = $this->loadData('User', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    $this->assertFalse($user1->isAdmin());
    $this->assertTrue($adminUser->isAdmin());

    // Admin checkbox should be visible for an admin on his own view, it should be checked and disabled
    $this->resetAll();
    $this->dispatchUrI('/user/settings', $adminUser);
    $this->assertQuery('input[type="checkbox"][name="adminStatus"][checked="checked"][disabled="disabled"]');

    // Admin checkbox should be visible for an admin on user 1's view, it should be unchecked and enabled
    $this->resetAll();
    $this->dispatchUrI('/user/settings?userId='.$user1->getKey(), $adminUser);
    $this->assertQuery('input[type="checkbox"][name="adminStatus"]');
    $this->assertNotQuery('input[type="checkbox"][name="adminStatus"][checked="checked"]');
    $this->assertNotQuery('input[type="checkbox"][name="adminStatus"][disabled="disabled"]');

    // Admin checkbox should not be visible on user 1's setting page at all
    $this->resetAll();
    $this->dispatchUrI('/user/settings?userId='.$user1->getKey(), $user1);
    $this->assertNotQuery('input[type="checkbox"][name="adminStatus"]');

    // If non admin user attempts to maliciously become admin, make sure we ignore it.
    $this->resetAll();
    $this->params = array();
    $this->params['firstname'] = 'First Name';
    $this->params['lastname'] = 'Last Name';
    $this->params['company'] = 'Company';
    $this->params['privacy'] = MIDAS_USER_PRIVATE;
    $this->params['adminStatus'] = 'on';
    $this->params['modifyAccount'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI('/user/settings', $user1);

    $user1 = $this->User->load($user1->getKey());
    $this->assertFalse($user1->isAdmin());

    // Admin user should be allowed to set user 1 as admin
    $this->resetAll();
    $this->params = array();
    $this->params['firstname'] = 'First Name';
    $this->params['lastname'] = 'Last Name';
    $this->params['company'] = 'Company';
    $this->params['privacy'] = MIDAS_USER_PRIVATE;
    $this->params['adminStatus'] = 'on';
    $this->params['modifyAccount'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI('/user/settings?userId='.$user1->getKey(), $adminUser);

    $user1 = $this->User->load($user1->getKey());
    $this->assertTrue($user1->isAdmin());

    // Admin user should be able to unset another admin user's status
    $this->resetAll();
    $this->params = array();
    $this->params['firstname'] = 'First Name';
    $this->params['lastname'] = 'Last Name';
    $this->params['company'] = 'Company';
    $this->params['privacy'] = MIDAS_USER_PRIVATE;
    $this->params['adminStatus'] = '';
    $this->params['modifyAccount'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI('/user/settings?userId='.$user1->getKey(), $adminUser);

    $user1 = $this->User->load($user1->getKey());
    $this->assertFalse($user1->isAdmin());

    // But an admin should not be able to remove their own admin status
    $this->resetAll();
    $this->params = array();
    $this->params['firstname'] = 'First Name';
    $this->params['lastname'] = 'Last Name';
    $this->params['company'] = 'Company';
    $this->params['privacy'] = MIDAS_USER_PRIVATE;
    $this->params['adminStatus'] = '';
    $this->params['modifyAccount'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI('/user/settings?userId='.$adminUser->getKey(), $adminUser);

    $adminUser = $this->User->load($adminUser->getKey());
    $this->assertTrue($adminUser->isAdmin());
    }
  }
