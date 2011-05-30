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

/** ItemRevisionModelBase*/
abstract class ItemRevisionModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itemrevision';
    $this->_daoName = 'ItemRevisionDao';
    $this->_key = 'itemrevision_id';
    
    $this->_components = array('Filter');
  
    $this->_mainData = array(
      'itemrevision_id' =>  array('type' => MIDAS_DATA),
      'item_id' =>  array('type' => MIDAS_DATA),
      'revision' =>  array('type' => MIDAS_DATA),
      'date' =>  array('type' => MIDAS_DATA),
      'changes' =>  array('type' => MIDAS_DATA),
      'user_id' => array('type' => MIDAS_DATA),
      'license' => array('type' => MIDAS_DATA),
      'uuid' => array('type' => MIDAS_DATA),
      'bitstreams' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Bitstream', 'parent_column' => 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      'item' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
      'user' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      );
    $this->initialize(); // required
    } // end __construct()
  
  abstract function getByUuid($uuid);
  abstract function getMetadata($revisiondao);
  
   /** Add a bitstream to a revision */
  function addBitstream($itemRevisionDao, $bitstreamDao)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $BitstreamModel = $modelLoad->loadModel('Bitstream');
    $ItemModel = $modelLoad->loadModel('Item');
    // $TaskModel = $modelLoad->loadModel('Task');

    $bitstreamDao->setItemrevisionId($itemRevisionDao->getItemrevisionId());

    // Save the bistream
    $bitstreamDao->setDate(date('c'));
    $BitstreamModel->save($bitstreamDao);
    
    $item = $itemRevisionDao->getItem($bitstreamDao);
    $item->setSizebytes($this->getSize($itemRevisionDao));
    $item->setDate(date('c'));
 
    $modulesThumbnail =  Zend_Registry::get('notifier')->notify(MIDAS_NOTIFY_CREATE_THUMBNAIL);
    if(empty($modulesThumbnail))
      {
      $mime = $bitstreamDao->getMimetype();
      $tmpfile = $bitstreamDao->getPath();
      if(!file_exists($tmpfile))
        {
        $tmpfile = $bitstreamDao->getFullPath();
        }
       // Creating temp image as a source image (original image).
      $createThumb = true;
      if(file_exists($tmpfile) && $mime == 'image/jpeg')
        {
        try
          {
          $src = imagecreatefromjpeg($tmpfile);
          }
        catch (Exception $exc)
          {
          $createThumb = false; 
          }
        }
      else if(file_exists($tmpfile) && $mime == 'image/png')
        {
        try
          {
          $src = imagecreatefrompng($tmpfile);
          }
        catch (Exception $exc)
          {
          $createThumb = false; 
          }        
        }
      else if(file_exists($tmpfile) && $mime == 'image/gif')
        {
        try
          {
          $src = imagecreatefromgif($tmpfile);
          }
        catch (Exception $exc)
          {
          $createThumb = false; 
          }   
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

    
  /** save */
  public function save($dao)
    {
    if(!isset($dao->uuid) || empty($dao->uuid))
      {
      $dao->setUuid(uniqid() . md5(mt_rand()));
      }
    parent::save($dao);
    }
 
  
} // end class ItemRevisionModelBase
