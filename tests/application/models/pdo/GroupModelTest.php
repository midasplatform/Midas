<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class GroupModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Group'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreateAndAddUser()
    {
    $usersFile=$this->loadData('User','default');
    $communitiesFile=$this->loadData('Community','default');
    $newgroup=$this->Group->createGroup($communitiesFile[0], "testName");
    $this->assertNotEquals(false, $newgroup);
    $this->assertEquals("testName", $newgroup->getName());

    $this->Group->addUser($newgroup, $usersFile[0]);
    $users=$newgroup->getUsers();
    $this->assertNotEquals(false, $users);
    $this->assertEquals($usersFile[0]->getKey(), $users[0]->getKey());
    }
  }
