<?php
/**
 * \class ItemKeywordModel
 * \brief Pdo Model
 */
class ItemKeywordModel extends AppModelPdo
{
  public $_name = 'itemkeyword';
  public $_daoName = 'ItemKeywordDao';
  public $_key = 'keyword_id';

  public $_mainData= array(
    'keyword_id'=> array('type'=>MIDAS_DATA),
    'value'=> array('type'=>MIDAS_DATA),
    'relevance'=> array('type'=>MIDAS_DATA),
    );

  /** Get the keyword from the search.
   * @return Array of ItemDao */
  function getItemsFromSearch($searchterm,$userDao)
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
                          ->from(array('p' => 'itempolicyuser'),
                                 array('item_id'))
                          ->where('policy >= ?', MIDAS_POLICY_READ)
                          ->where('user_id = ? ',$userId);

    $subqueryGroup = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('p' => 'itempolicygroup'),
                           array('item_id'))
                    ->where('policy >= ?', MIDAS_POLICY_READ)
                    ->where('( '.$this->_db->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN (' .new Zend_Db_Expr(
                              $this->select()
                                   ->setIntegrityCheck(false)
                                   ->from(array('u2g' => 'user2group'),
                                          array('group_id'))
                                   ->where('u2g.user_id = ?' , $userId)
                                   .'))' ));


    $sql = $this->select()->from(array('i' => 'item'),array('item_id','name','count(*)'))
                                          ->join(array('i2k' => 'item2keyword'),'i.item_id=i2k.item_id')
                                          ->join(array('k' => 'itemkeyword'),'k.keyword_id=i2k.keyword_id')
                                          ->where('k.value LIKE ?','%'.$searchterm.'%')
                                          ->where('i.item_id IN ('.$subqueryUser.')' )
                                          ->orWhere('i.item_id IN ('.$subqueryGroup.')' )
                                          ->group('i.name')
                                          ->setIntegrityCheck(false)
                                          ->limit(14);

    $rowset = $this->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao= new ItemDao();
      $tmpDao->setItemId($row->item_id);
      $tmpDao->setName($row->name);
      $tmpDao->count = $row['count(*)'];
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
    $row = $this->fetchRow($this->select()->from($this->_name)
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
