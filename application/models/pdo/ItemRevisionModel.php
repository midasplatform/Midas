<?php
/**
 * \class ItemRevisionModel
 * \brief Pdo Model
 */
class ItemRevisionModel extends AppModelPdo
{
  public $_name = 'itemrevision';
  public $_daoName = 'ItemRevisionDao';
  public $_key = 'itemrevision_id';

  public $_mainData= array(
    'itemrevision_id'=>  array('type'=>MIDAS_DATA),
    'item_id'=>  array('type'=>MIDAS_DATA),
    'revision' =>  array('type'=>MIDAS_DATA),
    'date' =>  array('type'=>MIDAS_DATA),
    'changes' =>  array('type'=>MIDAS_DATA),
    'user_id' => array('type'=>MIDAS_DATA),
    'bitstreams' =>  array('type'=>MIDAS_ONE_TO_MANY, 'model'=>'Bitstream', 'parent_column'=> 'itemrevision_id', 'child_column' => 'itemrevision_id'),
    );

  /** Returns the latest revision of a model */
  function getLatestRevision($itemdao)
    {
    $row = $this->fetchRow($this->select()->from($this->_name)->where('item_id=?',$itemdao->getItemId())->order('revision DESC')->limit(1));
    return $this->initDao('ItemRevision',$row);
    }

  /** Return a bitstream by name */
  function getBitstreamByName($revision,$name)
    {
    $row = $this->fetchRow($this->select()->setIntegrityCheck(false)
                                          ->from('bitstream')
                                          ->where('itemrevision_id=?',$revision->getItemrevisionId())
                                          ->where('name=?',$name));
    return $this->initDao('Bitstream',$row);
    } // end getBitstreamByName

  /** Add a bitstream to a revision */
  function addBitstream($itemRevisionDao,$bitstreamDao)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $BitstreamModel = $modelLoad->loadModel('Bitstream');

    $bitstreamDao->setItemrevisionId($itemRevisionDao->getItemrevisionId());

    // Save the bistream
    $BitstreamModel->save($bitstreamDao);
    } // end addBitstream

} // end class
?>
