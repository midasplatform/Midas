<?php
/**
 * \class FolderpolicyuserModel
 * \brief Pdo Model
 */
class FolderpolicyuserModel extends AppModelPdo
  {
  public $_name='folderpolicyuser';

  public $_mainData=array(
    'folder_id'=>array(
    'type'=>MIDAS_DATA
  ),'user_id'=>array(
    'type'=>MIDAS_DATA
  ),'policy'=>array(
    'type'=>MIDAS_DATA
  ),'folder'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Folder','parent_column'=>'folder_id','child_column'=>'folder_id'
  ),'user'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id'
  )
  );

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

  /** getPolicy
   * @return FolderpolicyuserDao
   */
  public function getPolicy($user, $folder)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    return $this->initDao('Folderpolicyuser',$this->fetchRow($this->select()
          ->where('folder_id = ?',$folder->getKey())
          ->where('user_id = ?',$user->getKey())
          ));
    }
  }
?>
