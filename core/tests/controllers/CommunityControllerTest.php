<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
/** Test for the community controller*/
class CommunityControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Community', 'Group', 'User');
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
    $this->dispatchUrI('/community/promotedialog?community='.$comm->getKey().'&user='.$user2->getKey(), $user1);
    $this->assertController('community');
    $this->assertAction('promotedialog');
    // It should only display the moderator group as an available option for promotion
    $this->assertQueryCount('input[type="checkbox"][name*="groupCheckbox_"]', 1);
    $this->assertQuery('input[type="checkbox"][name="groupCheckbox_'.$comm->getModeratorgroupId().'"]');

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

    // User 1 should be able to promote to moderator
    $this->resetAll();
    $this->assertFalse($this->Community->policyCheck($comm, $user2, MIDAS_POLICY_WRITE));
    $this->dispatchUrI('/community/promoteuser?communityId='.$comm->getKey().
                       '&userId='.$user2->getKey().
                       '&groupCheckbox_'.$comm->getModeratorgroupId().'=on',
                       $user1);
    $this->assertTrue($this->Community->policyCheck($comm, $user2, MIDAS_POLICY_WRITE));

    // User 1 should be able to remove moderator status on user 2
    $this->resetAll();
    $this->dispatchUrI('/community/removeuserfromgroup?groupId='.$comm->getModeratorgroupId().
                       '&userId='.$user2->getKey(), $user1);
    $this->assertFalse($this->Community->policyCheck($comm, $user2, MIDAS_POLICY_WRITE));

    // User 1 should not be able to remove admin user as a member
    $this->resetAll();
    $this->dispatchUrI('/community/removeuserfromgroup?groupId='.$comm->getMembergroupId().
                       '&userId='.$adminUser->getKey(), $user1);
    $resp = json_decode($this->getBody());
    $this->assertTrue($resp[0] == false);
    }
  }
