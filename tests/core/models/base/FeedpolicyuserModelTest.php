<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class FeedpolicyuserModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Feedpolicyuser'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreatePolicyAndGetPolicy()
    {
    $feedsFile=$this->loadData('Feed','default');
    $usersFile=$this->loadData('User','default');
    $policy=$this->Feedpolicyuser->createPolicy($usersFile[0],$feedsFile[5],1);
    $this->assertEquals(true,$policy->saved);
    $policy=$this->Feedpolicyuser->getPolicy($usersFile[0],$feedsFile[5]);
    $this->assertNotEquals(false, $policy);
    }
  }