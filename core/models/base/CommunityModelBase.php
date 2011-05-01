<?php
abstract class CommunityModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();  
    $this->_name = 'community';
    $this->_key = 'community_id';
    $this->_mainData = array(
      'community_id' => array('type' => MIDAS_DATA),
      'name' => array('type' => MIDAS_DATA),
      'description' => array('type' => MIDAS_DATA),
      'creation' => array('type' => MIDAS_DATA),
      'privacy' => array('type' => MIDAS_DATA),
      'folder_id' => array('type' => MIDAS_DATA),
      'publicfolder_id' => array('type' => MIDAS_DATA),
      'privatefolder_id' => array('type' => MIDAS_DATA),
      'admingroup_id' => array('type' => MIDAS_DATA),
      'moderatorgroup_id' => array('type' => MIDAS_DATA),
      'membergroup_id' => array('type' => MIDAS_DATA),
      'view' => array('type' => MIDAS_DATA),
      'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id'),
      'public_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'publicfolder_id', 'child_column' => 'folder_id'),
      'private_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'privatefolder_id', 'child_column' => 'folder_id'),
      'admin_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'admingroup_id', 'child_column' => 'group_id'),
      'moderator_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'moderatorgroup_id', 'child_column' => 'group_id'),
      'member_group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'membergroup_id', 'child_column' => 'group_id'),
      'feeds' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Feed', 'table' => 'feed2community', 'parent_column'=> 'community_id', 'child_column' => 'feed_id'),
      );
    $this->initialize(); // required  
    } // end __construct()
  
  abstract function getPublicCommunities($limit=20);
  abstract function getByName($name);
  abstract function getAll();

  
  /** plus one view*/
  function incrementViewCount($communityDao)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $communityDao->view++;
    $this->save($communityDao);
    } //end incrementViewCount
  
  /** Create a community.
   *  privacy: MIDAS_COMMUNITY_PUBLIC, MIDAS_COMMUNITY_SEMIPRIVATE, MIDAS_COMMUNITY_PRIVATE
   *  */
  function createCommunity($name,$description,$privacy,$user)
    {
    $name = ucfirst($name);  
    if($this->getByName($name)!==false)
      {
      throw new Zend_Exception("Community already exists.");
      }
      
    $this->loadDaoClass('CommunityDao');       
    $communityDao=new CommunityDao();
    $communityDao->setName($name);
    $communityDao->setDescription($description);
    $communityDao->setPrivacy($privacy);
    $communityDao->setCreation(date('c'));
    $this->save($communityDao);

    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');
    $groupModel = $modelLoad->loadModel('Group');
    $feedModel = $modelLoad->loadModel('Feed');
    $folderpolicygroupModel = $modelLoad->loadModel('Folderpolicygroup');
    $feedpolicyuserModel = $modelLoad->loadModel('Feedpolicyuser');
    $feedpolicygroupModel = $modelLoad->loadModel('Feedpolicygroup');
    
    $folderGlobal=$folderModel->createFolder('community_'.$communityDao->getKey(),'Main folder of the community '.$communityDao->getKey(),MIDAS_FOLDER_COMMUNITYPARENT);
    $folderPublic=$folderModel->createFolder('Public','Public folder of the community '.$communityDao->getKey(),$folderGlobal);
    $folderPrivate=$folderModel->createFolder('Private','Private folder of the community '.$communityDao->getKey(),$folderGlobal);

    $adminGroup=$groupModel->createGroup($communityDao,'Admin group of community '.$communityDao->getKey());
    $moderatorsGroup=$groupModel->createGroup($communityDao,'Moderators group of community '.$communityDao->getKey());
    $memberGroup=$groupModel->createGroup($communityDao,'Members group of community '.$communityDao->getKey());
    $anonymousGroup=$groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);

    $communityDao->setFolderId($folderGlobal->getKey());
    $communityDao->setPublicfolderId($folderPublic->getKey());
    $communityDao->setPrivatefolderId($folderPrivate->getKey());
    $communityDao->setAdmingroupId($adminGroup->getKey());
    $communityDao->setModeratorgroupId($moderatorsGroup->getKey());
    $communityDao->setMembergroupId($memberGroup->getKey());
    $this->save($communityDao);

    if($user != NULL)
      {
      $groupModel->addUser($adminGroup,$user);
      $groupModel->addUser($memberGroup,$user);

      $feed=$feedModel->createFeed($user,MIDAS_FEED_CREATE_COMMUNITY,$communityDao,$communityDao);
      $feedpolicyuserModel->createPolicy($user,$feed,MIDAS_POLICY_ADMIN);
      $feedpolicygroupModel->createPolicy($adminGroup,$feed,MIDAS_POLICY_ADMIN);
      $feedpolicygroupModel->createPolicy($moderatorsGroup,$feed,MIDAS_POLICY_ADMIN);
      $feedpolicygroupModel->createPolicy($memberGroup,$feed,MIDAS_POLICY_READ);
      }
      
    $folderpolicygroupModel->createPolicy($adminGroup,$folderGlobal,MIDAS_POLICY_ADMIN);
    $folderpolicygroupModel->createPolicy($adminGroup,$folderPublic,MIDAS_POLICY_ADMIN);
    $folderpolicygroupModel->createPolicy($adminGroup,$folderPrivate,MIDAS_POLICY_ADMIN);
    

    $folderpolicygroupModel->createPolicy($moderatorsGroup,$folderGlobal,MIDAS_POLICY_READ);
    $folderpolicygroupModel->createPolicy($moderatorsGroup,$folderPublic,MIDAS_POLICY_WRITE);
    $folderpolicygroupModel->createPolicy($moderatorsGroup,$folderPrivate,MIDAS_POLICY_WRITE);
    

    $folderpolicygroupModel->createPolicy($memberGroup,$folderGlobal,MIDAS_POLICY_READ);
    $folderpolicygroupModel->createPolicy($memberGroup,$folderPublic,MIDAS_POLICY_WRITE);
    $folderpolicygroupModel->createPolicy($memberGroup,$folderPrivate,MIDAS_POLICY_WRITE);
    

    if($communityDao->getPrivacy()!=MIDAS_COMMUNITY_PRIVATE)
      {
      $folderpolicygroupModel->createPolicy($anonymousGroup,$folderPublic,MIDAS_POLICY_READ);
      if($user != NULL)
        {
        $feedpolicygroupModel->createPolicy($anonymousGroup,$feed,MIDAS_POLICY_READ);
        }
      } 
    return $communityDao;
    } // end createCommunity()    
    
    
  /** Delete a community */
  function delete($communityDao)
    {
    if(!$communityDao instanceof CommunityDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $this->ModelLoader = new MIDAS_ModelLoader();
    $group_model=$this->ModelLoader->loadModel('Group');
    $groups=$group_model->findByCommunity($communityDao);
    foreach($groups as $group)
      {
      $group_model->delete($group);
      }
    
    $folder_model=$this->ModelLoader->loadModel('Folder');
    $folder=$communityDao->getFolder();
    $folder_model->delete($folder,true);
    
    $feed_model=$this->ModelLoader->loadModel('Feed');
    $feeds=$communityDao->getFeeds();
    foreach($feeds as $feed)
      {
      $feed_model->delete($feed);
      }
    parent::delete($communityDao);
    unset($communityDao->community_id);
    $communityDao->saved=false;
    }//end delete
    
  
   /** check if the policy is valid
   *
   * @param FolderDao $folderDao
   * @param UserDao $userDao
   * @param type $policy
   * @return  boolean 
   */
  function policyCheck($communityDao,$userDao=null,$policy=0)
    {
    if(!$communityDao instanceof CommunityDao||!is_numeric($policy))
      {
      throw new Zend_Exception("Error param.");
      }
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      if($userDao->isAdmin())
        {
        return true;
        }
      }
    
    $privacy=$communityDao->getPrivacy();    
    switch ($policy)
      {
      case MIDAS_POLICY_READ:
        if($privacy!=MIDAS_COMMUNITY_PRIVATE)
          {
          return true;
          }
        else if ($userId==-1)
          {
          return false;
          }
        else
          {
          $user_groups=$userDao->getGroups();
          $member_group=$communityDao->getMemberGroup();
          foreach($user_groups as $group)
            {
            if($group->getKey()==$member_group->getKey())
              {
              return true;
              }
            }
          return false;
          }
        break;
      case MIDAS_POLICY_WRITE:
        if ($userId==-1)
          {
          return false;
          }
        else
          {
          $user_groups=$userDao->getGroups();
          $moderator_group=$communityDao->getModeratorGroup();
          $admin_group=$communityDao->getAdminGroup();
          foreach($user_groups as $group)
            {
            if($group->getKey()==$moderator_group->getKey()|| $group->getKey()==$admin_group->getKey())
              {
              return true;
              }
            }
          return false;
          }
        break;
      case MIDAS_POLICY_ADMIN:
        if ($userId==-1)
          {
          return false;
          }
        else
          {
          $user_groups=$userDao->getGroups();
          $admin_group=$communityDao->getAdminGroup();
          foreach($user_groups as $group)
            {
            if($group->getKey()==$admin_group->getKey())
              {
              return true;
              }
            }
          return false;
          }
        break;
       default:
         return false;
         break;
      }
   
    if(count($rowset)>0)
      {
      return true;
      }
    return false;
    } //end policyCheck
  
} // end class CommunityModelBase
?>