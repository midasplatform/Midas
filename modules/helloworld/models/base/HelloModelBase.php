<?php
class Helloworld_HelloModelBase extends Helloworld_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'helloworld_hello';
    $this->_key = 'hello_id';

    $this->_mainData= array(
        'hello_id'=>  array('type'=>MIDAS_DATA),
        );
    $this->initialize(); // required
    } // end __construct()
    
} // end class AssetstoreModelBase
?>