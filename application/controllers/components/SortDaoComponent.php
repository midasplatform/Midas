<?php
/** Sort Daos*/
class SortdaoComponent extends AppComponent
{ 
  public $field='';
  public $order='asc';
  
  /* sort daos*/
  public function sortByDate($a,$b)
    {
    if($this->field==''||!isset($a->{$this->field}))
      {
      throw new Zend_Exception("Error field.");
      }
    $a_t = strtotime( $a->{$this->field} ) ; 
    $b_t = strtotime( $b->{$this->field} ) ; 

    if( $a_t == $b_t )
      return 0 ; 
    
    if($this->order=='asc')
      {
      return ($a_t > $b_t ) ? -1 : 1; 
      }
    else
      {
      return ($a_t > $b_t ) ? 1 : -1; 
      }
    }
    
} // end class
?>