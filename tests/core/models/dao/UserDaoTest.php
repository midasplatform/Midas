<?php

class UserDaoTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array('User');
    $this->_daos=array('User');
    parent::setUp();
    }

  public function testGetFullName()
    {
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $this->assertEquals($userDao->getFullName(), $usersFile[0]->firstname." ".$usersFile[0]->lastname);
    }
  }