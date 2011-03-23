<?php
/**
 * \class FolderpolicygroupModel
 * \brief Pdo Model
 */
class FolderpolicygroupModel extends MIDASFolderpolicygroupModel
{
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
    $this->database->save($policyGroupDao);
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
    return $this->initDao('Folderpolicygroup',$this->database->fetchRow($this->database->select()->where('folder_id = ?',$folder->getKey())->where('group_id = ?',$group->getKey())));
    }
}
?>
