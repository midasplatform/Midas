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

/** Test for the community controller*/
class CommunityControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Community', 'Group', 'User', 'NewUserInvitation', 'CommunityInvitation');
    $this->_daos = array();
    parent::setUp();
    }

  /**
   * STUB: Test the index action
   */
  public function testIndexAction()
    {
    $this->dispatchUrI('/community');
    $this->assertController('community');
    $this->assertAction('index');
    }

  /**
   * STUB: Test view action
   */
  public function testViewAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $this->dispatchUrI('/community/'.$commFile[0]->getKey());
    $this->assertController('community');
    $this->assertAction('view');
    }

  /**
   * STUB: Test delete action
   */
  public function testDeleteAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $adminUser = $this->User->load($userFile[2]->getKey());

    $this->assertTrue($comm instanceof CommunityDao);

    // Anon should not be able to call this
    $this->dispatchUrI('/community/delete?communityId='.$commFile[0]->getKey(), null, true);

    // Admin should be able to call this
    $this->resetAll();
    $this->dispatchUrI('/community/delete?communityId='.$commFile[0]->getKey(), $adminUser);
    $comm = $this->Community->load($commFile[0]->getKey());
    $this->assertEquals($comm, null);
    }

  /**
   * Test manage action
   */
  public function testManageAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $user1 = $this->User->load($userFile[0]->getKey());
    $user2 = $this->User->load($userFile[1]->getKey());
    $adminUser = $this->User->load($userFile[2]->getKey());

    $this->Group->removeUser($comm->getAdminGroup(), $user1);
    $this->Group->addUser($comm->getModeratorGroup(), $user1);
    $this->assertTrue($this->Community->policyCheck($comm, $user1, MIDAS_POLICY_WRITE));
    $this->assertFalse($this->Community->policyCheck($comm, $user1, MIDAS_POLICY_ADMIN));
    $this->assertFalse($this->Community->policyCheck($comm, $user2, MIDAS_POLICY_WRITE));
    $this->assertTrue($this->Community->policyCheck($comm, $adminUser, MIDAS_POLICY_ADMIN));

    // User with no write (moderator) access should not be able to get to manage page
    $this->dispatchUrI('/community/manage?communityId='.$comm->getKey(), $user2, true);

    // User with write access should be able to see manage page
    $this->resetAll();
    $this->dispatchUrI('/community/manage?communityId='.$comm->getKey(), $user1);
    $this->assertController('community');
    $this->assertAction('manage');

    // User with write access should be able to change info
    $this->resetAll();
    $this->request->setMethod('POST');
    $this->params['communityId'] = $comm->getKey();
    $newName = "newname";
    $newDesc = "newdesc";
    $this->params['name'] = $newName;
    $this->params['description'] = $newDesc;
    $this->params['modifyInfo'] = 'true';
    $this->dispatchUrI('/community/manage', $user1);
    $comm = $this->Community->load($comm->getCommunityId());
    $this->assertEquals($comm->getName(), $newName, 'changed community has wrong name');
    $this->assertEquals($comm->getDescription(), $newDesc, 'changed community has wrong description');

    // Non-admin users should not be able to change privacy
    $nonAdmins = array($user1, $user2);
    foreach($nonAdmins as $user)
      {
      $this->resetAll();
      $this->request->setMethod('POST');
      $this->params['communityId'] = $comm->getKey();
      $this->params['name'] = $comm->getName();
      $this->params['privacy'] = (string)MIDAS_COMMUNITY_PUBLIC;
      $this->params['modifyPrivacy'] = 'true';
      $this->dispatchUrI('/community/manage', $user, true);
      }

    // exercise changing privacy status
    $privacyStatuses = array(MIDAS_COMMUNITY_PUBLIC, MIDAS_COMMUNITY_PRIVATE);
    foreach($privacyStatuses as $initialStatus)
      {
      foreach($privacyStatuses as $finalStatus)
        {
        // initialize privacy
        $comm->setPrivacy($initialStatus);
        $this->Community->save($comm);

        // try to set privacy with admin, should pass
        $this->resetAll();
        $this->request->setMethod('POST');
        $this->params['communityId'] = $comm->getKey();
        $this->params['name'] = $comm->getName();
        // send privacy as a string, since there was a bug with privacy codes as
        // strings, which is how they would be sent from an actual rendered page
        $this->params['privacy'] = (string)$finalStatus;
        $this->params['modifyPrivacy'] = 'true';
        $this->dispatchUrI('/community/manage', $adminUser);

        $comm = $this->Community->load($comm->getCommunityId());
        $this->assertEquals($comm->getPrivacy(), $finalStatus, 'changed community has wrong privacy');
        }
      }
    }

  /**
   * Test promote dialog and promote action
   */
  public function testPromote()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $user1 = $this->User->load($userFile[0]->getKey());
    $user2 = $this->User->load($userFile[1]->getKey());
    $adminUser = $this->User->load($userFile[2]->getKey());

    $this->Group->removeUser($comm->getAdminGroup(), $user1);
    $this->Group->addUser($comm->getModeratorGroup(), $user1);

    // Dialog should not show for a user with no write privileges
    $this->dispatchUrI('/community/promotedialog?community='.$comm->getKey().'&user='.$user2->getKey(), $user2, true);

    // Should get an error if trying to promote someone who isn't a community member
    $this->resetAll();
    $this->dispatchUrI('/community/promotedialog?community='.$comm->getKey().'&user='.$user2->getKey(), $user1, true);

    // Have user 2 join the community as a member
    $this->Group->addUser($comm->getMemberGroup(), $user2);

    // We should now be able to render a dialog for them
    $this->resetAll();
    // need admin perms to do this, so expect it to fail
    $this->dispatchUrI('/community/promotedialog?community='.$comm->getKey().'&user='.$user2->getKey(), $user1, true);

    // Admin user should be able to promote to both moderator or admin group
    $this->resetAll();
    $this->dispatchUrI('/community/promotedialog?community='.$comm->getKey().'&user='.$user2->getKey(), $adminUser);
    $this->assertController('community');
    $this->assertAction('promotedialog');
    $this->assertQueryCount('input[type="checkbox"][name*="groupCheckbox_"]', 2);
    $this->assertQuery('input[type="checkbox"][name="groupCheckbox_'.$comm->getModeratorgroupId().'"]');
    $this->assertQuery('input[type="checkbox"][name="groupCheckbox_'.$comm->getAdmingroupId().'"]');

    // Anonymous promotion not allowed
    $this->resetAll();
    $this->dispatchUrI('/community/promoteuser?communityId='.$comm->getKey().'&userId='.$user2->getKey(), null, true);

    // User with no write access should not be able to promote
    // Anonymous promotion not allowed
    $this->resetAll();
    $this->dispatchUrI('/community/promoteuser?communityId='.$comm->getKey().'&userId='.$user1->getKey(), null, true);

    // User 1 should not be able to promote to admin
    $this->resetAll();
    $this->dispatchUrI('/community/promoteuser?communityId='.$comm->getKey().
                       '&userId='.$user2->getKey().
                       '&groupCheckbox_'.$comm->getAdmingroupId().'=on',
                       $user1, true);

    // User 1 should not be able to promote to moderator
    $this->resetAll();
    $this->assertFalse($this->Community->policyCheck($comm, $user2, MIDAS_POLICY_WRITE));
    $this->dispatchUrI('/community/promoteuser?communityId='.$comm->getKey().
                       '&userId='.$user2->getKey().
                       '&groupCheckbox_'.$comm->getModeratorgroupId().'=on',
                       $user1, true);

    // Admin user should be able to promote user 2 to moderator
    $this->resetAll();
    $this->assertFalse($this->Community->policyCheck($comm, $user2, MIDAS_POLICY_WRITE));
    $this->dispatchUrI('/community/promoteuser?communityId='.$comm->getKey().
                       '&userId='.$user2->getKey().
                       '&groupCheckbox_'.$comm->getModeratorgroupId().'=on',
                       $adminUser);
    $this->assertTrue($this->Group->userInGroup($user2, $comm->getModeratorGroup()));

    // User 1 should not be able to remove moderator status on user 2
    $this->resetAll();
    $this->dispatchUrI('/community/removeuserfromgroup?groupId='.$comm->getModeratorgroupId().
                       '&userId='.$user2->getKey(), $user1, true);

    // User 1 should not be able to remove admin user as a member
    $this->resetAll();
    $this->dispatchUrI('/community/removeuserfromgroup?groupId='.$comm->getMembergroupId().
                       '&userId='.$adminUser->getKey(), $user1, true);

    // Admin user should be able to remove users from groups
    $this->resetAll();
    $this->assertTrue($this->Group->userInGroup($user2, $comm->getMemberGroup()));
    $this->dispatchUrI('/community/removeuserfromgroup?groupId='.$comm->getMembergroupId().
                       '&userId='.$user2->getKey(), $adminUser);
    $this->assertFalse($this->Group->userInGroup($user2, $comm->getMemberGroup()));
    $this->assertFalse($this->Group->userInGroup($user2, $comm->getModeratorGroup()));
    }

  /**
   * Test the community invitation dialog
   */
  public function testInvitationAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $user1 = $this->User->load($userFile[0]->getKey());
    $adminUser = $this->User->load($userFile[2]->getKey());

    // Not passing a communityId should throw exception
    $this->dispatchUrI('/community/invitation', $adminUser, true);

    // Anonymous users should get exception
    $this->resetAll();
    $this->dispatchUrI('/community/invitation?communityId='.$comm->getKey(), null, true);

    // Have user 1 join the community as a member; should not be able to see dialog
    $this->Group->removeUser($comm->getModeratorGroup(), $user1);
    $this->Group->removeUser($comm->getAdminGroup(), $user1);
    $this->Group->addUser($comm->getMemberGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/invitation?communityId='.$comm->getKey(), $user1, true);

    // Have user 1 join moderator group; now should be able to see dialog
    $this->Group->addUser($comm->getModeratorGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/invitation?communityId='.$comm->getKey(), $user1);
    }

  /**
   * Test select group dialog
   */
  public function testSelectgroupAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $user1 = $this->User->load($userFile[0]->getKey());
    $user2 = $this->User->load($userFile[1]->getKey());
    $adminUser = $this->User->load($userFile[2]->getKey());

    // Not passing a communityId should throw exception
    $this->dispatchUrI('/community/selectgroup', $adminUser, true);

    // Anonymous users should get exception
    $this->resetAll();
    $this->dispatchUrI('/community/selectgroup?communityId='.$comm->getKey(), null, true);

    // Have user 1 join the community as a member; should not be able to see dialog
    $this->Group->removeUser($comm->getModeratorGroup(), $user1);
    $this->Group->removeUser($comm->getAdminGroup(), $user1);
    $this->Group->addUser($comm->getMemberGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/selectgroup?communityId='.$comm->getKey(), $user1, true);

    // Have user 1 join moderator group; now should be able to see dialog
    $this->Group->addUser($comm->getModeratorGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/selectgroup?communityId='.$comm->getKey(), $user1);
    $this->assertQuery('option[value="'.$comm->getMembergroupId().'"]');
    $this->assertQuery('option[value="'.$comm->getModeratorgroupId().'"]');
    $this->assertNotQuery('option[value="'.$comm->getAdmingroupId().'"]');

    // Have user 1 join admin group; now should be able to see admin group also
    $this->Group->addUser($comm->getAdminGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/selectgroup?communityId='.$comm->getKey(), $user1);
    $this->assertQuery('option[value="'.$comm->getMembergroupId().'"]');
    $this->assertQuery('option[value="'.$comm->getModeratorgroupId().'"]');
    $this->assertQuery('option[value="'.$comm->getAdmingroupId().'"]');
    }
    
  /**
   * Test sendinvitation action
   */
  public function testAddusertogroupAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $user1 = $this->User->load($userFile[0]->getKey());
    $user2 = $this->User->load($userFile[1]->getKey());

    // Anonymous users should get exception
    $this->dispatchUrI('/community/addusertogroup?communityId='.$comm->getKey(), null, true);

    // Have user 1 join the community as a member; should not be able to see dialog
    $this->Group->removeUser($comm->getMemberGroup(), $user2);
    $this->Group->removeUser($comm->getAdminGroup(), $user2);
    $this->Group->addUser($comm->getMemberGroup(), $user1);
    $this->Group->addUser($comm->getAdminGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey()
            .'&group_id='.$comm->getAdminGroup()->getKey()
            .'&user_id='.$user2->getKey(), $user1);

    $this->assertTrue($this->Group->userInGroup($user2, $comm->getAdminGroup()));
    }

  /**
   * Test sendinvitation action
   */
  public function testSendinvitationAction()
    {
    $commFile = $this->loadData('Community', 'default');
    $userFile = $this->loadData('User', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());
    $user1 = $this->User->load($userFile[0]->getKey());
    $user2 = $this->User->load($userFile[1]->getKey());
    $adminUser = $this->User->load($userFile[2]->getKey());

    // Not passing a communityId should throw exception
    $this->dispatchUrI('/community/sendinvitation', $adminUser, true);

    // Anonymous users should get exception
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey(), null, true);

    // Have user 1 join the community as a member; should not be able to see dialog
    $this->Group->removeUser($comm->getModeratorGroup(), $user1);
    $this->Group->removeUser($comm->getAdminGroup(), $user1);
    $this->Group->addUser($comm->getMemberGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey(), $user1, true);

    // Have user 1 join moderator group; should not be able to invoke on admin group
    $this->Group->addUser($comm->getModeratorGroup(), $user1);
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey().
                       '&groupId='.$comm->getAdmingroupId().'&email=test@test.com', $user1, true);
    // Should be able to invoke on moderator group
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey().
                       '&groupId='.$comm->getModeratorgroupId().'&email=test@test.com', $user1);

    // Default to member group
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey().
                       '&email=test@test.com', $user1);
    // Make sure we only have one record for test@test.com, and that it is the member group invitation
    $invites = $this->NewUserInvitation->getAllByParams(array('email' => 'test@test.com', 'community_id' => $comm->getKey()));
    $this->assertEquals(count($invites), 1);
    $this->assertEquals($invites[0]->getGroupId(), $comm->getMembergroupId());

    // Test the case of inviting an existing user by email (should not create new user invitation)
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey().
                       '&email='.$user2->getEmail().'&groupId='.$comm->getModeratorgroupId(), $user1);
    $newUserInvites = $this->NewUserInvitation->getAllByParams(array('email' => $user2->getEmail(), 'community_id' => $comm->getKey()));
    $this->assertEquals(count($newUserInvites), 0);
    $invite = $this->CommunityInvitation->isInvited($comm, $user2, true);
    $this->assertTrue($invite instanceof CommunityInvitationDao);
    $this->assertEquals($invite->getGroupId(), $comm->getModeratorgroupId());

    // Test the case of inviting by user id
    $this->resetAll();
    $this->dispatchUrI('/community/sendinvitation?communityId='.$comm->getKey().
                       '&userId='.$adminUser->getKey().'&groupId='.$comm->getModeratorgroupId(), $user1);
    $newUserInvites = $this->NewUserInvitation->getAllByParams(array('email' => $adminUser->getEmail(), 'community_id' => $comm->getKey()));
    $this->assertEquals(count($newUserInvites), 0);
    $invite = $this->CommunityInvitation->isInvited($comm, $adminUser, true);
    $this->assertTrue($invite instanceof CommunityInvitationDao);
    $this->assertEquals($invite->getGroupId(), $comm->getModeratorgroupId());
    }
  }
