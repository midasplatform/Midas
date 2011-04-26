<?php
require_once BASE_PATH.'/core/models/base/ItemKeywordModelBase.php';

/**
 * \class ItemKeywordModel
 * \brief Pdo Model
 */
class ItemKeywordModel extends ItemKeywordModelBase
{
  
  /** Get the keyword from the search.
   * @return Array of ItemDao */
  function getItemsFromSearch($searchterm,$userDao,$limit=14,$group=true,$order='view')
    {
    $isAdmin=false;
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      if($userDao->isAdmin())
        {
        $isAdmin= true;
        }
      }
          
    $searchterms=explode(' ', $searchterm);
    // Apparently it's slow to do a like in a subquery so we run it first  
    $sql = $this->database->select()->from(array('i'=>'itemkeyword'),array())
                ->setIntegrityCheck(false);
    $sql->join(array('i2k' => 'item2keyword'),'i.keyword_id=i2k.keyword_id',array('item_id'));
    
    if(empty($searchterms))
      {
      return array();
      }
    
    foreach($searchterms as $key=>$term)
      {
      if($key==0)
        {
        $sql->where('value LIKE ?','%'.$term.'%'); 
        }
      else
        {
        $sql->orWhere('value LIKE ?','%'.$term.'%'); 
        }
      }
                                   
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    $itemIdsCount=array();
    $itemIds=array();
    foreach($rowset as $row)
      {
      if(isset($itemIdsCount[$row->item_id]))
        {
        $itemIdsCount[$row->item_id]++;
        }
      else
        {
        $itemIdsCount[$row->item_id]=1;
        }
      }
    foreach($itemIdsCount as $key=>$n)
      {
      if($n<count($searchterms))
        {
        unset($itemIdsCount[$key]);
        }
      else
        {
        $itemIds[]=$key;
        }
      }
      
    if(empty($itemIds))
      {
      return array();
      }
    $sql=$this->database->select();
    if($group)
      {
      $sql->from(array('i' => 'item'),array('item_id','name','count(*)'));
      }
    else
      {
      $sql->from(array('i' => 'item'));
      }
              
    if(!$isAdmin)
      {
      $sql ->joinLeft(array('ipu' => 'itempolicyuser'),'
                    i.item_id = ipu.item_id AND '.$this->database->getDB()->quoteInto('ipu.policy >= ?', MIDAS_POLICY_READ).'
                       AND '.$this->database->getDB()->quoteInto('ipu.user_id = ? ',$userId).' ',array())
          ->joinLeft(array('ipg' => 'itempolicygroup'),'
                         i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto('ipg.policy >= ?', MIDAS_POLICY_READ).'
                             AND ( '.$this->database->getDB()->quoteInto('ipg.group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                  ipg.group_id IN (' .new Zend_Db_Expr(
                                  $this->database->select()
                                       ->setIntegrityCheck(false)
                                       ->from(array('u2g' => 'user2group'),
                                              array('group_id'))
                                       ->where('u2g.user_id = ?' , $userId)
                                       ) .'))' ,array())
          ->where(
           '(
            ipu.item_id is not null or
            ipg.item_id is not null)'
            );
      }
    $sql->setIntegrityCheck(false)  
          ->where('i.item_id IN   (?)',$itemIds)             
          ->limit($limit)
          ;
    
    if($group)
      {
      $sql->group('i.name');
      }
      
    switch ($order)
      {
      case 'name':
        $sql->order(array('i.name ASC'));
        break;
      case 'date':
        $sql->order(array('i.date ASC'));
        break;
      case 'view': 
      default:
        $sql->order(array('i.view DESC'));
        break;
      }
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $row)
      {
      $tmpDao=$this->initDao('Item', $row);
      if(isset($row['count(*)']))
        {
        $tmpDao->count = $row['count(*)'];
        }
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    } // end getItemsFromSearch()

  /** Custom insert function
   * @return boolean */
  function insertKeyword($keyword)
    {
    if(!$keyword instanceof ItemKeywordDao)
      {
      throw new Zend_Exception("Should be a keyword" );
      }
      
    // Check if the keyword already exists
    $row = $this->database->fetchRow($this->database->select()->from($this->_name)
                                          ->where('value=?',$keyword->getValue()));
    
    if($row)
      {
      $row->relevance += 1; // increase the relevance
      $return =$row->save();
      $keyword->setKeywordId($row->keyword_id);
      }
    else
      {
      $keyword->setRelevance(1);
      $return = parent::save($keyword);
      }
    unset($row);
    return $return;
    } // end insertKeyword()

  /** custom save function
  * @return boolean */
  function save($keyword)
    {
    return $this->insertKeyword($keyword);
    }

} // end class
?>
