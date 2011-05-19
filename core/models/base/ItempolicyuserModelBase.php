<?php
/** ItempolicyuserModelBase */
class ItempolicyuserModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itempolicyuser';

    $this->_mainData = array(
        'item_id' => array('type' => MIDAS_DATA),
        'user_id' => array('type' => MIDAS_DATA),
        'policy' => array('type' => MIDAS_DATA),
        'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id')
      );
    $this->initialize(); // required
    } // end __construct()  
  
  /** delete */
  public function delete($dao)
    {
    $item = $dao->getItem();
    parent::delete($dao);
    $modelLoad = new MIDAS_ModelLoader();
    $fitemGroupModel = $modelLoad->loadModel('Itempolicygroup');
    $fitemGroupModel->computePolicyStatus($item);
    }//end delete
} // end class ItempolicyuserModelBase
