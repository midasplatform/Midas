<?php
require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class FeedpolicygroupModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Feedpolicygroup'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreatePolicyAndGetPolicy()
    {
    $feedsFile=$this->loadData('Feed','default');
    $groupsFile=$this->loadData('Group','default');
    $policy=$this->Feedpolicygroup->createPolicy($groupsFile[0],$feedsFile[0],1);
    $this->assertEquals(true,$policy->saved);
    $policy=$this->Feedpolicygroup->getPolicy($groupsFile[0],$feedsFile[0]);
    $this->assertNotEquals(false, $policy);
    }
  }
