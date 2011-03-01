<?php
/**
 * \class BitstreamModel
 * \brief Pdo Model
 */
class BitstreamModel extends AppModelPdo
{
  public $_name = 'bitstream';
  public $_key = 'bitstream_id';

  public $_mainData= array(
      'bitstream_id'=>  array('type'=>MIDAS_DATA),
      'itemrevision_id'=>  array('type'=>MIDAS_DATA),
      'assetstore_id'=>  array('type'=>MIDAS_DATA),
      'name' =>  array('type'=>MIDAS_DATA),
      'mimetype' =>  array('type'=>MIDAS_DATA),
      'sizebytes' =>  array('type'=>MIDAS_DATA),
      'checksum' =>  array('type'=>MIDAS_DATA),
      'path' =>  array('type'=>MIDAS_DATA),
      'assetstore_id' =>  array('type'=>MIDAS_DATA),
      'date' =>  array('type'=>MIDAS_DATA),
      //'itemrevision' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'ItemRevision', 'parent_column'=> 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      'assetstore' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model' => 'Assetstore', 'parent_column'=> 'assetstore_id', 'child_column' => 'assetstore_id'),
    );

  /** init the bitstream (before save it using addBitstream in ItemRevision Model*/
  public function initBitstream($assetstoreDao,$name,$path)
    {        
    Zend_Loader::loadClass('BitstreamDao', BASE_PATH.'/application/models/dao');
    $bitstreamDao = new BitstreamDao;
    $bitstreamDao->setName($name);
    $bitstreamDao->setPath($path);

    $tmpPath=$assetstoreDao->getPath().'/'.rand(1, 1000);
    if(!file_exists($assetstoreDao->getPath()))
      {
      throw new Zend_Exception("Problem assetstore path: "+$assetstoreDao->getKey());
      }
    if(!file_exists($tmpPath))
      {
      mkdir($tmpPath);
      }
    $tmpPath.='/'.rand(1, 1000);
    if(!file_exists($tmpPath))
      {
      mkdir($tmpPath);
      }
    $fullPath=$tmpPath."/".rand(1,1000);
    while(file_exists($fullPath))
      {
      $fullPath=$tmpPath."/".rand(1,1000);
      }
    if(!rename ( $path ,$fullPath))
      {
      throw new Zend_Exception("Unable to move file ".$path.' to '.$fullPath);
      }
    $bitstreamDao->setPath($fullPath);    
    $bitstreamDao->fillPropertiesFromPath();
    $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());
    return $bitstreamDao;
    }
  
  /** do not use, use method addBitstream in ItemRevision Model*/
  public function save($dao)
    {
    $stack=debug_backtrace();
    if($stack[1]['class']=="ItemRevisionModel"&&$stack[1]['function']=='addBitstream')
      {
      return parent::save($dao);
      }
    throw new Zend_Exception(" Do not use, use method addBitstream in ItemRevision Model.");
    }//end Save
    

}  // end class
?>
