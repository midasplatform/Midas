<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class ItempolicyuserModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Itempolicyuser'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreatePolicyAndGetPolicy()
    {
    $usersFile=$this->loadData('User','default');
    $itemsFile=$this->loadData('Item','default');
    $policy=$this->Itempolicyuser->createPolicy($usersFile[0],$itemsFile[1],1);
    $this->assertEquals(true,$policy->saved);
    $policy=$this->Itempolicyuser->getPolicy($usersFile[0],$itemsFile[1]);
    $this->assertNotEquals(false, $policy);
    }
  }
