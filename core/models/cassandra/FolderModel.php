<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

require_once BASE_PATH.'/core/models/base/FolderModelBase.php';

/**
 * \class FolderModel
 * \brief Cassandra Model
 */
class FolderModel extends FolderModelBase
{

  /** Get a folder by id */
  function getByFolder_id($folderid)
    {
    try
      {
      $folder = new ColumnFamily($this->database->getDB(), 'folder');
      $folderarray = $folder->get($folderid);

      // Add the user_id
      $folderarray[$this->_key] = $folderid;
      $dao = $this->initDao('Folder', $folderarray);
      }
    catch(cassandra_NotFoundException $e)
      {
      return false;
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e);
      }
    return $dao;
    } // end getByFolder_id()

  /** Add an item */
  function addItem($folder, $item)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    $this->database->link('items', $folder, $item);
    } // end function addItem

  /** Remove an item from a folder
   * @return void
   */
  function removeItem($folder, $item)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    //$this->database->removeLink('items', $folder, $item);
    } // end function addItem


  /** Custom save function*/
  public function save($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    /*
   if($folder->getParentId()<=0)
      {
      $rightParent = 0;
      }
    else
      {
      $parentFolder = $folder->getParent();
      $rightParent = $parentFolder->getRightIndice();
      }


    */

    $rightParent = 0;   // REMOVE ME

    $data = array();


    foreach($this->_mainData as $key => $var)
      {
      if(isset($folder->$key))
        {
        $data[$key] = $folder->$key;
        }
      if($key == 'right_indice')
        {
        $folder->$key = $rightParent + 1;
        $data[$key] = $rightParent + 1;
        }
      if($key == 'left_indice')
        {
        $data[$key] = $rightParent;
        }
      }

    if(isset($data['folder_id']))
      {
      $key = $data['folder_id'];
      unset($data['folder_id']);
      unset($data['left_indice']);
      unset($data['right_indice']);

      $column_family = new ColumnFamily($this->database->getDB(), 'folder');
      $column_family->insert($this->database->hex2bin($key), $data);
      return $key;
      }
    else
      {
      /*$this->_db->update('folder', array('right_indice'=> new Zend_Db_Expr('2 + right_indice')),
                          array('right_indice >= ?' => $rightParent));
      $this->_db->update('folder', array('left_indice'=> new Zend_Db_Expr('2 + left_indice')),
                          array('left_indice >= ?' => $rightParent));
      $insertedid = $this->insert($data);
      */
      $column_family = new ColumnFamily($this->database->getDB(), 'folder');
      $uuid = bin2hex(CassandraUtil::uuid1());
      $column_family->insert($uuid, $data);
      $folder->folder_id = $uuid;
      $folder->saved = true;
      return true;
      }
    } // end method save


  /** getFolder with policy check */
  function getChildrenFoldersFiltered($folder, $userDao = null, $policy = 0)
    {
    if(!isset($policy))
      {
      throw new Zend_Exception("Error parameter.");
      }
    if(is_array($folder))
      {
      $folderIds = array();
      foreach($folder as $f)
        {
        if(!$f instanceof FolderDao)
          {
          throw new Zend_Exception("Should be a folder.");
          }
        $folderIds[] = $f->getKey();
        }
      }
    else if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    else
      {
      $folderIds = array($folder->getKey());
      }
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    /*
    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('f' => 'folder'))
                          ->join(array('p' => 'folderpolicyuser'),
                                'f.folder_id = p.folder_id',
                                 array('p.policy'))
                          ->where ('f.parent_id IN (?)', $folderIds)
                          ->where('policy >= ?', $policy)
                          ->where('user_id = ? ', $userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'))
                    ->join(array('p' => 'folderpolicygroup'),
                          'f.folder_id = p.folder_id',
                           array('p.policy'))
                    ->where ('f.parent_id IN (?)', $folderIds)
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));
    $sql = $this->database->select()
            ->union(array($subqueryUser, $subqueryGroup));

    $rowset = $this->database->fetchAll($sql);
    */
    $return = array();
    /*$policyArray = array();
    foreach($rowset as $keyRow => $row)
      {
      if(!isset($policyArray[$row['folder_id']])||(isset($policyArray[$row['folder_id']])&&$row['policy']>$policyArray[$row['folder_id']]))
        {
        $policyArray[$row['folder_id']] = $row['policy'];
        }
      }

    foreach($rowset as $keyRow => $row)
      {
      if(isset($policyArray[$row['folder_id']]))
        {
        $tmpDao= $this->initDao('Folder', $row);
        $tmpDao->policy = $policyArray[$row['folder_id']];
        $return[] = $tmpDao;
        unset($policyArray[$row['folder_id']]);
        }
      }
   */
    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($return, array($this->Component->Sortdao, 'sortByName'));
    return $return;
    }


  /** getItems with policy check
   * @return
   */
  function getItemsFiltered($folder, $userDao = null, $policy = 0)
    {
    if(!isset($policy))
      {
      throw new Zend_Exception("Error parameter.");
      }
    if(is_array($folder))
      {
      $folderIds = array();
      foreach($folder as $f)
        {
        if(!$f instanceof FolderDao)
          {
          throw new Zend_Exception("Should be a folder.");
          }
        $folderIds[] = $f->getKey();
        }
      }
    else if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    else
      {
      $folderIds = array($folder->getKey());
      }
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    /*
    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('f' => 'item'))
                          ->join(array('p' => 'itempolicyuser'),
                                'f.item_id = p.item_id',
                                 array('p.policy'))
                          ->join(array('i' => 'item2folder'),
                                $this->database->getDB()->quoteInto('i.folder_id IN (?)', $folderIds).'
                                AND i.item_id = p.item_id' ,array('i.folder_id'))
                          ->where('policy >= ?', $policy)
                          ->where('user_id = ? ', $userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'item'))
                    ->join(array('p' => 'itempolicygroup'),
                          'f.item_id = p.item_id',
                           array('p.policy'))
                    ->join(array('i' => 'item2folder'),
                                $this->database->getDB()->quoteInto('i.folder_id IN (?)', $folderIds).'
                                AND i.item_id = p.item_id' ,array('i.folder_id'))
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->database->getDB()->quoteInto('p.group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              p.group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));

    $sql = $this->database->select()
            ->union(array($subqueryUser, $subqueryGroup));

    $rowset = $this->database->fetchAll($sql);
    */
    $return = array();
    /* $policyArray = array();
    foreach($rowset as $keyRow => $row)
      {

      if(!isset($policyArray[$row['item_id']])||(isset($policyArray[$row['item_id']])&&$row['policy']>$policyArray[$row['item_id']]))
        {
        $policyArray[$row['item_id']] = $row['policy'];
        }
      }
    foreach($rowset as $keyRow => $row)
      {
      if(isset($policyArray[$row['item_id']]))
        {
        $tmpDao= $this->initDao('Item', $row);
        $tmpDao->policy = $policyArray[$row['item_id']];
        $tmpDao->parent_id = $row['folder_id'];
        $return[] = $tmpDao;
        unset($policyArray[$row['item_id']]);
        }
      }

    $this->Component->Sortdao->field = 'name';
    $this->Component->Sortdao->order = 'asc';
    usort($return, array($this->Component->Sortdao, 'sortByName'));
     */
    return $return;
    }

  /** get the size and the number of item in a folder*/
  public function getSizeFiltered($folders, $userDao = null, $policy = 0)
    {
    if(!isset($policy))
      {
      throw new Zend_Exception("Error parameter.");
      }
    if(!is_array($folders))
      {
      $folders = array($folders);
      }
    if($userDao == null)
      {
      $userId = -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

   /*  foreach($folders as $key => $folder)
      {
      if(!$folder instanceof FolderDao)
        {
        throw new Zend_Exception("Should be a folder" );
        }

        $subqueryUser= $this->database->select()
                      ->setIntegrityCheck(false)
                      ->from(array('f' => 'folder'),array('folder_id'))
                      ->join(array('fpu' => 'folderpolicyuser'), '
                            f.folder_id = fpu.folder_id AND '.$this->database->getDB()->quoteInto('fpu.policy >= ?', $policy).'
                               AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ',array())
                      ->where('left_indice > ?', $folder->getLeftIndice())
                      ->where('right_indice < ?', $folder->getRightIndice());

      $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'),array('folder_id'))
                    ->join(array('fpg' => 'folderpolicygroup'), '
                                f.folder_id = fpg.folder_id  AND '.$this->database->getDB()->quoteInto('fpg.policy >= ?', $policy).'
                                   AND ( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                        group_id IN (' .new Zend_Db_Expr(
                                        $this->database->select()
                                             ->setIntegrityCheck(false)
                                             ->from(array('u2g' => 'user2group'),
                                                    array('group_id'))
                                             ->where('u2g.user_id = ?' , $userId)
                                             ) .'))' ,array())
                    ->where('left_indice > ?', $folder->getLeftIndice())
                    ->where('right_indice < ?', $folder->getRightIndice());

       $subSqlFolders = $this->database->select()
              ->union(array($subqueryUser, $subqueryGroup));

      $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from(array('i' => 'item'),array('sum' => 'sum(i.sizebytes)', 'count' => 'count(i.item_id)'))
                ->join(array('i2f' => 'item2folder'),
                         '( '.$this->database->getDB()->quoteInto('i2f.folder_id IN (?)', $subSqlFolders).'
                          OR i2f.folder_id = '.$folder->getKey().'
                          )
                          AND i2f.item_id = i.item_id'
                          ,array() )
                ->joinLeft(array('ip' => 'itempolicyuser'), '
                          i.item_id = ip.item_id AND '.$this->database->getDB()->quoteInto('policy >= ?', $policy).'
                             AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ',array())
                ->joinLeft(array('ipg' => 'itempolicygroup'), '
                                i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto('ipg.policy >= ?', $policy).'
                                   AND ( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                        group_id IN (' .new Zend_Db_Expr(
                                        $this->database->select()
                                             ->setIntegrityCheck(false)
                                             ->from(array('u2g' => 'user2group'),
                                                    array('group_id'))
                                             ->where('u2g.user_id = ?' , $userId)
                                             ) .'))' ,array())
                ->where(
                 '(
                  ip.item_id is not null or
                  ipg.item_id is not null)'
                  )
                ;


      $row = $this->database->fetchRow($sql);
      $folders[$key]->count = $row['count'];
      $folders[$key]->size = $row['sum'];
      if($folders[$key]->size == null)
        {
        $folders[$key]->size = 0;
        }
      }
      */
    return $folders;
    }

  /** Get community if the folder is the main folder of one*/
  function getCommunity($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }

    $folderid = $folder->getFolderId();
    $folderarray = $this->database->getCassandra('folder', $folderid, array('community_id'));
    if($folderarray !== false)
      {
      $communityid = $folderarray['community_id'];
      $communityarray = $this->database->getCassandra('community', $communityid);
      // Massage the data to the proper format
      $communityarray['community_id'] = $communityid;
      return $this->initDao('Community', $communityarray);
      }
    return false;
    }

  /** Get user if the folder is the main folder of one */
  function getUser($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $folderid = $folder->getFolderId();
    $folderarray = $this->database->getCassandra('folder', $folderid, array('user_id'));
    if($folderarray !== false)
      {
      $userid = $folderarray['user_id'];
      $userarray = $this->database->getCassandra('user', $userid);
      // Massage the data to the proper format
      $userarray['user_id'] = $userid;
      return $this->initDao('User', $userarray);
      }
    return false;
    }

} // end class FolderModel
?>
