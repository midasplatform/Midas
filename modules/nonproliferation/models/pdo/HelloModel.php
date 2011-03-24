<?php

class Helloworld_HelloModel extends AppModelPdo
{
  public $_name = 'helloworld_hello';
  public $_key = 'hello_id';

  public $_mainData= array(
      'hello_id'=>  array('type'=>MIDAS_DATA),
      );
    
}  // end class
?>