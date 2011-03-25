<?php
require_once BASE_PATH.'/core/models/base/BitstreamModelBase.php';

/**
 * \class BitstreamModel
 * \brief Pdo Model
 */
class BitstreamModel extends BitstreamModelBase
{
  /** do not use, use method addBitstream in ItemRevision Model*/
  public function save($dao)
    {
    $stack=debug_backtrace(false);
    if($stack[1]['class']=="ItemRevisionModel"&&$stack[1]['function']=='addBitstream')
      {
      return parent::save($dao);
      }
    throw new Zend_Exception(" Do not use, use method addBitstream in ItemRevision Model.");
    }//end Save
    

}  // end class
?>
