<?php
/** ItempolicygroupModelBase */
class ItempolicygroupModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itempolicygroup';

    $this->_mainData = array(
        'item_id' => array('type' => MIDAS_DATA),
        'group_id' => array('type' => MIDAS_DATA),
        'policy' => array('type' => MIDAS_DATA),
        'date' => array('type' => MIDAS_DATA),
        'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
        'group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'group_id', 'child_column' => 'group_id')
      );
    $this->initialize(); // required
    } // end __construct() 
  
} // end class ItempolicygroupModelBase