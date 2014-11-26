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

/** test user controller */
class Core_UserControllerTest extends ControllerTestCase
{
    /** init tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_models = array('User', 'Community', 'Group', 'Feed', 'NewUserInvitation', 'PendingUser');
        $this->_daos = array('User');
        parent::setUp();
    }

    /** test index */
    public function testIndexAction()
    {
        $this->dispatchUrl("/user");
        $this->assertController("user");

        $this->assertQuery("div.userBlock");
    }

    /** test register */
    public function testRegisterAction()
    {
        $this->dispatchUrl("/user/register");
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
        $this->dispatchUrl("/user/register", null, true);

        $this->params = array();
        $this->params['email'] = 'user2@user1.com';
        $this->params['password1'] = 'test';
        $this->params['password2'] = 'test';
        $this->params['firstname'] = 'Firstname';
        $this->params['lastname'] = 'Lastname';
        $this->params['submit'] = 'Register';

        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/register");

        $userDao = $this->User->getByEmail($this->params['email']);
        $this->assertTrue($userDao != false, 'Unable to register');
    }

    /** test login */
    public function testLoginAction()
    {
        $this->resetAll();
        $this->dispatchUrl('/user/login');
        $this->assertController('user');
        $this->assertAction('login');

        $this->assertQuery('form#loginForm');

        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'user1@user1.com';
        $this->params['password'] = 'wrong password';
        $this->request->setMethod('POST');
        $this->dispatchUrl('/user/login');
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp->status == false);
        $this->assertTrue(is_string($resp->message) && strlen($resp->message) > 0);
        $this->assertFalse(Zend_Auth::getInstance()->hasIdentity());

        $userDao = $this->User->getByEmail('user1@user1.com');
        $this->User->changePassword($userDao, 'test');
        $this->User->save($userDao);

        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'user1@user1.com';
        $this->params['password'] = 'test';
        $this->request->setMethod('POST');
        $this->dispatchUrl('/user/login');
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp->status == true);
        $this->assertTrue(is_string($resp->redirect) && strlen($resp->redirect) > 0);
    }

    /** test terms */
    public function testTermofserviceAction()
    {
        $this->resetAll();
        $this->dispatchUrl("/user/termofservice");
        $this->assertController("user");
        $this->assertAction("termofservice");
    }

    /** test terms */
    public function testRecoverpasswordAction()
    {
        $this->resetAll();
        $this->dispatchUrl("/user/recoverpassword", null, false);

        $this->assertQuery("form#recoverPasswordForm");

        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $this->dispatchUrl("/user/recoverpassword", $userDao, true);

        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'user1@user1.com';
        $this->request->setMethod('POST');
        $userDao = $this->User->getByEmail($this->params['email']);
        $this->dispatchUrl("/user/recoverpassword", null);

        $userDao2 = $this->User->getByEmail($this->params['email']);
        $this->assertNotEquals($userDao->getSalt(), $userDao2->getSalt(), 'Salt should have changed');
        $this->setupDatabase(array('default'));
    }

    /** Test user accepting an email community invitation and registering */
    public function testEmailregisterAction()
    {
        $commFile = $this->loadData('Community', 'default');
        $userFile = $this->loadData('User', 'default');
        $comm = $this->Community->load($commFile[0]->getKey());
        $adminUser = $this->User->load($userFile[2]->getKey());

        // We should not be able to call this without an email or auth key
        $this->dispatchUri('/user/emailregister?authKey=abcd', null, true);
        $this->resetAll();
        $this->dispatchUri('/user/emailregister?email=test@test.com', null, true);

        // Test rendering of the view
        $inv = $this->NewUserInvitation->createInvitation('test@test.com', $comm->getModeratorGroup(), $adminUser);
        $this->resetAll();
        $this->dispatchUri('/user/emailregister?email='.$inv->getEmail().'&authKey='.$inv->getAuthKey(), null);
        $this->assertQueryContentContains('span.emailDisplay', $inv->getEmail());
        $this->assertQuery('input[type="hidden"][name="authKey"][value="'.$inv->getAuthKey().'"]');

        // Test submitting the registration
        $this->resetAll();
        $this->params = array();
        $this->params['email'] = $inv->getEmail();
        $this->params['authKey'] = $inv->getAuthKey();
        $this->params['firstName'] = 'Email';
        $this->params['lastName'] = 'Registration';
        $this->params['password1'] = 'badpassword';
        $this->params['password2'] = 'badpassword';
        $this->request->setMethod('POST');
        $this->dispatchUri('/user/emailregister', null);

        // We should now have a user who is a member of the moderator and members group of the community
        $user = $this->User->getByEmail($inv->getEmail());
        $this->assertTrue($user instanceof UserDao);
        $this->assertEquals($user->getFullName(), 'Email Registration');
        $this->assertTrue($this->Group->userInGroup($user, $comm->getMemberGroup()));
        $this->assertTrue($this->Group->userInGroup($user, $comm->getModeratorGroup()));
        $this->assertFalse($this->Group->userInGroup($user, $comm->getAdminGroup()));

        $this->resetAll();
        $this->dispatchUri('/user/emailsent'); // get some simple coverage of this action
    }

    /** Test email verification endpoint */
    public function testVerifyemailAction()
    {
        // We should not be able to call this without an email or auth key
        $this->dispatchUri('/user/verifyemail?authKey=abcd', null, true);
        $this->resetAll();
        $this->dispatchUri('/user/verifyemail?email=test@test.com', null, true);

        $pending = $this->PendingUser->createPendingUser('testverify@email.com', 'Pending', 'User', 'badpassword');
        $this->resetAll();
        $this->dispatchUri(
            '/user/verifyemail?email='.$pending->getEmail().'&authKey='.$pending->getAuthKey(),
            null
        );
        $this->assertRedirectTo('/user/userpage');
        $user = $this->User->getByEmail($pending->getEmail());
        $this->assertTrue($user instanceof UserDao);
        $this->assertEquals($user->getFirstname(), $pending->getFirstname());
        $this->assertEquals($user->getLastname(), $pending->getLastname());
        $pendingUsers = $this->PendingUser->getByParams(array('email' => $pending->getEmail()));
        $this->assertEmpty($pendingUsers);
    }

    /** test settings */
    public function testSettingsAction()
    {
        $this->resetAll();
        $this->dispatchUrl("/user/settings", null, false);
        $body = $this->getBody();
        $this->assertTrue(empty($body), 'Should return nothing');

        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $user2Dao = $this->User->load($usersFile[1]->getKey());
        $adminDao = $this->User->load($usersFile[2]->getKey());

        // Non admin user should not be able to edit other user's profiles
        $this->resetAll();
        $this->dispatchUrl('/user/settings?userId='.$adminDao->getKey(), $userDao, true);
        $this->resetAll();
        $this->dispatchUrl('/user/settings?userId='.$user2Dao->getKey(), $userDao, true);

        $this->resetAll();
        $this->dispatchUrl("/user/settings", $userDao);
        $this->assertQuery("div#tabsSettings");
        $this->assertQuery("li.settingsCommunityList");

        $instanceSalt = Zend_Registry::get('configGlobal')->password->prefix;
        // By changing password we will update the salt and hash
        $this->resetAll();
        $this->params = array();
        $this->params['modifyPassword'] = 'true';
        $this->params['oldPassword'] = 'test';
        $this->params['newPassword'] = 'newPassword';
        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/settings", $userDao);
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp[0] == true);

        $userCheckDao = $this->User->getByEmail($userDao->getEmail());
        $this->assertNotEquals($userDao->getSalt(), $userCheckDao->getSalt(), 'Salt should have changed');
        $this->assertTrue(
            $this->User->hashExists(hash('sha256', $instanceSalt.$userCheckDao->getSalt().'newPassword')),
            'New hash should have been added to password table'
        );
        $this->setupDatabase(array('default'));

        $this->resetAll();
        $this->params = array();
        $this->params['email'] = $userDao->getEmail();
        $this->params['firstname'] = 'new First Name';
        $this->params['lastname'] = 'new Last Name';
        $this->params['company'] = 'Compagny';
        $this->params['privacy'] = MIDAS_USER_PRIVATE;
        $this->params['modifyAccount'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/settings", $userDao);

        $userCheckDao = $this->User->load($userDao->getKey());
        $this->assertEquals(
            $this->params['firstname'],
            $userCheckDao->getFirstname(),
            'Unable to change account information'
        );

        $this->resetAll();
        $this->params = array();
        $this->params['modifyPicture'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/settings", $userDao);

        $userCheckDao = $this->User->load($userDao->getKey());

        $thumbnail = $userCheckDao->getThumbnail();
        $this->assertTrue(!empty($thumbnail), 'Unable to change avatar');

        // Should not be able to change to an invalid email
        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'invalid';
        $this->params['firstname'] = 'bad';
        $this->params['lastname'] = 'bad';
        $this->params['company'] = 'Compagny';
        $this->params['privacy'] = MIDAS_USER_PRIVATE;
        $this->params['modifyAccount'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/settings", $userDao);
        $userCheckDao = $this->User->load($userDao->getKey());
        $this->assertNotEquals($userCheckDao->getEmail(), 'invalid');
        $this->assertEquals($userCheckDao->getFirstname(), 'new First Name');
        $this->assertEquals($userCheckDao->getLastname(), 'new Last Name');

        // Should not be able to change to a different user's email
        $this->resetAll();
        $this->params = array();
        $this->params['email'] = $user2Dao->getEmail();
        $this->params['firstname'] = 'bad';
        $this->params['lastname'] = 'bad';
        $this->params['company'] = 'Compagny';
        $this->params['privacy'] = MIDAS_USER_PRIVATE;
        $this->params['modifyAccount'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/settings", $userDao);
        $userCheckDao = $this->User->load($userDao->getKey());
        $this->assertNotEquals($userCheckDao->getEmail(), $user2Dao->getEmail());
        $this->assertEquals($userCheckDao->getFirstname(), 'new First Name');
        $this->assertEquals($userCheckDao->getLastname(), 'new Last Name');

        // Should be able to change email to something valid and unique
        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'valid@unique.com';
        $this->params['firstname'] = 'Good';
        $this->params['lastname'] = 'Good';
        $this->params['company'] = 'Compagny';
        $this->params['privacy'] = MIDAS_USER_PRIVATE;
        $this->params['modifyAccount'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl("/user/settings", $userDao);
        $userCheckDao = $this->User->load($userDao->getKey());
        $this->assertEquals($userCheckDao->getEmail(), 'valid@unique.com');
        $this->assertEquals($userCheckDao->getFirstname(), 'Good');
        $this->assertEquals($userCheckDao->getLastname(), 'Good');

        $this->setupDatabase(array('default'));
    }

    /** test manage */
    public function testManageAction()
    {
        $this->resetAll();
        $this->dispatchUrl("/user/manage", null, false);

        $body = $this->getBody();
        $this->assertTrue(empty($body), 'The page should be empty');

        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $this->dispatchUrl("/user/manage", $userDao);

        $this->assertQuery('div.genericInfo');

        $folders = $userDao->getFolder()->getFolders();
        $this->assertQuery("tr[element='".$folders[0]->getKey()."']");
    }

    /** test userpage */
    public function testUserpageAction()
    {
        $this->resetAll();
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());
        $this->dispatchUrl('/user/userpage', $userDao);

        $this->assertQuery('div.genericInfo');

        $folders = $userDao->getFolder()->getFolders();
        $this->assertQuery("tr[element='".$folders[0]->getKey()."']");

        // Should be able to see this user page since user is public
        $this->resetAll();
        $this->dispatchUrl('/user/'.$userDao->getKey(), null);

        $userDao->setPrivacy(MIDAS_USER_PRIVATE);
        $this->User->save($userDao);

        // Should throw an exception since the user is now private
        $this->resetAll();
        $this->dispatchUrl('/user/'.$userDao->getKey(), null, true);

        // Private user should be able to view his own user page
        $this->resetAll();
        $this->dispatchUrl('/user/'.$userDao->getKey(), $userDao);
        $this->assertController('user');
        $this->assertAction('userpage');
    }

    /** test the userexists action */
    public function testUserexistsAction()
    {
        $this->resetAll();
        $this->dispatchUrl('/user/userexists');
        $this->assertTrue(strpos($this->getBody(), 'false') !== false);

        $this->resetAll();
        $this->params = array();
        $this->params['entry'] = 'user1@user1.com';
        $this->dispatchUrl('/user/userexists');
        $this->assertTrue(strpos($this->getBody(), 'true') !== false);

        $this->resetAll();
        $this->params = array();
        $this->params['entry'] = 'test_email_not_in_db';
        $this->dispatchUrl('/user/userexists');
        $this->assertTrue(strpos($this->getBody(), 'false') !== false);

        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'user1@user1.com';
        $this->params['password'] = 'wrong_password';
        $this->request->setMethod('POST');
        $this->dispatchUrl('/user/login');
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp->status == false);

        $userDao = $this->User->getByEmail('user1@user1.com');
        $this->User->changePassword($userDao, 'test');
        $this->User->save($userDao);

        $this->resetAll();
        $this->params = array();
        $this->params['email'] = 'user1@user1.com';
        $this->params['password'] = 'test';
        $this->request->setMethod('POST');
        $this->dispatchUrl('/user/login');
        $resp = json_decode($this->getBody());
        $this->assertTrue($resp->status == true);
    }

    /** Test admin ability to delete a user */
    public function testDeleteUserAction()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
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
        $this->dispatchUrl('/user/deletedialog?userId='.$user1->getKey(), $adminUser);
        $this->assertQuery('input[type="hidden"][name="userId"][value="'.$user1->getKey().'"]');
        $this->assertQueryContentContains(
            '#deleteDialogUserName',
            $user1->getFirstname().' '.$user1->getLastname()
        );

        // Should fail if we aren't logged in
        $this->resetAll();
        $this->dispatchUrl('/user/delete?userId='.$user1->getKey(), null, true);

        // Should fail if we try to delete an admin user
        $this->resetAll();
        $this->dispatchUrl('/user/delete?userId='.$adminUser->getKey(), $adminUser, true);

        // Should fail if a non admin user tries to delete a different user
        $this->resetAll();
        $this->dispatchUrl('/user/delete?userId='.$user2->getKey(), $user1, true);

        // Make the item exist outside of the user's tree
        $commFolders = $comm->getFolder()->getFolders();
        $folderModel->addItem($commFolders[0], $item);

        $oldRevisions = $user1->getItemrevisions();
        $this->assertTrue(count($oldRevisions) > 0);
        $revisionKeys = array();
        foreach ($oldRevisions as $oldRevision) {
            $this->assertEquals($oldRevision->getUserId(), $user1->getKey());
            $revisionKeys[] = $oldRevision->getKey();
        }
        // Delete user 1 as administrator
        $key = $user1->getKey();
        $this->resetAll();
        $this->dispatchUrl('/user/delete?userId='.$user1->getKey(), $adminUser);

        // Make sure user record is now gone
        $this->assertFalse($this->User->load($key));

        // Make sure all of user's revisions that weren't removed are now listed as uploaded by superadmin
        $revisionModel = MidasLoader::loadModel('ItemRevision');
        $revisionNotDeleted = false;
        foreach ($revisionKeys as $revisionKey) {
            $revision = $revisionModel->load($revisionKey);
            $this->assertTrue($revision === false || $revision->getUserId() == $adminuserSetting);
            if ($revision !== false) {
                $revisionNotDeleted = true;
            }
        }
        $this->assertTrue($revisionNotDeleted, 'At least one revision should not have been deleted');
    }

    /** Test user's ability to delete himself */
    public function testDeleteSelfAction()
    {
        $settingModel = MidasLoader::loadModel('Setting');
        $communityModel = MidasLoader::loadModel('Community');
        $folderModel = MidasLoader::loadModel('Folder');
        $itemModel = MidasLoader::loadModel('Item');
        $adminuserSetting = $settingModel->getValueByName('adminuser');
        $usersFile = $this->loadData('User', 'default');
        $commFile = $this->loadData('Community', 'default');
        $itemFile = $this->loadData('Item', 'default');
        $user1 = $this->User->load($usersFile[0]->getKey());
        $comm = $communityModel->load($commFile[0]->getKey());
        $item = $itemModel->load($itemFile[0]->getKey());

        // Render the delete dialog and make sure it has correct text for self-deletion
        $this->resetAll();
        $this->dispatchUrl('/user/deletedialog?userId='.$user1->getKey(), $user1);
        $this->assertQuery('input[type="hidden"][name="userId"][value="'.$user1->getKey().'"]');
        $this->assertTrue(strpos($this->getBody(), 'Are you sure you want to delete your user account?') !== false);

        // Make item exist outside of user's tree
        $commFolders = $comm->getFolder()->getFolders();
        $folderModel->addItem($commFolders[0], $item);

        $oldRevisions = $user1->getItemrevisions();
        $this->assertTrue(count($oldRevisions) > 0);
        $revisionKeys = array();
        foreach ($oldRevisions as $oldRevision) {
            $this->assertEquals($oldRevision->getUserId(), $user1->getKey());
            $revisionKeys[] = $oldRevision->getKey();
        }
        // Delete user 1 as user 1
        $key = $user1->getKey();
        $this->resetAll();
        $this->dispatchUrl('/user/delete?userId='.$user1->getKey(), $user1);

        // Make sure user record is now gone
        $this->assertFalse($this->User->load($key));

        // Make sure all of user's revisions that weren't removed are now listed as uploaded by superadmin
        $revisionModel = MidasLoader::loadModel('ItemRevision');
        $revisionNotDeleted = false;
        foreach ($revisionKeys as $revisionKey) {
            $revision = $revisionModel->load($revisionKey);
            $this->assertTrue($revision === false || $revision->getUserId() == $adminuserSetting);
            if ($revision !== false) {
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
        $this->dispatchUrl('/user/settings', $adminUser);
        $this->assertQuery('input[type="checkbox"][name="adminStatus"][checked="checked"][disabled="disabled"]');

        // Admin checkbox should be visible for an admin on user 1's view, it should be unchecked and enabled
        $this->resetAll();
        $this->dispatchUrl('/user/settings?userId='.$user1->getKey(), $adminUser);
        $this->assertQuery('input[type="checkbox"][name="adminStatus"]');
        $this->assertNotQuery('input[type="checkbox"][name="adminStatus"][checked="checked"]');
        $this->assertNotQuery('input[type="checkbox"][name="adminStatus"][disabled="disabled"]');

        // Admin checkbox should not be visible on user 1's setting page at all
        $this->resetAll();
        $this->dispatchUrl('/user/settings?userId='.$user1->getKey(), $user1);
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
        $this->dispatchUrl('/user/settings', $user1);

        $user1 = $this->User->load($user1->getKey());
        $this->assertFalse($user1->isAdmin());

        // Admin user should be allowed to set user 1 as admin
        $this->resetAll();
        $this->params = array();
        $this->params['email'] = $user1->getEmail();
        $this->params['firstname'] = 'First Name';
        $this->params['lastname'] = 'Last Name';
        $this->params['company'] = 'Company';
        $this->params['privacy'] = MIDAS_USER_PRIVATE;
        $this->params['adminStatus'] = 'on';
        $this->params['modifyAccount'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl('/user/settings?userId='.$user1->getKey(), $adminUser);

        $user1 = $this->User->load($user1->getKey());
        $this->assertTrue($user1->isAdmin());

        // Admin user should be able to unset another admin user's status
        $this->resetAll();
        $this->params = array();
        $this->params['email'] = $user1->getEmail();
        $this->params['firstname'] = 'First Name';
        $this->params['lastname'] = 'Last Name';
        $this->params['company'] = 'Company';
        $this->params['privacy'] = MIDAS_USER_PRIVATE;
        $this->params['adminStatus'] = '';
        $this->params['modifyAccount'] = 'true';
        $this->request->setMethod('POST');
        $this->dispatchUrl('/user/settings?userId='.$user1->getKey(), $adminUser);

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
        $this->dispatchUrl('/user/settings?userId='.$adminUser->getKey(), $adminUser);

        $adminUser = $this->User->load($adminUser->getKey());
        $this->assertTrue($adminUser->isAdmin());
    }
}
