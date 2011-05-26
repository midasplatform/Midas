<?php
require_once dirname(__FILE__).'/../ControllerTestCase.php';
class UserControllerTest extends ControllerTestCase
  {
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models=array('User', 'Feed');
    $this->_daos=array('User');
    parent::setUp();
    
    }

  /** test register*/
  public function testRegisterAction()
    {
    $this->dispatchUrI("/user/register");
    $this->assertController("user");
    $this->assertAction("register");  
    
    $this->assertQuery("form#registerForm");
    
    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password1'] = 'test';
    $this->params['password2'] = 'test';
    $this->params['firstname'] = 'Firstname';
    $this->params['lastname'] = 'Lastname';
    $this->params['submit'] = 'Register';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/register", null, true);

    
    $this->params = array();
    $this->params['email'] = 'user2@user1.com';
    $this->params['password1'] = 'test';
    $this->params['password2'] = 'test';
    $this->params['firstname'] = 'Firstname';
    $this->params['lastname'] = 'Lastname';
    $this->params['submit'] = 'Register';
    
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/register");
    
    $userDao = $this->User->getByEmail($this->params['email']);
    if($userDao == false)
      {
      $this->fail('Unable to register');
      }
    }
    
  /** test login*/
  public function testLoginAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/login");
    $this->assertController("user");
    $this->assertAction("login");  
    
    $this->assertQuery("form#loginForm");
    
    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password'] = 'wrong password';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/login");

    $this->assertRedirect();
    $this->assertFalse(Zend_Auth::getInstance()->hasIdentity());
    
    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password'] = 'test';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/login");

    if(strpos($this->getBody(), 'Test Pass') === false)
      {
      $this->fail('Unable to authenticate');
      }    
    }
    
  /** test terms */
  public function testTermofserviceAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/termofservice");
    $this->assertController("user");
    $this->assertAction("termofservice");  
    }
    
  /** test terms */
  public function testRecoverpasswordAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/recoverpassword", null, false); 
    
    $this->assertQuery("form#recoverPasswordForm");
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI("/user/recoverpassword", $userDao, true); 
    
    $this->resetAll();
    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->request->setMethod('POST');
    $userDao = $this->User->getByEmail($this->params['email']);
    $this->dispatchUrI("/user/recoverpassword", null);
    
    $userDao2 = $this->User->getByEmail($this->params['email']);
    if($userDao->getPassword() == $userDao2->getPassword())
      {
      $this->fail('Unable to change password');
      }
    $this->setupDatabase(array('default'));
    }
    
  /** test settings */
  public function testSettingsAction()
    {
    $this->resetAll();    
    $this->dispatchUrI("/user/settings", null, false); 
    $body = $this->getBody();
    if(!empty($body))
      {
      $this->fail('Should return nothing');
      } 
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI("/user/settings", $userDao); 
   
    $this->assertQuery("div#tabsSettings");
    $this->assertQuery("li.settingsCommunityList");
    
    $this->resetAll(); 
    $this->params = array();
    $this->params['modifyPassword'] = 'true';
    $this->params['oldPassword'] = 'test';
    $this->params['newPassword'] = 'newPassword';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/settings", $userDao); 
    
    $userCheckDao = $this->User->getByEmail($userDao->getEmail());
    if($userDao->getPassword() == $userCheckDao->getPassword())
      {
      $this->fail('Unable to change password');
      }
    $this->setupDatabase(array('default'));
      
    $this->resetAll(); 
    $this->params = array();
    $this->params['firstname'] = 'new First Name';
    $this->params['lastname'] = 'new Last Name';
    $this->params['company'] = 'Compagny';
    $this->params['privacy'] = MIDAS_USER_PRIVATE;
    $this->params['modifyAccount'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/settings", $userDao); 
    
    $userCheckDao = $this->User->load($userDao->getKey());
    if($this->params['firstname'] != $userCheckDao->getFirstname())
      {
      $this->fail('Unable to change account information');
      }  
      
    $this->resetAll(); 
    $this->params = array();
    $this->params['modifyPicture'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/settings", $userDao); 
    
    $userCheckDao = $this->User->load($userDao->getKey());
    
    $thumbnail = $userCheckDao->getThumbnail();
    if(empty($thumbnail))
      {
      $this->fail('Unable to change avatar');
      }  
      
    $this->setupDatabase(array('default'));
    }
    
   /** test manage */
  public function testManageAction()
    {
    $this->resetAll();    
    $this->dispatchUrI("/user/manage", null, false);  
    
    $body = $this->getBody();
    if(!empty($body))
      {
      $this->fail('The page should be empty');
      }
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI("/user/manage", $userDao);    

    $this->assertQuery('div.genericInfo');
    
    $folder = $userDao->getPublicFolder();
    $this->assertQuery("tr[element='".$folder->getKey()."']");
    }
    
   /** test userpage */
  public function testUserpageAction()
    {
    $this->resetAll();        
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI("/user/userpage", $userDao);    

    $this->assertQuery('div.genericInfo');
    
    $folder = $userDao->getPublicFolder();
    $this->assertQuery("tr[element='".$folder->getKey()."']");
    
    $this->params = array();
    $this->params['user_id'] = $userDao->getKey();
    $this->dispatchUrI("/user/userpage", null, false);   
    
    $userDao->setPrivacy(MIDAS_USER_PRIVATE);
    $this->User->save($userDao);
    
    $this->dispatchUrI("/user/userpage", null, true);   
    }
    
    /** test validentry */
  public function testValidentryAction()
    {
    $this->resetAll();
    $this->dispatchUrI("/user/validentry");
    if(strpos($this->getBody(), 'false') === false)
      {
      $this->fail();
      }  
    
    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'user1@user1.com';
    $this->params['type'] = 'dbuser';
    $this->dispatchUrI("/user/validentry");
    if(strpos($this->getBody(), 'true') === false)
      {
      $this->fail();
      } 
      
    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'test_email_not_in_db';
    $this->params['type'] = 'dbuser';
    $this->dispatchUrI("/user/validentry");
    if(strpos($this->getBody(), 'false') === false)
      {
      $this->fail();
      } 
      
    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'user1@user1.com';
    $this->params['type'] = 'login';
    $this->params['password'] = 'wrong_password';
    $this->dispatchUrI("/user/validentry");
    if(strpos($this->getBody(), 'false') === false)
      {
      $this->fail();
      } 
      
    $this->resetAll();
    $this->params = array();
    $this->params['entry'] = 'user1@user1.com';
    $this->params['type'] = 'login';
    $this->params['password'] = 'test';
    $this->dispatchUrI("/user/validentry");
    if(strpos($this->getBody(), 'true') === false)
      {
      $this->fail();
      } 
    }

  }
