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

/** Component used to create thumbnails using phMagick library (on top of ImageMagick) */
class Thumbnailcreator_ImagemagickComponent extends AppComponent
{
  /**
   * Create a 100x100 thumbnail from an item. Called by the scheduler, will
   * echo an error message if a problem occurs
   * @param item The item to create the thumbnail for
   * @param inputFile (optional) The file to thumbnail. If none is specified, uses
   *                             the first bitstream in the head revision of the item.
   */
  public function createThumbnail($item, $inputFile = null)
    {
    $modelLoader = new MIDAS_ModelLoader;
    $itemModel = $modelLoader->loadModel('Item');
    if(is_array($item))
      {
      $item = $itemModel->load($item['item_id']);
      }
    $revision = $itemModel->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) < 1)
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

    try
      {
      $pathThumbnail = $this->createThumbnailFromPath($fullPath, 100, 100, true);
      }
    catch(phMagickException $exc)
      {
      return;
      }
    catch(Exception $exc)
      {
      return;
      }

    if(file_exists($pathThumbnail))
      {
      $oldThumbnail = $item->getThumbnail();
      if(!empty($oldThumbnail) && file_exists($pathThumbnail))
        {
        unlink($oldThumbnail);
        }
      $item->setThumbnail(substr($pathThumbnail, strlen(BASE_PATH) + 1));
      $itemModel->save($item);
      }
    }

  /**
   * Create a thumbnail for the given file with the given width & height
   * @param fullPath Absolute path to the image to create the thumbnail of
   * @param width Width to resize to (Set to 0 to preserve aspect ratio)
   * @param height Height to resize to (Set to 0 to preserve aspect ratio)
   * @param exact This will preserve aspect ratio by using a crop after the resize (Defaults to true)
   * @return The path where the thumbnail was created
   * @throws phMagickException if something goes wrong with the resize
   * @throws Exception if something else goes wrong
   */
  public function createThumbnailFromPath($fullPath, $width, $height, $exact = true)
    {
    $ext = strtolower(substr(strrchr($fullPath, '.'), 1));

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
    $num = '0';
    $destination = $tmpPath.'/'.$num.'.jpeg';
    while(file_exists($destination))
      {
      $num++;
      $destination = $tmpPath.'/'.$num.'.jpeg';
      }
    $pathThumbnail = $destination;

    require_once BASE_PATH.'/modules/thumbnailcreator/library/Phmagick/phmagick.php';
    $modulesConfig = Zend_Registry::get('configsModules');
    $imageMagickPath = $modulesConfig['thumbnailcreator']->imagemagick;

    switch($ext)
      {
      case 'pdf':
      case 'mpg':
      case 'mpeg':
      case 'mp4':
      case 'm4v':
      case 'avi':
      case 'mov':
      case 'flv':
      case 'mp4':
      case 'rm':
        // Use first frame if this is a video
        $p = new phMagick('', $pathThumbnail);
        $p->setImageMagickPath($imageMagickPath);
        $p->acquireFrame($fullPath, 0);
        if($exact)
          {
          //preserve aspect ratio by performing a crop after the resize
          $p->resizeExactly($width, $height);
          }
        else
          {
          $p->resize($width, $height);
          }
        break;
      default:
        // Otherwise it is just a normal image
        $p = new phMagick($fullPath, $pathThumbnail);
        $p->setImageMagickPath($imageMagickPath);
        if($exact)
          {
          //preserve aspect ratio by performing a crop after the resize
          $p->resizeExactly($width, $height);
          }
        else
          {
          $p->resize($width, $height);
          }
        break;
      }
    return $pathThumbnail;
    }

} // end class
