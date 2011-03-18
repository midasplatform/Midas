<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class ItempolicygroupModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Itempolicygroup'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreatePolicyAndGetPolicy()
    {
    $groupsFile=$this->loadData('Group','default');
    $itemsFile=$this->loadData('Item','default');
    $policy=$this->Itempolicygroup->createPolicy($groupsFile[0],$itemsFile[1],1);
    $this->assertEquals(true,$policy->saved);
    $policy=$this->Itempolicygroup->getPolicy($groupsFile[0],$itemsFile[1]);
    $this->assertNotEquals(false, $policy);
    }
  }
