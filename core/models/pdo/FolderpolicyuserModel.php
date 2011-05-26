<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

require_once BASE_PATH.'/core/models/base/FolderpolicyuserModelBase.php';

/**
 * \class FolderpolicyuserModel
 * \brief Pdo Model
 */
class FolderpolicyuserModel extends FolderpolicyuserModelBase
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
    return $this->initDao('Folderpolicyuser', $this->database->fetchRow($this->database->select()
          ->where('folder_id = ?', $folder->getKey())
          ->where('user_id = ?', $user->getKey())
          ));
    }
}  // end class FolderpolicyuserModel
?>
