<?php
/**
 * \class Folderpolicygroup
 * \brief Cassandra Model
 */
class FolderpolicygroupModel extends MIDASFolderpolicygroupModel
{
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
    return $this->initDao('Folderpolicyuser',$this->database->fetchRow($this->database->select()
          ->where('folder_id = ?',$folder->getKey())
          ->where('user_id = ?',$user->getKey())
          ));
    }
} // end class
?>
