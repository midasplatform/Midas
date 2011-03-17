<?php
/**
 * \class AssetstoreModel
 * \brief Pdo Model
 */
class AssetstoreModel extends AppModelPdo
{
  public $_name = 'assetstore';
  public $_key = 'assetstore_id';

  public $_mainData= array(
      'assetstore_id'=>  array('type'=>MIDAS_DATA),
      'name'=>  array('type'=>MIDAS_DATA),
      'path'=>  array('type'=>MIDAS_DATA),
      'type' =>  array('type'=>MIDAS_DATA),
      );
    
}  // end class
?>
