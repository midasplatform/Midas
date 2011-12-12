<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

class Thumbnailcreator_ImagemagickComponent extends AppComponent
{ 
  /** createThumbnail */
  public function createThumbnail($item,$inputFile=null)
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
    $fullPath = null;
    if($inputFile)
      {
      $fullPath = $inputFile;
      }
    else
      {
      $fullPath = $bitstream->getFullPath();
      }
    $ext = null;
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
          $p->acquireFrame($fullPath, 0);
          $p->resizeExactly(100,100);
          break;
          break;
        default:
          $p = new phMagick($fullPath, $pathThumbnail);
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
      echo $exc->getMessage();
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
