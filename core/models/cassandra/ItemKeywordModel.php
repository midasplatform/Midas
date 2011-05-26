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

    // Check ifthe keyword already exists
    $itemkeyword = $this->database->getCassandra('itemkeyword', $keyword->getValue());
    
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
