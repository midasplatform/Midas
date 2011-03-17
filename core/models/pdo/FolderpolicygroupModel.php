<?php
/**
 * \class FolderpolicygroupModel
 * \brief Pdo Model
 */
class FolderpolicygroupModel extends AppModelPdo
  {
  public $_name='folderpolicygroup';

  public $_mainData=array(
    'folder_id'=>array(
    'type'=>MIDAS_DATA
  ),'group_id'=>array(
    'type'=>MIDAS_DATA
  ),'policy'=>array(
    'type'=>MIDAS_DATA
  ),'folder'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Folder','parent_column'=>'folder_id','child_column'=>'folder_id'
  ),'group'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Group','parent_column'=>'group_id','child_column'=>'group_id'
  )
  );

  /** create a policy
   * @return FolderpolicygroupDao*/
  public function createPolicy($group, $folder, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$folder->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group,$folder) !== false)
      {
      $this->delete($this->getPolicy($group,$folder));
      }
    $this->loadDaoClass('FolderpolicygroupDao');
    $policyGroupDao=new FolderpolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setFolderId($folder->getFolderId());
    $policyGroupDao->setPolicy($policy);
    $this->save($policyGroupDao);
    return $policyGroupDao;
    }

  /** getPolicy
   * @return FolderpolicygroupDao
   */
  public function getPolicy($group, $folder)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    return $this->initDao('Folderpolicygroup',$this->fetchRow($this->select()->where('folder_id = ?',$folder->getKey())->where('group_id = ?',$group->getKey())));
    }
  }
?>
