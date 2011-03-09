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
    }//end sortByDate
    
    /** sort by name*/
  public function sortByName($a,$b)
    {
    if($this->field==''||!isset($a->{$this->field}))
      {
      throw new Zend_Exception("Error field.");
      }
    $a_n = strtolower($a->{$this->field})  ; 
    $b_n = strtolower($b->{$this->field})  ; 

    if( $a_n == $b_n )
      return 0 ; 
    
    if($this->order=='asc')
      {
      return ($a_n < $b_n) ? -1 : 1; 
      }
    else
      {
      return ($a_n < $b_n ) ? 1 : -1; 
      }
    }//end sortByDate
    
    
  public function arrayUniqueDao($array, $keep_key_assoc = false)
    {
    $duplicate_keys = array();
    $tmp         = array();       

    foreach ($array as $key=>$val)
      {
      // convert objects to arrays, in_array() does not support objects
      if (is_object($val))
          $val = (array)$val;

      if (!in_array($val, $tmp))
          $tmp[] = $val;
      else
          $duplicate_keys[] = $key;
      }

    foreach ($duplicate_keys as $key)
        unset($array[$key]);

    return $keep_key_assoc ? $array : array_values($array);
    }
} // end class
?>