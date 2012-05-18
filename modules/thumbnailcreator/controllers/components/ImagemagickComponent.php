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

include_once BASE_PATH . '/library/KWUtils.php';
/** Component used to create thumbnails using phMagick library (on top of ImageMagick) */
class Thumbnailcreator_ImagemagickComponent extends AppComponent
{
  /**
   * Create a 100x100 thumbnail from an item.
   * Echoes an error message if a problem occurs (for the scheduler log)
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
    $name = $bitstream->getName();
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
      $pathThumbnail = $this->createThumbnailFromPath($name, $fullPath, 100, 100, true);
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
      $itemModel->replaceThumbnail($item, $pathThumbnail);
      }
    }

  /**
   * Create a thumbnail for the given file with the given width & height
   * @param name name of the image to create the thumbnail of
   * @param fullPath Absolute path to the image to create the thumbnail of
   * @param width Width to resize to (Set to 0 to preserve aspect ratio)
   * @param height Height to resize to (Set to 0 to preserve aspect ratio)
   * @param exact This will preserve aspect ratio by using a crop after the resize (Defaults to true)
   * @return The path where the thumbnail was created
   * @throws phMagickException if something goes wrong with the resize
   * @throws Exception if something else goes wrong
   */
  public function createThumbnailFromPath($name, $fullPath, $width, $height, $exact = true)
    {
    $ext = strtolower(substr(strrchr($name, '.'), 1));
    if(file_exists(BASE_PATH."/core/configs/thumbnailcreator.local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/thumbnailcreator.local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/thumbnailcreator/configs/module.ini', true);
      }
    $useThumbnailer = $applicationConfig['global']['useThumbnailer'];
    // get image formats which require thumbnailer to do pre-processing
    $preprocessedFormats = array_map('trim', explode(',', $applicationConfig['global']['imageFormats']));
    if(($useThumbnailer == "1") && in_array($ext, $preprocessedFormats))
      {
      // pre-process the file to get a temporary jpeg file and then feed it to image magick later.
      $preprecessedJpeg = $this->preprocessByThumbnailer($name, $fullPath);
      if(isset($preprecessedJpeg) && file_exists($preprecessedJpeg))
        {
        $fullPath = $preprecessedJpeg;
        $ext = strtolower(substr(strrchr($preprecessedJpeg, '.'), 1));
        }
      }
    // create destination
    $tmpPath = BASE_PATH.'/data/thumbnail';
    if(!file_exists($tmpPath))
      {
      throw new Zend_Exception('Temporary thumbnail dir does not exist: '.BASE_PATH.'/data/thumbnail/');
      }
    $destination = $tmpPath.'/'.rand(1, 10000).'.jpeg';
    while(file_exists($destination))
      {
      $destination = $tmpPath.'/'.rand(1, 10000).'.jpeg';
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
    // delete temerary file generated in pre-process step
    if(isset($preprecessedJpeg) && file_exists($preprecessedJpeg))
      {
      unlink($preprecessedJpeg);
      }
    return $pathThumbnail;
    }

  /**
   * Use thumbnailer to pre-process a bitstream to generate a jpeg file.
   * Echoes an error message if a problem occurs (for the scheduler log)
   * @param name name of the image to be pre-processed
   * @param fullPath Absolute path to the image to be pre-processed
   */
  public function preprocessByThumbnailer($name, $fullpath)
    {
    $tmpPath = BASE_PATH.'/data/thumbnail';
    if(!file_exists($tmpPath))
      {
      throw new Zend_Exception('Temporary thumbnail dir does not exist: '.BASE_PATH.'/data/thumbnail/');
      }
    
    $copyDestination = $tmpPath.'/'.$name;
    copy($fullpath, $copyDestination);

    $jpegDestination = $tmpPath.'/'.$name.'.jpeg';
    while(file_exists($jpegDestination))
      {
      $jpegDestination = $tmpPath.'/'.$name.rand(1, 10000).'.jpeg';
      }
    $modulesConfig = Zend_Registry::get('configsModules');
    $thumbnailerPath = $modulesConfig['thumbnailcreator']->thumbnailer;
    $thumbnailerParams = array($copyDestination, $jpegDestination);
    $thumbnailerCmd = KWUtils::prepareExeccommand($thumbnailerPath, $thumbnailerParams);
    if(KWUtils::isExecutable($thumbnailerPath))
      {
      KWUtils::exec($thumbnailerCmd);
      }
    else
      {
      throw new Zend_Exception('Thumbnailer does not exist or you do not have execute permission. Please check the configuration of thumbnailcreator module.');
      }

    if(!file_exists($jpegDestination))
      {
      throw new Zend_Exception('Problem executing thumbnailer on your system');
      return;
      }
    else
      {
      unlink($copyDestination);
      return $jpegDestination;
      }
    }

} // end class
