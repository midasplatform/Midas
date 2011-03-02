<?php
/**
 * \class FolderModel
 * \brief Pdo Model
 */
class FolderModel extends AppModelPdo
{
  public $_name = 'folder';
  public $_key = 'folder_id';
  


  public $_mainData= array(
    'folder_id'=> array('type'=>MIDAS_DATA),
    'left_indice'=> array('type'=>MIDAS_DATA),
    'right_indice'=> array('type'=>MIDAS_DATA),
    'parent_id'=> array('type'=>MIDAS_DATA),
    'name'=> array('type'=>MIDAS_DATA),
    'description' =>  array('type'=>MIDAS_DATA),
    'date' =>  array('type'=>MIDAS_DATA),
    'items' =>  array('type'=>MIDAS_MANY_TO_MANY, 'model'=>'Item', 'table' => 'item2folder', 'parent_column'=> 'folder_id', 'child_column' => 'item_id'),
    'folderpolicygroup' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Folderpolicygroup', 'parent_column'=> 'folder_id', 'child_column' => 'folder_id'),
    'folderpolicyuser' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Folderpolicyuser', 'parent_column'=> 'folder_id', 'child_column' => 'folder_id'),
    'folders' => array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'Folder', 'parent_column'=> 'folder_id', 'child_column' => 'parent_id'),
    'parent' => array('type'=>MIDAS_MANY_TO_ONE, 'model'=>'Folder', 'parent_column'=> 'parent_id', 'child_column' => 'folder_id'),
    );

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
      
     $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'folderpolicyuser'),
                                 array('folder_id'))
                          ->where('policy >= ?', $policy)
                          ->where('p.folder_id >= ?', $folderDao->getKey())
                          ->where('user_id = ? ',$userId);

     $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'folderpolicygroup'),
                           array('folder_id'))
                    ->where('policy >= ?', $policy)
                    ->where('p.folder_id >= ?', $folderDao->getKey())
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));

    $sql = $this->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $rowset = $this->fetchAll($sql);
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
    $folderIds=array();
    $foldersWithChild=$this->getChildrenFoldersFilteredRecursive($folders,$userDao,$policy);
    foreach($foldersWithChild as $folder)
      {
      if(!$folder instanceof FolderDao)
        {
        throw new Zend_Exception("Should be a folder" );
        }
      $folderIds[]=$folder->getKey();     
      foreach($folder->allChildren as $child)
        {
        $folderIds[]=$child->getKey();
        }
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
    
       $subqueryUser= $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'))
                    ->join(array('i2f' => 'item2folder'),
                          $this->_db->quoteInto('i2f.folder_id IN (?)',$folderIds).'
                          AND i2f.folder_id = f.folder_id' ,array())
                    ->join(array('i' => 'item'),'
                          i.item_id = i2f.item_id' ,array('i.item_id','i.sizebytes'))
                    ->join(array('ip' => 'itempolicyuser'),'
                          i.item_id = ip.item_id AND '.$this->_db->quoteInto('policy >= ?', $policy).'
                             AND '.$this->_db->quoteInto('user_id = ? ',$userId).' ',array())
                    ;

        $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'))
                    ->join(array('i2f' => 'item2folder'),
                          $this->_db->quoteInto('i2f.folder_id IN (?)',$folderIds).'
                          AND i2f.folder_id = f.folder_id' ,array())
                    ->join(array('i' => 'item'),'
                          i.item_id = i2f.item_id' ,array('i.item_id', 'i.sizebytes'))
                    ->join(array('ipg' => 'itempolicygroup'),'
                                i.item_id = ipg.item_id AND '.$this->_db->quoteInto('ipg.policy >= ?', $policy).'
                                   AND ( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                        group_id IN (' .new Zend_Db_Expr(
                                        $this->select()
                                             ->setIntegrityCheck(false)
                                             ->from(array('u2g' => 'user2group'),
                                                    array('group_id'))
                                             ->where('u2g.user_id = ?' , $userId)
                                             ) .'))' ,array())
                  ;

     $sql = $this->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $rowset = $this->fetchAll($sql);
    
    $rowsetAnalysed=array();
    foreach($rowset as $keyR=>$r)
      {
      $r=$r->toArray();
      $found=false;
      foreach($rowsetAnalysed as $keyRr=>$rr)
        {
        if($rr['folder_id']==$r['folder_id']&&!in_array($r['item_id'], $rr['items']))
          {
          $rowsetAnalysed[$keyRr]['items'][]=$r['item_id'];
          $rowsetAnalysed[$keyRr]['count']++;
          $rowsetAnalysed[$keyRr]['sizebytes']+=$r['sizebytes'];
          $found=true;
          break;
          }
        }
      if(!$found)
        {
        $r['items']=array($r['item_id']);
        $r['count']=1;
        $rowsetAnalysed[]=$r;
        }
      }
    $results = array();
    foreach($rowsetAnalysed as $row)
      {
      foreach($results as $r)
        {
        if($r->getKey()==$row['folder_id'])
          {
          unset($row);
          break;
          }
        }
      if(isset($row))
        {
        $tmpDao= $this->initDao('Folder', $row);
        $tmpDao->count = $row['count'];
        $tmpDao->size = $row['sizebytes'];
        $results[] = $tmpDao;
        unset($tmpDao);
        }
      }
      
    foreach($folders as $key=>$folder)
      {
      $folders[$key]->count=0;
      $folders[$key]->size=0;
      foreach($results as $result)
        {
        if($folders[$key]->getKey()==$result->getKey())
          {
          $folders[$key]->count+=$result->count;
          $folders[$key]->size+=$result->size;
          }
        foreach($folder->allChildren as $child)
          {
          if($child->getKey()==$result->getKey())
            {
            $folders[$key]->count+=$result->count;
            $folders[$key]->size+=$result->size;
            }
          }
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
    $this->_db->update('folder', array('left_indice'=> new Zend_Db_Expr('left_indice - 2')),
                          array('left_indice >= ?'=>$leftIndice));
    $this->_db->update('folder', array('right_indice'=> new Zend_Db_Expr('right_indice - 2')),
                          array('right_indice >= ?'=>$leftIndice));
    parent::delete( $folder);
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
      $this->update($data, array('folder_id = ?'=>$key));
      return $key;
      }
    else
      {
      $this->_db->update('folder', array('right_indice'=> new Zend_Db_Expr('2 + right_indice')),
                          array('right_indice >= ?'=>$rightParent));
      $this->_db->update('folder', array('left_indice'=> new Zend_Db_Expr('2 + left_indice')),
                          array('left_indice >= ?'=>$rightParent));
      $insertedid = $this->insert($data);
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
    $dao= $this->initDao('Community', $this->fetchRow($this->select()
                                                           ->setIntegrityCheck(false)
                                                           ->from('community')
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
    $this->save($folder);
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

    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'itempolicyuser'),
                                 array('item_id'))
                          ->join(array('i' => 'item2folder'),
                                $this->_db->quoteInto('i.folder_id IN (?)',$folderIds).'
                                AND i.item_id = p.item_id' ,array('i.folder_id'))
                          ->where('policy >= ?', $policy)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'itempolicygroup'),
                           array('item_id'))
                    ->join(array('i' => 'item2folder'),
                                $this->_db->quoteInto('i.folder_id IN (?)',$folderIds).'
                                AND i.item_id = p.item_id' ,array('i.folder_id'))
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));

    $sql = $this->select()
            ->union(array($subqueryUser, $subqueryGroup));
    $rowset = $this->fetchAll($sql);
    $items = array();
    $parents = array();
    foreach ($rowset as $row)
      {
      $items[]=$row->item_id;
      $parents[$row->item_id]=$row->folder_id;
      }
    $this->ModelLoader = new MIDAS_ModelLoader();
    $model = $this->ModelLoader->loadModel('Item');
    $itemsDao=$model->load($items);
    foreach($itemsDao as $k => $v)
      {
      $itemsDao[$k]->parent_id=$parents[$v->getItemId()];
      }
    return $itemsDao;
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

      
       $subqueryUser= $this->select()
                      ->setIntegrityCheck(false)
                      ->from(array('f' => 'folder'))
                      ->join(array('fpu' => 'folderpolicyuser'),'
                            f.folder_id = fpu.folder_id AND '.$this->_db->quoteInto('fpu.policy >= ?', $policy).'
                               AND '.$this->_db->quoteInto('user_id = ? ',$userId).' ',array('policy'))
                      ->where('left_indice > ?', $folder->getLeftIndice())
                      ->where('right_indice < ?', $folder->getRightIndice());

      $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('f' => 'folder'))
                    ->join(array('fpg' => 'folderpolicygroup'),'
                                f.folder_id = fpg.folder_id  AND '.$this->_db->quoteInto('fpg.policy >= ?', $policy).'
                                   AND ( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                        group_id IN (' .new Zend_Db_Expr(
                                        $this->select()
                                             ->setIntegrityCheck(false)
                                             ->from(array('u2g' => 'user2group'),
                                                    array('group_id'))
                                             ->where('u2g.user_id = ?' , $userId)
                                             ) .'))' ,array('policy'))
                    ->where('left_indice > ?', $folder->getLeftIndice())
                    ->where('right_indice < ?', $folder->getRightIndice());

      $sql = $this->select()
            ->union(array($subqueryUser, $subqueryGroup));
      $rowset = $this->fetchAll($sql);      

      $return = array();
      foreach ($rowset as $keyRow=>$row)
        {
        foreach($return as $keyElement=>$element)
          {
          if(isset($row)&&$row['folder_id']==$element->getKey()&&$row['policy']>$element->policy)
            {
            unset($return[$keyElement]);
            }
          elseif(isset($row)&&$row['folder_id']==$element->getKey())
            {
            unset($row);
            }
          }
        if(isset($row))
          {
          $tmpDao= $this->initDao('Folder', $row);
          $tmpDao->policy=$row['policy'];
          $return[] = $tmpDao;
          unset($tmpDao);
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

    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'folderpolicyuser'),
                                 array('folder_id'))
                          ->join(array('f' => 'folder'),
                          $this->_db->quoteInto('f.parent_id IN (?)',$folderIds)
                          .' AND p.folder_id = f.folder_id'  ,array())
                          ->where('policy >= ?', $policy)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'folderpolicygroup'),
                           array('folder_id'))
                    ->join(array('f' => 'folder'),
                          $this->_db->quoteInto('f.parent_id IN (?)',$folderIds)
                          .' AND p.folder_id = f.folder_id'  ,array())
                    ->where('policy >= ?', $policy)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));
    $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from('folder')
                ->where('folder_id IN ('.$subqueryUser.')' )
                ->orWhere('folder_id IN ('.$subqueryGroup.')' );
    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Folder', $row);
      $return[] = $tmpDao;
      unset($tmpDao);
      }
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
    $row = $this->fetchRow($this->select()->from($this->_name)
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
    $row = $this->fetchRow($this->select()->setIntegrityCheck(false)
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

    $subqueryUser= $this->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'folderpolicyuser'),
                                 array('folder_id'))
                          ->where('policy >= ?', MIDAS_POLICY_READ)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'folderpolicygroup'),
                           array('folder_id'))
                    ->where('policy >= ?', MIDAS_POLICY_READ)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));


    $sql = $this->select()->from($this->_name,array('folder_id','name','count(*)'))
                                          ->where($this->_db->quoteInto('name LIKE ?','%'.$search.'%').'
                                                   AND (folder_id IN ('.$subqueryUser.')
                                                   OR folder_id IN ('.$subqueryGroup.'))')
                                          ->group('name')
                                          ->limit(14);


    $rowset = $this->fetchAll($sql);
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
