<?php
require_once BASE_PATH.'/core/models/base/ItemKeywordModelBase.php';

/**
 * \class ItemKeywordModel
 * \brief Cassandra Model
 */
class ItemKeywordModel extends ItemKeywordModelBase
{
  /** Custom insert function
   * @return boolean */
  function insertKeyword($keyword)
    {
    if(!$keyword instanceof ItemKeywordDao)
      {
      throw new Zend_Exception("Should be a keyword" );
      }

    // Check if the keyword already exists
    $itemkeyword = $this->database->getCassandra('itemkeyword',$keyword->getValue());
    
    if(empty($itemkeyword))
      {
      $keyword->setRelevance(1);
      $return = parent::save($keyword);
      }
    else
      {
      $relevance = $itemkeyword['relevance'];
      $relevance += 1;  
      $keyword->setRelevance($relevance);
      $return = parent::save($keyword);
      }
    return $return;
    } // end insertKeyword()

} // end class
?>
