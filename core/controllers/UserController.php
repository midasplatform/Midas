<?php

class UserController extends AppController
  {
  public $_models=array(
    'User','Folder','Folderpolicygroup','Folderpolicyuser','Group','Feed','Feedpolicygroup','Feedpolicyuser','Group'
  );
  public $_daos=array(
    'User','Folder','Folderpolicygroup','Folderpolicyuser','Group'
  );
  public $_components=array('Date');
  public $_forms=array(
    'User'
  );

  /** Init Controller */
  function init()
    {
    $this->view->activemenu='user'; // set the active menu
    $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && is_numeric($actionName))
      {
      $this->_forward('userpage',null,null,array('user_id'=>$actionName));
      }
    } // end init()


  /** logout an user*/
  function logoutAction()
    {
    $this->userSession->Dao=null;
    Zend_Session::ForgetMe();
    $this->_forward('index','index');
    } //end logoutAction


  /** register an user*/
  function registerAction()
    {
    $form=$this->Form->User->createRegisterForm();
    if($this->_request->isPost() && $form->isValid($this->getRequest()->getPost()))
      {
      if($this->User->getByEmail(strtolower($form->getValue('email'))) !== false)
        {
        throw new Zend_Exception("User already exists.");
        }
        
      $this->userSession->Dao=$this->User->createUser($form->getValue('email'),$form->getValue('password1'),$form->getValue('firstname'),$form->getValue('lastname'));
      
      $this->_redirect("/");
      }
    $this->view->form=$this->getFormAsArray($form);
    $this->_helper->layout->disableLayout();
    $this->view->jsonRegister=JsonComponent::encode(array(
      'MessageNotValid'=>$this->t('The e-mail is not valid'),'MessageNotAvailable'=>$this->t('This e-mail is not available'),'MessagePassword'=>$this->t('Password too short'),'MessagePasswords'=>$this->t('The passwords are not the same'),'MessageLastname'=>$this->t('Please set your lastname'),'MessageTerms'=>$this->t('Please validate the terms of service'),'MessageFirstname'=>$this->t('Please set your firstname')
    ));
    } //end register


  /*check log in action*/
  function loginAction()
    {
    $this->Form->User->uri=$this->getRequest()->getRequestUri();
    $form=$this->Form->User->createLoginForm();
    $this->view->form=$this->getFormAsArray($form);
    $this->_helper->layout->disableLayout();
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $previousUri=$this->_getParam('previousuri');
      if($form->isValid($this->getRequest()->getPost()))
        {
        $userDao=$this->User->getByEmail($form->getValue('email'));
        $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
        if($userDao != false && md5($passwordPrefix.$form->getValue('password')) == $userDao->getPassword())
          {
          $this->userSession->Dao=$userDao;
          $remember=$form->getValue('remerberMe');

          if(isset($remember) && $remember == 1)
            {
            $seconds=60 * 60 * 24 * 14; // 14 days
            Zend_Session::RememberMe($seconds);
            }
          else
            {
            Zend_Session::ForgetMe();
            }
          $url=$form->getValue('url');
          $this->userSession->lock();
          $this->getLogger()->info(__METHOD__ . " Log in : " . $userDao->getFullName());
          }
        }

      if(isset($previousUri) && strpos($previousUri,$this->view->webroot) !== false && strpos($previousUri,"logout") === false)
        {
        $this->_redirect(substr($previousUri,strlen($this->view->webroot)));
        }
      else
        {
        $this->_redirect("/");
        }
      }
    } // end method login


  /** term of service */
  public function termofserviceAction()
    {
    if($this->getRequest()->isXmlHttpRequest())
      {
      $this->_helper->layout->disableLayout();
      }
    } // end term of service


  /** valid  entries (ajax)*/
  public function validentryAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here? Should be ajax.");
      }

    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $entry=$this->_getParam("entry");
    $type=$this->_getParam("type");
    if(!is_string($entry) || !is_string($type))
      {
      echo 'false';
      return;
      }
    switch ($type)
      {
      case 'dbuser' :
        $userDao=$this->User->getByEmail(strtolower($entry));
        if($userDao == !false)
          {
          echo "true";
          }
        else
          {
          echo "false";
          }
        return;
      case 'login' :        
        $password=$this->_getParam("password");
        if(!is_string($password))
          {
          echo 'false';
          return;
          }
        $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
        $userDao=$this->User->getByEmail($entry);
        if($userDao != false && md5($passwordPrefix.$password) == $userDao->getPassword())
          {
          echo 'true';
          return;
          }
      default :
        echo "false";
        return;
      }
    } //end valid entry

 /** settings page action*/
  public function settingsAction()
    {
    if(!$this->logged)
      {
      $this->_helper->viewRenderer->setNoRender();
      return false;
      }   
    $this->_helper->layout->disableLayout();
    
    }
  
  /** user page action*/
  public function userpageAction()
    {
    $this->view->Date=$this->Component->Date;
    $user_id=$this->_getParam("user_id");
    if(!isset($user_id) && !$this->logged)
      {
      $this->view->header=$this->t("You should be logged in.");
      $this->_helper->viewRenderer->setNoRender();
      return false;
      }
    elseif(!isset($user_id))
      {
      $userDao=$this->userSession->Dao;
      }
    else
      {
      $userDao=$this->User->load($user_id);
      }
      
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Unable to find user");
      }

    $this->view->user=$userDao;
    $this->view->userCommunities=$this->User->getUserCommunities($userDao);
    $this->view->folders=array();
    $this->view->folders[]=$userDao->getPublicFolder();
    if(!empty($this->userSession->Dao)&& $userDao->getKey() == $this->userSession->Dao->getKey())
      {
      $this->view->folders[]=$userDao->getPrivateFolder();
      }
    $this->view->feeds=$this->Feed->getFeedsByUser($this->userSession->Dao,$userDao);
    
    $this->view->information=array();
    }
  }//end class