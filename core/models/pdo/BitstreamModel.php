<?php
/**
 * \class BitstreamModel
 * \brief Pdo Model
 */
class BitstreamModel extends MIDASBitstreamModel
{
  /** do not use, use method addBitstream in ItemRevision Model*/
  public function save($dao)
    {
    $stack=debug_backtrace();
    if($stack[1]['class']=="ItemRevisionModel"&&$stack[1]['function']=='addBitstream')
      {
      return $this->database->save($dao);
      }
    throw new Zend_Exception(" Do not use, use method addBitstream in ItemRevision Model.");
    }//end Save
    

}  // end class
?>
