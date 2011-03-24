<?php
require_once BASE_PATH.'/core/models/base/FolderpolicygroupModelBase.php';

/**
 * \class FolderpolicygroupModel
 * \brief Pdo Model
 */
class FolderpolicygroupModel extends FolderpolicygroupModelBase
{
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
