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
 * \class Folderpolicyuser
 * \brief Cassandra Model
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

    $folderid = $folder->getKey();
    $userid = $user->getKey();

    $folder = new ColumnFamily($this->database->getDB(), 'folder');
    $column = 'user_'.$userid;
    $folderarray = $this->database->getCassandra('folder', $folderid, array($column));

    if(empty($folderarray))
      {
      return null;
      }

    // Massage the data to the proper format
    $newarray['folder_id'] = $folderid;
    $newarray['user_id'] = $userid;
    $newarray['policy'] = $folderarray[$column];

    return $this->initDao('Folderpolicyuser', $newarray);
    } // end getPolicy

  /** Custom save command */
  public function save($dao)
    {
    $instance = $this->_name."Dao";
    if(!$dao instanceof $instance)
      {
      throw new Zend_Exception("Should be an object (".$instance.").");
      }

    try
      {
      $folderid = $dao->getFolderId();
      $userid = $dao->getUserId();
      $column = 'user_'.$userid;

      $dataarray = array();
      $dataarray[$column] = $dao->getPolicy();

      $column_family = new ColumnFamily($this->database->getDB(), 'folder');
      $column_family->insert($folderid, $dataarray);
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }

    $dao->saved = true;
    return true;
    } // end save()

  /** Custome delete command */
  public function delete($dao)
    {
    // No DAO passed we just return
    if($dao == null)
      {
      return false;
      }

    $instance = ucfirst($this->_name)."Dao";
    if(get_class($dao) !=  $instance)
      {
      throw new Zend_Exception("Should be an object (".$instance."). It was: ".get_class($dao) );
      }
    if(!$dao->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }

    try
      {
      // Remove the column user from the folder
      $folderid = $dao->getFolderId();
      $userid = $dao->getUserId();
      $column = 'user_'.$userid;
      $cf = new ColumnFamily($this->database->getDB(), 'folder');
      $cf->remove($folderid, array($column));
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    $dao->saved = false;
    return true;
    } // end delete()

} // end class
