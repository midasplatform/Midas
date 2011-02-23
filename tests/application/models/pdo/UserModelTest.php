<?php
require_once dirname(__FILE__).'/../../DatabaseTestCase.php';
class UserModelTest extends DatabaseTestCase
  {

  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array('User');
    $this->_daos=array('User');
    parent::setUp();
    }

  public function testGetUserCommunities()
    {
    $communitiesFile=$this->loadData('Community','default');

    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $communityDaos=$this->User->getUserCommunities($userDao);
    if(!in_array(  $communitiesFile[0] , $communityDaos,false))
      {
      $this->fail('Unable to match community');
      }
    }

  public function testGetUserCommunitiesException()
    {
    try
      {
      $communityDaos=$this->User->getUserCommunities('test');
      }
      catch (Exception $expected) {
          return;
      }
    $this->fail('An expected exception has not been raised.');
    }
  }
