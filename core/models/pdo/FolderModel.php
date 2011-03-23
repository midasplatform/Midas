<?php
/**
 * \class FolderModel
 * \brief Pdo Model
 */
class FolderModel extends MIDASFolderModel
{
  /** check if the policy is valid*/
  function policyCheck($folderDao,$userDao=null,$policy=0)
    {
    if(!$folderDao instanceof  FolderDao||!is_numeric($policy))
      {
      throw new Zend_Exception("Error param.");
      }
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
      
     $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'folderpolicyuser'),
                                 array('folder_id'))
                          ->where('policy >= ?', $policy)
                          ->where('p.folder_id >= ?', $folderDao->getKey())
                          ->where('user_id = ? ',$userId);

     $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'folderpolicygroup'),
                           array('folder_id'))
                    ->where('policy >= ?', $policy)
                    ->where('p.folder_id >= ?', $folderDao->getKey())
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
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
    if(count($rowset)>0)
      {
      return true;
      }
    return false;
    }//end policyCheck
    
  /** get the size and the number of item in a folder*/
  public function getSizeFiltered($folders,$userDao=null,$policy=0)
    {
    if(!is_array($folders))
      {
      $folders=array($folders);
      }
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
    foreach($folders as $key => $folder)
      { 
      if(!$folder instanceof FolderDao)
        {
        throw new Zend_Exception("Should be a folder" );
        }
      $subqueryUser= $this->database->select()
                      ->setIntegrityCheck(false)
                      ->from(array('f' => 'folder'),array('folder_id'))
                      ->join(array('fpu' => 'folderpolicyuser'),'
                            f.folder_id = fpu.folder_id AND '.$this->database->getDB()->quoteInto('fpu.policy >= ?', $policy).'
                               AND '.$this->database->getDB()->quoteInto('user_id = ? ',$userId).' ',array())
                      ->where('left_indice > ?', $folder->getLeftIndice())
                      ->where('right_indice < ?', $folder->getRightIndice());

      $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'),array('folder_id'))
                    ->join(array('fpg' => 'folderpolicygroup'),'
                                f.folder_id = fpg.folder_id  AND '.$this->database->getDB()->quoteInto('fpg.policy >= ?', $policy).'
                                   AND ( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
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

      $sql=$this->database->select()
                ->setIntegrityCheck(false)
                ->from(array('i' => 'item'),array('sum'=>'sum(i.sizebytes)','count'=>'count(i.item_id)'))
                ->join(array('i2f' => 'item2folder'),
                         '( '.$this->database->getDB()->quoteInto('i2f.folder_id IN (?)',$subSqlFolders).'
                          OR i2f.folder_id='.$folder->getKey().'
                          )
                          AND i2f.item_id = i.item_id'
                          ,array() )
                ->joinLeft(array('ip' => 'itempolicyuser'),'
                          i.item_id = ip.item_id AND '.$this->database->getDB()->quoteInto('policy >= ?', $policy).'
                             AND '.$this->database->getDB()->quoteInto('user_id = ? ',$userId).' ',array())
                ->joinLeft(array('ipg' => 'itempolicygroup'),'
                                i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto('ipg.policy >= ?', $policy).'
                                   AND ( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
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
      if($folders[$key]->size==null)
        {
        $folders[$key]->size=0;
        }
      }
    return $folders;
    }
 
  /** Custom delete function */
  public function delete($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder" );
      }
    if(!$folder->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }
    $key=$folder->getKey();
    if(!isset($key))
      {
      throw new Zend_Exception("Unable to find the key" );
      }
    $leftIndice=$folder->getLeftIndice();
    $this->database->getDB()->update('folder', array('left_indice'=> new Zend_Db_Expr('left_indice - 2')),
                          array('left_indice >= ?'=>$leftIndice));
    $this->database->getDB()->update('folder', array('right_indice'=> new Zend_Db_Expr('right_indice - 2')),
                          array('right_indice >= ?'=>$leftIndice));
    $this->database->delete( $folder);
    unset($folder->folder_id);
    $folder->saved=false;
    return true;
    } //end delete

  /** Custom save function*/
  public function save($folder)
    {
    if(!$folder instanceof  FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if($folder->getParentId()<=0)
      {
      $rightParent=0;
      }
    else
      {
      $parentFolder=$folder->getParent();
      $rightParent=$parentFolder->getRightIndice();
      }
    $data = array();
    foreach($this->_mainData as $key => $var)
      {
      if(isset($folder->$key))
        {
        $data[$key] = $folder->$key;
        }
      if($key=='right_indice')
        {
        $folder->$key=$rightParent+1;
        $data[$key]=$rightParent+1;
        }
      if($key=='left_indice')
        {
        $data[$key]=$rightParent;
        }
      }

    if(isset($data['folder_id']))
      {
      $key = $data['folder_id'];
      unset($data['folder_id']);
      unset($data['left_indice']);
      unset($data['right_indice']);
      $this->database->update($data, array('folder_id = ?'=>$key));
      return $key;
      }
    else
      {
      $this->database->getDB()->update('folder', array('right_indice'=> new Zend_Db_Expr('2 + right_indice')),
                          array('right_indice >= ?'=>$rightParent));
      $this->database->getDB()->update('folder', array('left_indice'=> new Zend_Db_Expr('2 + left_indice')),
                          array('left_indice >= ?'=>$rightParent));
      $insertedid = $this->database->insert($data);
      if(!$insertedid)
        {
        return false;
        }
      $folder->folder_id = $insertedid;
      $folder->saved=true;
      return true;
      }
    } // end method save

  /** Get community if  the folder is the main folder of one*/
  function getCommunity($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $dao= $this->initDao('Community', $this->database->fetchRow($this->database->select()
                                                           ->setIntegrityCheck(false)
                                                           ->from('community')
                                                           ->where('folder_id = ?', $folder->getFolderId())));
    return $dao;
    }
    
  /** Get user if  the folder is the main folder of one*/
  function getUser($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $dao= $this->initDao('User', $this->database->fetchRow($this->database->select()
                                                           ->setIntegrityCheck(false)
                                                           ->from('user')
                                                           ->where('folder_id = ?', $folder->getFolderId())));
    return $dao;
    }

  /** Create a folder */
  function createFolder($name,$description,$parent)
    {
    if(!$parent instanceof FolderDao&&!is_numeric($parent))
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!is_string($name)||!is_string($description))
      {
      throw new Zend_Exception("Should be a string.");
      }
    $this->loadDaoClass('FolderDao');
    $folder=new FolderDao();
    $folder->setName($name);
    $folder->setDescription($description);
    $folder->setDate(date('c'));
    if($parent instanceof FolderDao)
      {
      $parentId=$parent->getFolderId();
      }
    else
      {
      $parentId=$parent;
      }
    $folder->setParentId($parentId);
    $this->database->save($folder);
    return $folder;
    }

  /** getItems with policy check
   * @return
   */
  function getItemsFiltered($folder,$userDao=null,$policy=0)
    {
    if(is_array($folder))
      {
      $folderIds=array();
      foreach($folder as $f)
        {
        if(!$f instanceof FolderDao)
          {
          throw new Zend_Exception("Should be a folder.");
          }
        $folderIds[]=$f->getKey();
        }
      }
    else if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    else
      {
      $folderIds=array($folder->getKey());
      }
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('f' => 'item'))
                          ->join(array('p' => 'itempolicyuser'),
                                'f.item_id=p.item_id',
                                 array('p.policy'))
                          ->join(array('i' => 'item2folder'),
                                $this->database->getDB()->quoteInto('i.folder_id IN (?)',$folderIds).'
                                AND i.item_id = p.item_id' ,array('i.folder_id'))
                          ->where('policy >= ?', $policy)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'item'))
                    ->join(array('p' => 'itempolicygroup'),
                          'f.item_id=p.item_id',
                           array('p.policy'))
                    ->join(array('i' => 'item2folder'),
                                $this->database->getDB()->quoteInto('i.folder_id IN (?)',$folderIds).'
                                AND i.item_id = p.item_id' ,array('i.folder_id'))
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->database->getDB()->quoteInto('p.group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
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
    $return = array();    
    $policyArray=array();
    foreach ($rowset as $keyRow=>$row)
      {
   
      if(!isset($policyArray[$row['item_id']])||(isset($policyArray[$row['item_id']])&&$row['policy']>$policyArray[$row['item_id']]))
        {
        $policyArray[$row['item_id']]=$row['policy'];
        }
      }
    foreach ($rowset as $keyRow=>$row)
      {
      if(isset($policyArray[$row['item_id']]))
        {
        $tmpDao= $this->initDao('Item', $row);
        $tmpDao->policy=$policyArray[$row['item_id']];
        $tmpDao->parent_id=$row['folder_id'];
        $return[] = $tmpDao;
        unset($policyArray[$row['item_id']]);
        }
      }

    $this->Component->Sortdao->field='name';
    $this->Component->Sortdao->order='asc';
    usort($return, array($this->Component->Sortdao,'sortByName'));

    return $return;
    }

    /** getFolder with policy check */
  function getChildrenFoldersFilteredRecursive($folders,$userDao=null,$policy=0)
    {
    if(!is_array($folders))
      {
      $folders=array($folders);     
      }

    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    foreach($folders as $key=>$folder)
      {
      if(!$folder instanceof FolderDao)
        {
        throw new Zend_Exception("Should be a folder.");
        }

      
       $subqueryUser= $this->database->select()
                      ->setIntegrityCheck(false)
                      ->from(array('f' => 'folder'))
                      ->join(array('fpu' => 'folderpolicyuser'),'
                            f.folder_id = fpu.folder_id AND '.$this->database->getDB()->quoteInto('fpu.policy >= ?', $policy).'
                               AND '.$this->database->getDB()->quoteInto('user_id = ? ',$userId).' ',array('policy'))
                      ->where('left_indice > ?', $folder->getLeftIndice())
                      ->where('right_indice < ?', $folder->getRightIndice());

      $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'))
                    ->join(array('fpg' => 'folderpolicygroup'),'
                                f.folder_id = fpg.folder_id  AND '.$this->database->getDB()->quoteInto('fpg.policy >= ?', $policy).'
                                   AND ( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                        group_id IN (' .new Zend_Db_Expr(
                                        $this->database->select()
                                             ->setIntegrityCheck(false)
                                             ->from(array('u2g' => 'user2group'),
                                                    array('group_id'))
                                             ->where('u2g.user_id = ?' , $userId)
                                             ) .'))' ,array('policy'))
                    ->where('left_indice > ?', $folder->getLeftIndice())
                    ->where('right_indice < ?', $folder->getRightIndice());

      $sql = $this->database->select()
            ->union(array($subqueryUser, $subqueryGroup));
      $rowset = $this->database->fetchAll($sql);     
      $return = array();
      $policyArray=array();
      foreach ($rowset as $keyRow=>$row)
        {
        if(!isset($policyArray[$row['folder_id']])||(isset($policyArray[$row['folder_id']])&&$row['policy']>$policyArray[$row['folder_id']]))
          {
          $policyArray[$row['folder_id']]=$row['policy'];
          }
        }
        
      foreach ($rowset as $keyRow=>$row)
        {
        if(isset($policyArray[$row['folder_id']]))
          {
          $tmpDao= $this->initDao('Folder', $row);
          $tmpDao->policy=$policyArray[$row['folder_id']];
          $return[$row['folder_id']] = $tmpDao;
          unset($policyArray[$row['folder_id']]);
          }
        }
      $folders[$key]->allChildren=$return;     
      }
    return $folders;
    }

  /** getFolder with policy check */
  function getChildrenFoldersFiltered($folder,$userDao=null,$policy=0)
    {
    if(is_array($folder))
      {
      $folderIds=array();
      foreach($folder as $f)
        {
        if(!$f instanceof FolderDao)
          {
          throw new Zend_Exception("Should be a folder.");
          }
        $folderIds[]=$f->getKey();
        }
      }
    else if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    else
      {
      $folderIds=array($folder->getKey());
      }
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('f' => 'folder'))
                          ->join(array('p' => 'folderpolicyuser'),
                                'f.folder_id=p.folder_id',
                                 array('p.policy'))
                          ->where ('f.parent_id IN (?)',$folderIds)
                          ->where('policy >= ?', $policy)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'))
                    ->join(array('p' => 'folderpolicygroup'),
                          'f.folder_id=p.folder_id',
                           array('p.policy'))
                    ->where ('f.parent_id IN (?)',$folderIds)
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
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
    $return = array();      
    $policyArray=array();
    foreach ($rowset as $keyRow=>$row)
      {
      if(!isset($policyArray[$row['folder_id']])||(isset($policyArray[$row['folder_id']])&&$row['policy']>$policyArray[$row['folder_id']]))
        {
        $policyArray[$row['folder_id']]=$row['policy'];
        }
      }

    foreach ($rowset as $keyRow=>$row)
      {
      if(isset($policyArray[$row['folder_id']]))
        {
        $tmpDao= $this->initDao('Folder', $row);
        $tmpDao->policy=$policyArray[$row['folder_id']];
        $return[] = $tmpDao;
        unset($policyArray[$row['folder_id']]);
        }
      }
   
    $this->Component->Sortdao->field='name';
    $this->Component->Sortdao->order='asc';
    usort($return, array($this->Component->Sortdao,'sortByName'));
    return $return;
    }

  /** Get the child folder
   *  @return FolderDao
   */
  function getFolderByName($folder,$foldername)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $row = $this->database->fetchRow($this->database->select()->from($this->_name)
                                          ->where('parent_id=?',$folder->getFolderId())
                                          ->where('name=?',$foldername));
    return $this->initDao('Folder',$row);
    } // end function getFolderByName

  /** Add an item to a folder
   * @return void
   */
  function addItem($folder,$item)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    $this->link('items',$folder,$item);
    } // end function addItem

  /** Return an item by its name
   * @return ItemDao*/
  function getItemByName($folder,$itemname)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $row = $this->database->fetchRow($this->database->select()->setIntegrityCheck(false)
                                          ->from('item')
                                          ->join('item2folder','item2folder.item_id=item.item_id')
                                          ->where('item2folder.folder_id=?',$folder->getFolderId())
                                          ->where('item.name=?',$itemname));
    return $this->initDao('Item',$row);
    } // end function getChildIdFromName

  /** Return a list of folders corresponding to the search
   * @return Array of FolderDao */
  function getFoldersFromSearch($search,$userDao)
    {
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }

    $subqueryUser= $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'folderpolicyuser'),
                                 array('folder_id'))
                          ->where('policy >= ?', MIDAS_POLICY_READ)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->database->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'folderpolicygroup'),
                           array('folder_id'))
                    ->where('policy >= ?', MIDAS_POLICY_READ)
                    ->where('( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->database->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));


    $sql = $this->database->select()->from($this->_name,array('folder_id','name','count(*)'))
                                          ->where($this->database->getDB()->quoteInto('name LIKE ?','%'.$search.'%').'
                                                   AND (folder_id IN ('.$subqueryUser.')
                                                   OR folder_id IN ('.$subqueryGroup.'))')
                                          ->group('name')
                                          ->limit(14);


    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = new FolderDao();
      $tmpDao->count = $row['count(*)'];
      $tmpDao->setName($row->name);
      $tmpDao->setFolderId($row->folder_id);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getFolderFromSearch()

} // end class
?>
