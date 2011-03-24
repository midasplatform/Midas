<?php

class CommunityController extends AppController
  {
  public $_models=array('Community','Folder','Group','Folderpolicygroup','Group','User','Feed',"Feedpolicygroup","Feedpolicyuser");
  public $_daos=array('Community','Folder','Group','Folderpolicygroup','Group','User');
  public $_components=array('Sortdao','Date');
  public $_forms=array('Community');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'community'; // set the active menu
    $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && is_numeric($actionName))
      {
      $this->_forward('view',null,null,array('communityId'=>$actionName));
      }
    }  // end init()

    /** index*/
  function indexAction()
    {
    $this->view->header=$this->t("Communities");
    $this->view->json['community']['createCommunity']=$this->t('Create a community');
    $this->view->json['community']['titleCreateLogin']=$this->t('Please log in');
    $this->view->json['community']['contentCreateLogin']=$this->t('You need to be logged in to be able to create a community.');

    $communities=$this->User->getUserCommunities($this->userSession->Dao);
    $communities=array_merge($communities, $this->Community->getPublicCommunities());
    $this->Component->Sortdao->field='name';
    $this->Component->Sortdao->order='asc';
    usort($communities, array($this->Component->Sortdao,'sortByName'));
    $communities=$this->Component->Sortdao->arrayUniqueDao($communities );
    
    $this->view->userCommunities=$communities;
    }//end index

  /** view a community*/
  function viewAction()
    {
    $this->view->Date=$this->Component->Date;
    //TODO: add policy check
    $communityId=$this->_getParam("communityId");
    if(!isset($communityId)||!is_numeric($communityId))
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    $communityDao=$this->Community->load($communityId);
    if($communityDao===false)
      {
      throw new Zend_Exception("This community doesn't exist.");
      }

    $this->view->communityDao=$communityDao;
    $this->view->information=array();
    $this->view->feeds=$this->Feed->getFeedsByCommunity($this->userSession->Dao,$communityDao);
    
    $this->view->folders=array();
    $this->view->folders[]=$communityDao->getPublicFolder();
    $this->view->folders[]=$communityDao->getPrivateFolder();
    }//end index


  /** create a community (ajax)*/
  function createAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
     $form=$this->Form->Community->createCreateForm();
     if($this->_request->isPost()&&$form->isValid($this->getRequest()->getPost()))
       {
       if($this->Community->getByName($form->getValue('name'))!==false)
         {
         throw new Zend_Exception("Community already exists.");
         }
       $communityDao=new CommunityDao();
       $communityDao->setName(ucfirst($form->getValue('name')));
       $communityDao->setDescription($form->getValue('description'));
       $communityDao->setPrivacy($form->getValue('privacy'));
       $communityDao->setCreation(date('c'));
       $this->Community->save($communityDao);

       $folderGlobal=$this->Folder->createFolder('community_'.$communityDao->getKey(),'Main folder of the community '.$communityDao->getKey(),MIDAS_FOLDER_COMMUNITYPARENT);
       $folderPublic=$this->Folder->createFolder('Public','Public folder of the community '.$communityDao->getKey(),$folderGlobal);
       $folderPrivate=$this->Folder->createFolder('Private','Private folder of the community '.$communityDao->getKey(),$folderGlobal);

       $adminGroup=$this->Group->createGroup($communityDao,'Admin group of community '.$communityDao->getKey());
       $moderatorsGroup=$this->Group->createGroup($communityDao,'Moderators group of community '.$communityDao->getKey());
       $memberGroup=$this->Group->createGroup($communityDao,'Members group of community '.$communityDao->getKey());
       $anonymousGroup=$this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);

       $communityDao->setFolderId($folderGlobal->getKey());
       $communityDao->setPublicfolderId($folderPublic->getKey());
       $communityDao->setPrivatefolderId($folderPrivate->getKey());
       $communityDao->setAdmingroupId($adminGroup->getKey());
       $communityDao->setModeratorgroupId($moderatorsGroup->getKey());
       $communityDao->setMembergroupId($memberGroup->getKey());
       $this->Community->save($communityDao);

       $this->Group->addUser($adminGroup,$this->userSession->Dao);
       $this->Group->addUser($memberGroup,$this->userSession->Dao);

       $feed=$this->Feed->createFeed($this->userSession->Dao,MIDAS_FEED_CREATE_COMMUNITY,$communityDao,$communityDao);
       $this->Feedpolicyuser->createPolicy($this->userSession->Dao,$feed,MIDAS_POLICY_ADMIN);

       $this->Folderpolicygroup->createPolicy($adminGroup,$folderGlobal,MIDAS_POLICY_ADMIN);
       $this->Folderpolicygroup->createPolicy($adminGroup,$folderPublic,MIDAS_POLICY_ADMIN);
       $this->Folderpolicygroup->createPolicy($adminGroup,$folderPrivate,MIDAS_POLICY_ADMIN);
       $this->Feedpolicygroup->createPolicy($adminGroup,$feed,MIDAS_POLICY_ADMIN);

       $this->Folderpolicygroup->createPolicy($moderatorsGroup,$folderGlobal,MIDAS_POLICY_READ);
       $this->Folderpolicygroup->createPolicy($moderatorsGroup,$folderPublic,MIDAS_POLICY_WRITE);
       $this->Folderpolicygroup->createPolicy($moderatorsGroup,$folderPrivate,MIDAS_POLICY_WRITE);
       $this->Feedpolicygroup->createPolicy($moderatorsGroup,$feed,MIDAS_POLICY_ADMIN);

       $this->Folderpolicygroup->createPolicy($memberGroup,$folderGlobal,MIDAS_POLICY_READ);
       $this->Folderpolicygroup->createPolicy($memberGroup,$folderPublic,MIDAS_POLICY_WRITE);
       $this->Folderpolicygroup->createPolicy($memberGroup,$folderPrivate,MIDAS_POLICY_WRITE);
       $this->Feedpolicygroup->createPolicy($memberGroup,$feed,MIDAS_POLICY_READ);

       if($communityDao->getPrivacy()!=MIDAS_COMMUNITY_PRIVATE)
         {
         $this->Folderpolicygroup->createPolicy($anonymousGroup,$folderPublic,MIDAS_POLICY_READ);
         $this->Feedpolicygroup->createPolicy($anonymousGroup,$feed,MIDAS_POLICY_READ);
         }

       $this->_redirect("/community");
       }
     else
       {
       if(!$this->getRequest()->isXmlHttpRequest())
         {
         throw new Zend_Exception("Why are you here ? Should be ajax.");
         }
       $this->_helper->layout->disableLayout();
       $this->view->form=$this->getFormAsArray($form);
       }
     }//end index



         /** valid  entries (ajax)*/
    public function validentryAction()
      {
      if(!$this->getRequest()->isXmlHttpRequest())
         {
         throw new Zend_Exception("Why are you here ? Should be ajax.");
         }

      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $entry=$this->_getParam("entry");
      $type=$this->_getParam("type");
      if(!is_string($entry)||!is_string($type))
        {
        echo 'false';
        return;
        }
      switch ($type)
        {
        case 'dbcommunityname':
          $communityDao=$this->Community->getByName($entry);
          if ($communityDao==!false)
            {
            echo "true";
            }
          else
            {
            echo "false";
            }
          return;
        default:
          echo "false";
          return;
        }
      } //end valid entry
  }//end class