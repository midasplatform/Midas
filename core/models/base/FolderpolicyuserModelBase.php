<?php
abstract class FolderpolicyuserModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='folderpolicyuser';
    $this->_mainData=array(
          'folder_id'=>array('type'=>MIDAS_DATA),
          'user_id'=>array('type'=>MIDAS_DATA),
          'policy'=>array('type'=>MIDAS_DATA),
          'date'=>array('type'=>MIDAS_DATA),
          'folder'=>array('type'=>MIDAS_MANY_TO_ONE,'model'=>'Folder','parent_column'=>'folder_id','child_column'=>'folder_id'),
          'user'=>array('type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id')
        );
    $this->initialize(); // required
    } // end __construct()
  
  /** Abstract functions */
  abstract function getPolicy($user, $folder);  
    
  /** create a policy
   * @return FolderpolicyuserDao*/
  public function createPolicy($user, $folder, $policy)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$user->saved && !$folder->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($user, $folder)!==false)
      {
      $this->delete($this->getPolicy($user, $folder));
      }
    $this->loadDaoClass('FolderpolicyuserDao');
    $policyUser=new FolderpolicyuserDao();
    $policyUser->setUserId($user->getUserId());
    $policyUser->setFolderId($folder->getFolderId());
    $policyUser->setPolicy($policy);
    $this->save($policyUser);
    return $policyUser;
    }
  
} // end class FolderpolicyuserModelBase
?>