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

class Thumbnailcreator_ImagemagickComponent extends AppComponent
{ 
  /** createThumbnail */
  public function createThumbnail($item)
    {
    $modelLoader = new MIDAS_ModelLoader;
    $itemModel = $modelLoader->loadModel("Item");  
    $item = $itemModel->load($item['item_id']);
    $revision = $itemModel->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) != 1)
      {
      return;
      }
    $bitstream = $bitstreams[0];
    $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
    
    // create destination
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
    $pathThumbnail = $destionation;
    
    require_once BASE_PATH.'/modules/thumbnailcreator/library/Phmagick/phmagick.php';    
    $modulesConfig=Zend_Registry::get('configsModules');
    $imageMagickPath = $modulesConfig['thumbnailcreator']->imagemagick;
    
    // try to create a thumbnail (generic way)
    try
      {
      switch($ext)
        {
        case "pdf":
        case "mpg":
        case "mpeg":
        case "mp4":
        case "m4v":
        case "avi":
        case "mov":
        case "flv":
        case "mp4":
        case "rm":
          $p = new phMagick("", $pathThumbnail);
          $p->setImageMagickPath($imageMagickPath);
          $p->acquireFrame($bitstream->getFullPath(), 0);
          $p->resizeExactly(100,100);
          break;
          break;
        default:
          $p = new phMagick($bitstream->getFullPath(), $pathThumbnail);
          $p->setImageMagickPath($imageMagickPath);
          $p->resizeExactly(100,100);
        }
      }
    catch (phMagickException $exc)
      {
      echo $exc->getMessage();
      }
    catch (Exception $exc)
      {

      }
      
    if(file_exists($pathThumbnail))
      {
      $oldThumbnail = $item->getThumbnail();
      if(!empty($oldThumbnail))
        {
        unlink($oldThumbnail);
        }
      $item->setThumbnail(substr($pathThumbnail, strlen(BASE_PATH) + 1));
      $itemModel->save($item);
      }   
    return;
    }
    
} // end class
?>