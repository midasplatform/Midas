<?php
require_once BASE_PATH.'/core/models/base/ItemRevisionModelBase.php';

/**
 * \class ItemRevisionModel
 * \brief Pdo Model
 */
class ItemRevisionModel extends ItemRevisionModelBase
{
  /** Returns the latest revision of a model */
  function getLatestRevision($itemdao)
    {
    $row = $this->database->fetchRow($this->database->select()->from($this->_name)->where('item_id=?',$itemdao->getItemId())->order('revision DESC')->limit(1));
    return $this->initDao('ItemRevision',$row);
    }
    
  /** Returns the of the revision in Bytes */
  function getSize($revision)
    {
    $row = $this->database->fetchRow($this->database->select()
                            ->setIntegrityCheck(false)->from('bitstream', array('sum(sizebytes) as sum'))->where('itemrevision_id=?',$revision->getKey()));
    return $row['sum'];
    }

  /** Return a bitstream by name */
  function getBitstreamByName($revision,$name)
    {
    $row = $this->database->fetchRow($this->database->select()->setIntegrityCheck(false)
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
    $ItemModel = $modelLoad->loadModel('Item');
  //  $TaskModel = $modelLoad->loadModel('Task');

    $bitstreamDao->setItemrevisionId($itemRevisionDao->getItemrevisionId());

    // Save the bistream
    $bitstreamDao->setDate(date('c'));
    $BitstreamModel->save($bitstreamDao);
    
    $item=$itemRevisionDao->getItem($bitstreamDao);
    $item->setSizebytes($this->getSize($itemRevisionDao));
    $item->setDate(date('c'));
    
    /** thumbnail*/   
 /*   $procces=Zend_Registry::get('configGlobal')->processing;
    if($procces=='cron')
      {
      $TaskModel->createTask(MIDAS_TASK_ITEM_THUMBNAIL,MIDAS_RESOURCE_ITEM,$item->getKey(),'');
      }
    else
      {
      $thumbnailCreator=$this->Component->Filter->getFilter('ThumbnailCreator');
      $thumbnailCreator->inputFile = $bitstreamDao->getPath();
      $thumbnailCreator->inputName = $bitstreamDao->getName();
      $hasThumbnail = $thumbnailCreator->process();
      $thumbnail_output_file = $thumbnailCreator->outputFile;
      if($hasThumbnail&&  file_exists($thumbnail_output_file))
        {
        $oldThumbnail=$item->getThumbnail();
        if(!empty($oldThumbnail))
          {
          unlink($oldThumbnail);
          }
        $item->setThumbnail(substr($thumbnail_output_file, strlen(BASE_PATH)+1));
        }    
      }*/
    $ItemModel->save($item);
    } // end addBitstream

} // end class
?>
