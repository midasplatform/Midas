<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class FolderpolicygroupModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Folderpolicygroup'
    );
    $this->_daos=array();
    parent::setUp();
    }

  public function testCreatePolicyAndGetPolicy()
    {
    $folderFile=$this->loadData('Folder','default');
    $groupsFile=$this->loadData('Group','default');
    $policy=$this->Folderpolicygroup->createPolicy($groupsFile[0],$folderFile[5],1);
    $this->assertEquals(true,$policy->saved);
    $policy=$this->Folderpolicygroup->getPolicy($groupsFile[0],$folderFile[5]);
    $this->assertNotEquals(false, $policy);
    }
  }
