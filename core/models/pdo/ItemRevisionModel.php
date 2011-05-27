<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

require_once BASE_PATH.'/core/models/base/ItemRevisionModelBase.php';

/**
 * \class ItemRevisionModel
 * \brief Pdo Model
 */
class ItemRevisionModel extends ItemRevisionModelBase
{
  /** get by uuid*/
  function getByUuid($uuid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid)); 
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    }
    
  /** get the metadata associated with the revision */
  function getMetadata($revisiondao)
    {
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Error param.");
      }

    $metadatavalues = array();    
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from('metadatavalue')
                          ->where('itemrevision_id = ?', $revisiondao->getKey())
                          ->joinLeft('metadata','metadata.metadata_id = metadatavalue.metadata_id');
                          
    $rowset = $this->database->fetchAll($sql); 
    foreach($rowset as $row)
      {
      $metadata = $this->initDao('Metadata', $row);
      $metadatavalues[] = $metadata; 
      }

    return $metadatavalues;
    }  // end getMetadata
    
  /** delete a revision*/
  function delete($revisiondao)
    {
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $bitstreams = $revisiondao->getBitstreams();
    $this->ModelLoader = new MIDAS_ModelLoader();
    $bitstream_model = $this->ModelLoader->loadModel('Bitstream');
    foreach($bitstreams as $bitstream)
      {
      $bitstream_model->delete($bitstream);
      }
      
      
    $deleteType = array(MIDAS_FEED_CREATE_REVISION);
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feed'))
                          ->where('ressource = ?', $revisiondao->getKey());
    
    $rowset = $this->database->fetchAll($sql);
    $this->ModelLoader = new MIDAS_ModelLoader();
    $feed_model = $this->ModelLoader->loadModel('Feed');
    foreach($rowset as $row)
      {
      $feed = $this->initDao('Feed', $row);
      if(in_array($feed->getType(), $deleteType))
        {
        $feed_model->delete($feed);
        }
      }
      
    parent::delete($revisiondao);
    $revisiondao->saved = false;
    unset($revisiondao->itemrevision_id);
    }//end delete
    
    
  /** Returns the latest revision of a model */
  function getLatestRevision($itemdao)
    {
    $row = $this->database->fetchRow($this->database->select()->from($this->_name)->where('item_id=?', $itemdao->getItemId())->order('revision DESC')->limit(1));
    return $this->initDao('ItemRevision', $row);
    }
    
  /** Returns the of the revision in Bytes */
  function getSize($revision)
    {
    $row = $this->database->fetchRow($this->database->select()
                            ->setIntegrityCheck(false)->from('bitstream', array('sum(sizebytes) as sum'))->where('itemrevision_id=?', $revision->getKey()));
    return $row['sum'];
    }

  /** Return a bitstream by name */
  function getBitstreamByName($revision, $name)
    {
    $row = $this->database->fetchRow($this->database->select()->setIntegrityCheck(false)
                                          ->from('bitstream')
                                          ->where('itemrevision_id=?', $revision->getItemrevisionId())
                                          ->where('name=?', $name));
    return $this->initDao('Bitstream', $row);
    } // end getBitstreamByName

  /** Add a bitstream to a revision */
  function addBitstream($itemRevisionDao, $bitstreamDao)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $BitstreamModel = $modelLoad->loadModel('Bitstream');
    $ItemModel = $modelLoad->loadModel('Item');
  //  $TaskModel = $modelLoad->loadModel('Task');

    $bitstreamDao->setItemrevisionId($itemRevisionDao->getItemrevisionId());

    // Save the bistream
    if(!isset($bitstreamDao->saved) || !$bitstreamDao->saved)
      {
      $bitstreamDao->setDate(date('c'));
      $BitstreamModel->save($bitstreamDao);
      }
    
    $item = $itemRevisionDao->getItem($bitstreamDao);
    $item->setSizebytes($this->getSize($itemRevisionDao));
    $item->setDate(date('c'));
    
 
    $modulesThumbnail =  Zend_Registry::get('notifier')->notify(MIDAS_NOTIFY_CREATE_THUMBNAIL);
    if(empty($modulesThumbnail))
      {
      $mime = $bitstreamDao->getMimetype();
      $tmpfile = $bitstreamDao->getPath();
       // Creating temp image as a source image (original image).
      $createThumb = true;
      if($mime == 'image/jpeg')
        {
        $src = imagecreatefromjpeg($tmpfile);
        }
      else if($mime == 'image/png')
        {
        $src = imagecreatefrompng($tmpfile);
        }
      else if($mime == 'image/gif')
        {
        $src = imagecreatefromgif($tmpfile);
        }  
      else
        {
        $createThumb = false;  
        }    
      
      if($createThumb)
        {
        $tmpPath = BASE_PATH.'/data/thumbnail/'.rand(1, 1000);
        if(!file_exists(BASE_PATH.'/data/thumbnail/'))
          {
          throw new Zend_Exception("Problem thumbnail path: ".BASE_PATH.'/data/thumbnail/');
          }
        if(!file_exists($tmpPath))
          {
          mkdir($tmpPath);
          }
        $tmpPath .= '/'.rand(1, 1000);
        if(!file_exists($tmpPath))
          {
          mkdir($tmpPath);
          }
        $destionation = $tmpPath."/".rand(1, 1000).'.jpeg';
        while(file_exists($destionation))
          {
          $destionation = $tmpPath."/".rand(1, 1000).'.jpeg';
          }
        $pathThumbnail=$destionation;

        list ($x, $y) = @getimagesize ($tmpfile);  //--- get size of img ---
        $thumb = 100;  //--- max. size of thumb ---
        if($x > $y) 
          {
          $tx = $thumb;  //--- landscape ---
          $ty = round($thumb / $x * $y);
          }
        else
          {
          $tx = round($thumb / $y * $x);  //--- portrait ---
          $ty = $thumb;
          }

        $thb = imagecreatetruecolor ($tx, $ty);  //--- create thumbnail ---
        imagecopyresampled ($thb,$src, 0,0, 0,0, $tx,$ty, $x,$y);
        imagejpeg ($thb, $pathThumbnail, 80);
        imagedestroy ($thb);    
        imagedestroy ($src);   
        } 
      }
    else
      {
      $createThumb = false;
      //TODO
      /*
    require_once BASE_PATH.'/core/controllers/components/FilterComponent.php';
    $filterComponent = new FilterComponent();
    $thumbnailCreator = $filterComponent->getFilter('ThumbnailCreator');
    $thumbnailCreator->inputFile = $bitstreamDao->getFullPath();
    $thumbnailCreator->inputName = $bitstreamDao->getName();
    $hasThumbnail = $thumbnailCreator->process();
    
      */
      }

    if($createThumb)
      {
      $oldThumbnail = $item->getThumbnail();
      if(!empty($oldThumbnail))
        {
        unlink($oldThumbnail);
        }
      $item->setThumbnail(substr($pathThumbnail, strlen(BASE_PATH)+1));
      }    
    $ItemModel->save($item);
    } // end addBitstream

} // end class
?>
