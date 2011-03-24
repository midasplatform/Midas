<?php
class MIDASFeedpolicyuserModel extends MIDASModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name='feedpolicyuser';
    $this->_mainData=array(
        'feed_id'=>array(
        'type'=>MIDAS_DATA
      ),'user_id'=>array(
        'type'=>MIDAS_DATA
      ),'policy'=>array(
        'type'=>MIDAS_DATA
      ),'feed'=>array(
        'type'=>MIDAS_MANY_TO_ONE,'model'=>'Feed','parent_column'=>'feed_id','child_column'=>'feed_id'
      ),'user'=>array(
        'type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id'
      )
      );
    $this->initialize(); // required
    } // end __construct()  
  
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
  
} // end class MIDASFeedpolicyuserModel
?>