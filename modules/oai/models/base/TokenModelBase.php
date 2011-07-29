<?php
abstract class Api_TokenModelBase extends Api_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'api_token';
    $this->_key = 'token_id';

    $this->_mainData= array(
        'token_id'=>  array('type'=>MIDAS_DATA),
        'userapi_id'=>  array('type'=>MIDAS_DATA),
        'token'=>  array('type'=>MIDAS_DATA),
        'expiration_date'=>  array('type'=>MIDAS_DATA),
        'userapi' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Userapi','module' => 'api', 'parent_column' => 'userapi_id', 'child_column' => 'userapi_id'),
        );
    $this->initialize(); // required
    } // end __construct()
    
   abstract function cleanExpired();
   
} // end class AssetstoreModelBase
?>
