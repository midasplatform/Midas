<?php

class UserController extends AppController
  {
  public $_models=array(
    'User','Folder','Folderpolicygroup','Folderpolicyuser','Group','Feed','Feedpolicygroup','Feedpolicyuser','Group'
  );
  public $_daos=array(
    'User','Folder','Folderpolicygroup','Folderpolicyuser','Group'
  );
  public $_components=array('Date','Filter');
  public $_forms=array(
    'User'
  );

  /** Init Controller */
  function init()
    {
    $this->view->activemenu='user'; // set the active menu
    $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && (is_numeric($actionName) || strlen($actionName)==32)) // This is tricky! and for Cassandra for now
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
        
      $this->userSession->Dao=$this->User->createUser(trim($form->getValue('email')),$form->getValue('password1'),trim($form->getValue('firstname')),trim($form->getValue('lastname')));
      
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
          Zend_Session::start();  
          $user=new Zend_Session_Namespace('Auth_User');
          $user->setExpirationSeconds(60*Zend_Registry::get('configGlobal')->session->lifetime);
          $user->Dao=$userDao;
          $url=$form->getValue('url');
          $user->lock();
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
    
    $userId=$this->_getParam('userId');
    if(isset($userId)&&!$this->userSession->Dao->isAdmin())
      {
      throw new Zend_Exception('You should be an admin');
      }
    else if (isset($userId))
      {
      $userDao=$this->User->load($userId);
      }
    else
      {
      $userDao=$this->userSession->Dao;
      }
      
    if(empty($userDao)||$userDao==false)
      {
      throw new Zend_Exception("Unable to load user");
      }
    
    $accountForm=$this->Form->User->createAccountForm($userDao->getFirstname(),$userDao->getLastname(),
                                                $userDao->getCompany(),$userDao->getPrivacy());
    $this->view->accountForm=$this->getFormAsArray($accountForm);
    
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $submitPassword=$this->_getParam('modifyPassword');
      $modifyAccount=$this->_getParam('modifyAccount');
      $modifyPicture=$this->_getParam('modifyPicture');
      if(isset($submitPassword)&&$this->logged)
        {
        $oldPass=$this->_getParam('oldPassword');
        $newPass=$this->_getParam('newPassword');
        $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
        $userDao=$this->User->load($userDao->getKey());
        if($userDao != false && ((!$userDao->isAdmin()&&$this->userSession->Dao->isAdmin())||md5($passwordPrefix.$oldPass) == $userDao->getPassword()))
          {
          $userDao->setPassword(md5($passwordPrefix.$newPass));
          $this->User->save($userDao);
          if(!isset($userId))
            {
            $this->userSession->Dao=$userDao;
            }
          echo JsonComponent::encode(array(true,$this->t('Changes saved')));
          }
        else
          {
          echo JsonComponent::encode(array(false,$this->t('The old password is incorrect')));
          }
        }
        
      if(isset($modifyAccount)&&$this->logged)
        {
        $firtname=trim($this->_getParam('firstname'));
        $lastname=trim($this->_getParam('lastname'));
        $company=trim($this->_getParam('company'));
        $privacy=$this->_getParam('privacy');
        
        $userDao=$this->User->load($userDao->getKey());

        if(!isset($privacy)||($privacy!=MIDAS_USER_PRIVATE&&$privacy!=MIDAS_USER_PUBLIC))
          {
          echo JsonComponent::encode(array(false,'Error'));
          }
        if(!isset($lastname)||!isset($firtname)||empty($lastname)||empty($firtname))
          {
          echo JsonComponent::encode(array(false,'Error'));
          }
        $userDao->setFirstname($firtname);
        $userDao->setLastname($lastname);
        if(isset($company))
          {
          $userDao->setCompany($company);
          }        
        $userDao->setPrivacy($privacy);
        $this->User->save($userDao);
        if(!isset($userId))
          {
          $this->userSession->Dao=$userDao;
          }     
        echo JsonComponent::encode(array(true,$this->t('Changes saved')));
        }
      if(isset($modifyPicture)&&$this->logged)
        {
        $upload = new Zend_File_Transfer();
        $upload->receive();
        $path=$upload->getFileName();
        if (!empty($path)&& file_exists($path) && $upload->getFileSize() > 0)
          {
          //create thumbnail
          $thumbnailCreator=$this->Component->Filter->getFilter('ThumbnailCreator');
          $thumbnailCreator->inputFile = $path;
          $thumbnailCreator->inputName = basename($path);
          $hasThumbnail = $thumbnailCreator->process();
          $thumbnail_output_file = $thumbnailCreator->outputFile;
          if($hasThumbnail&&  file_exists($thumbnail_output_file))
            {
            $userDao=$this->User->load($userDao->getKey());
            $oldThumbnail=$userDao->getThumbnail();
            if(!empty($oldThumbnail))
              {
              unlink(BASE_PATH.'/'.$oldThumbnail);
              }
            $userDao->setThumbnail(substr($thumbnail_output_file, strlen(BASE_PATH)+1));
            $this->User->save($userDao);
            if(!isset($userId))
              {
              $this->userSession->Dao=$userDao;
              }   
            echo JsonComponent::encode(array(true,$this->t('Changes saved'),$userDao->getThumbnail()));
            }   
          else
            {
            echo JsonComponent::encode(array(false,'Error'));
            }
          }
        }
      }
    
    $this->view->user=$userDao;
    $this->view->thumbnail=$userDao->getThumbnail();
    $this->view->jsonSettings=array();
    $this->view->jsonSettings['accountErrorFirstname']=$this->t('Please set your firstname');
    $this->view->jsonSettings['accountErrorLastname']=$this->t('Please set your lastname');
    $this->view->jsonSettings['passwordErrorShort']=$this->t('Password too short');
    $this->view->jsonSettings['passwordErrorMatch']=$this->t('The passwords are not the same');
    $this->view->jsonSettings=JsonComponent::encode($this->view->jsonSettings);
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
    else
      {
      $this->User->plusOneView($userDao);
      }
    $this->view->feeds=$this->Feed->getFeedsByUser($this->userSession->Dao,$userDao);
    
    $this->view->isViewAction=($this->logged&&($this->userSession->Dao->getKey()==$userDao->getKey()||$this->userSession->Dao->isAdmin()));
    $this->view->information=array();
    }
  }//end class