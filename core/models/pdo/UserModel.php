<?php

/**
 *  UserModel
 *  Pdo Model
 */
class UserModel extends AppModelPdo
  {
  public $_name = 'user';
  public $_key = 'user_id';
  public $_mainData = array(
    'user_id' => array('type' => MIDAS_DATA),
    'firstname' => array('type' => MIDAS_DATA),
    'lastname' => array('type' => MIDAS_DATA),
    'email' => array('type' => MIDAS_DATA),
    'thumbnail' => array('type' => MIDAS_DATA),
    'company' => array('type' => MIDAS_DATA),
    'password' => array('type' => MIDAS_DATA),
    'creation' => array('type' => MIDAS_DATA),
    'folder_id' => array('type' => MIDAS_DATA),
    'admin' => array('type' => MIDAS_DATA),    
    'publicfolder_id' => array('type' => MIDAS_DATA),
    'privatefolder_id' => array('type' => MIDAS_DATA),
    'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id'),
    'public_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'publicfolder_id', 'child_column' => 'folder_id'),
    'private_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'privatefolder_id', 'child_column' => 'folder_id'),
    'groups' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Group', 'table' => 'user2group', 'parent_column'=> 'user_id', 'child_column' => 'group_id'),
    'folderpolicyuser' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Folderpolicyuser', 'parent_column'=> 'user_id', 'child_column' => 'user_id'),
    'feeds' => array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'Feed', 'parent_column'=> 'user_id', 'child_column' => 'user_id'),

  );

    
    
  /** Get user communities */
  public function getUserCommunities($userDao)
    {
    if($userDao==null)
      {
      return array();
      }
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    $sql = $this->select()
          ->setIntegrityCheck(false)
          ->from('community')
          ->where('membergroup_id IN (' .new Zend_Db_Expr(
                                  $this->select()
                                       ->setIntegrityCheck(false)
                                       ->from(array('u2g' => 'user2group'),
                                              array('group_id'))
                                       ->where('u2g.user_id = ?' , $userDao->getUserId())
                                       .')' )
                 );
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Community', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getUserCommunities

  /** Return a list of users corresponding to the search */
  function getUsersFromSearch($search,$userDao)
    {
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
      }

    // Check that the user belong to the same group
    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('g1' => 'user2group'),
                                 array('count(*)'))
                          ->joinLeft(array('g2' => 'user2group'),
                                     'g1.group_id=g2.group_id',array())
                          ->where('g1.user_id=u.user_id')
                          ->where('g2.user_id= ? ',$userId);


    $sql = $this->select()->from(array('u'=>$this->_name),array('user_id','firstname','lastname','count(*)'))
                                          ->where('(privacy='.MIDAS_USER_PUBLIC.' OR ('.
                                          $subqueryUser.')>0'.') AND ('.
                                          $this->_db->quoteInto('firstname LIKE ?','%'.$search.'%').' OR '.
                                          $this->_db->quoteInto('lastname LIKE ?','%'.$search.'%').')')
                                          ->group(array('firstname','lastname'))
                                          ->limit(14)
                                          ->setIntegrityCheck(false);

    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = new UserDao();
      $tmpDao->count = $row['count(*)'];
      $tmpDao->setFirstname($row->firstname);
      $tmpDao->setLastname($row->lastname);
      $tmpDao->setUserId($row->user_id);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getUsersFromSearch()

    
  /** don't use save*/
  public function save($dao)
    {
    throw new Zend_Exception("Use createUser method.");
    }

 /** create user */
  public function createUser($email,$password,$firstname,$lastname,$admin=0)
    {    
    if(!is_string($email)||empty($email)||!is_string($password)||empty($password)||!is_string($firstname)
        ||empty($firstname)||!is_string($lastname)||empty($lastname)||!is_numeric($admin))
      {
      throw new Zend_Exception("Error Parameters.");
      }
    Zend_Loader::loadClass('UserDao', BASE_PATH.'/core/models/dao');
    $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
    if(isset($passwordPrefix)&&!empty($passwordPrefix))
      {
      $password=$passwordPrefix.$password;
      }
    $userDao=new UserDao();
    $userDao->setFirstname(ucfirst($firstname));
    $userDao->setLastname(ucfirst($lastname));
    $userDao->setEmail(strtolower($email));
    $userDao->setCreation(date('c'));
    $userDao->setPassword(md5($password));
    $userDao->setAdmin($admin);
    parent::save($userDao);

    $this->ModelLoader = new MIDAS_ModelLoader();
    $groupModel=$this->ModelLoader->loadModel('Group');
    $folderModel=$this->ModelLoader->loadModel('Folder');
    $folderpolicygroupModel=$this->ModelLoader->loadModel('Folderpolicygroup');
    $folderpolicyuserModel=$this->ModelLoader->loadModel('Folderpolicyuser');
    $feedModel=$this->ModelLoader->loadModel('Feed');
    $feedpolicygroupModel=$this->ModelLoader->loadModel('Feedpolicygroup');
    $feedpolicyuserModel=$this->ModelLoader->loadModel('Feedpolicyuser');
    
    $anonymousGroup=$groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);

    $folderGlobal=$folderModel->createFolder('user_' . $userDao->getKey(),'Main folder of ' . $userDao->getFullName(),MIDAS_FOLDER_USERPARENT);
    $folderPrivate=$folderModel->createFolder('Private','Private folder of ' . $userDao->getFullName(),$folderGlobal);
    $folderPublic=$folderModel->createFolder('Public','Public folder of ' . $userDao->getFullName(),$folderGlobal);

    $folderpolicygroupModel->createPolicy($anonymousGroup,$folderPublic,MIDAS_POLICY_READ);
    $folderpolicyuserModel->createPolicy($userDao,$folderPrivate,MIDAS_POLICY_ADMIN);
    $folderpolicyuserModel->createPolicy($userDao,$folderGlobal,MIDAS_POLICY_ADMIN);
    $folderpolicyuserModel->createPolicy($userDao,$folderPublic,MIDAS_POLICY_ADMIN);

    $userDao->setFolderId($folderGlobal->getKey());
    $userDao->setPublicfolderId($folderPublic->getKey());
    $userDao->setPrivatefolderId($folderPrivate->getKey());

    parent::save($userDao);
    $this->getLogger()->info(__METHOD__ . " Registration: " . $userDao->getFullName() . " " . $userDao->getKey());

    $feed=$feedModel->createFeed($userDao,MIDAS_FEED_CREATE_USER,$userDao);
    $feedpolicygroupModel->createPolicy($anonymousGroup,$feed,MIDAS_POLICY_READ);
    $feedpolicyuserModel->createPolicy($userDao,$feed,MIDAS_POLICY_ADMIN);    
    return $userDao;
    }
 
}// end class
?>