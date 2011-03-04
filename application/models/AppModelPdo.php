<?php

/**
 *  GlobalModelPdo
 *  Global model methods
 */
class AppModelPdo extends MIDAS_GlobalModelPdo
  {

    
  /** return the number row in the table
   *
   * @return int 
   */
  public function getCountAll()
    {
    $count = $this->fetchRow( $this->select()->from($this->_name, 'count(*) as COUNT' ));
    return $count['COUNT'];
    }//end getCountAll
  }//end class

?>
