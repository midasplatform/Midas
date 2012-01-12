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
require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
class Visualize_MainComponent extends AppComponent
{
  /** can visualize */
  public function canVisualizeWithParaview($itemDao)
    {
    $modulesConfig=Zend_Registry::get('configsModules');
    $useparaview = $modulesConfig['visualize']->useparaview;
    if(!isset($useparaview) || !$useparaview)
      {
      return false;
      }

    $extensions = array('vtk', 'ply', 'vtp', 'pvsm', 'mha', 'vtu');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }

    $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));
    return in_array($ext, $extensions);
    }//end canVisualize

  /* visualize*/
  public function canVisualizeTxt($itemDao)
    {
    $extensions = array('txt', 'php', 'js', 'html', 'cpp', 'java', 'py', 'h', 'log');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }

    $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));
    return in_array($ext, $extensions);
    }//end canVisualize

  /* visualize*/
  public function canVisualizePdf($itemDao)
    {
    $extensions = array('pdf');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }

    $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));
    return in_array($ext, $extensions);
    }//end canVisualize

  /* visualize*/
  public function canVisualizeImage($itemDao)
    {
    $extensions = array('jpg', 'jpeg', 'gif', 'bmp', 'png');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }

    $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));
    return in_array($ext, $extensions);
    }//end canVisualize

  /** can visualize */
  public function canVisualizeMedia($itemDao)
    {
    $extensions = array('m4a', 'm4v', 'mp3', 'mp4', 'avi');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }

    $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));
    return in_array($ext, $extensions);
    }//end canVisualize


  /** processParaviewData*/
  public function processParaviewData($itemDao)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    if(!is_object($itemDao))
      {
      $itemDao = $itemModel->load($itemDao['item_id']);
      }
    if(!$this->canVisualizeWithParaview($itemDao))
      {
      return;
      }

    $modulesConfig=Zend_Registry::get('configsModules');
    $pwapp = $modulesConfig['visualize']->pwapp;
    $pvbatch = $modulesConfig['visualize']->pvbatch;
    $usesymlinks = $modulesConfig['visualize']->usesymlinks;
    $paraviewworkdir = $modulesConfig['visualize']->paraviewworkdir;
    $customtmp = $modulesConfig['visualize']->customtmp;

    if(empty($pwapp) || empty($pvbatch))
      {
      return;
      }

    $pathArray = $this->createParaviewPath();
    $path = $pathArray['path'];
    $tmpFolderName = $pathArray['foderName'];

    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $bitstream)
      {
      if($usesymlinks)
        {
        symlink($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
        }
      else
        {
        copy($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
        }

      $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
      if($ext != 'pvsm')
        {
        $filePath = $paraviewworkdir."/".$tmpFolderName.'/'.$bitstream->getName();
        $mainBitstream = $bitstream;
        }
      }

    foreach($bitstreams as $bitstream)
      {
      $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
      if($ext == 'pvsm')
        {
        $file_contents = file_get_contents($path.'/'.$bitstream->getName());
        $file_contents = preg_replace('/\"([a-zA-Z0-9_.\/\\\:]{1,1000})'.  str_replace('.', '\.', $mainBitstream->getName())."/", '"'.$filePath, $file_contents);
        $filePath = $paraviewworkdir."/".$tmpFolderName.'/'.$bitstream->getName();
        $inF = fopen($path.'/'.$bitstream->getName(),"w");
        fwrite($inF, $file_contents);
        fclose($inF);
        $this->view->json['visualize']['openState'] = true;
        break;
        }
      }

    $tmpPath = UtilityComponent::getTempDirectory();
    if(file_exists($tmpPath.'/screenshot1.png'))
      {
      unlink($tmpPath.'/screenshot1.png');
      }
    if(file_exists($tmpPath.'/screenshot2.png'))
      {
      unlink($tmpPath.'/screenshot2.png');
      }
    if(file_exists($tmpPath.'/screenshot4.png'))
      {
      unlink($tmpPath.'/screenshot4.png');
      }
    if(file_exists($tmpPath.'/screenshot3.png'))
      {
      unlink($tmpPath.'/screenshot3.png');
      }

    $return  = file_get_contents(str_replace("PWApp", 'processData', $pwapp)."?file=".$filePath."&pvbatch=".$pvbatch);
    if(strpos($return, 'PROBLEME') !== false)
      {
      return;
      }
    copy(str_replace("PWApp", 'processData', $pwapp)."/screenshot1.png", $tmpPath.'/screenshot1.png');
    copy(str_replace("PWApp", 'processData', $pwapp)."/screenshot2.png", $tmpPath.'/screenshot2.png');
    copy(str_replace("PWApp", 'processData', $pwapp)."/screenshot4.png", $tmpPath.'/screenshot4.png');
    copy(str_replace("PWApp", 'processData', $pwapp)."/screenshot3.png", $tmpPath.'/screenshot3.png');

    $json = file_get_contents(str_replace("PWApp", 'processData', $pwapp)."/metadata.txt");
    //copy(str_replace("PWApp", 'processData', $pwapp)."/metadata.txt", $tmpPath.'/metadata.txt');
    $metadata = json_decode($json);

    $MetadataModel = $modelLoader->loadModel('Metadata');

    $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'image', 'type');
    if(!$metadataDao)
      {
      $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'image', 'type', '');
      }
    $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 'image', 'type', $metadata[0]);

    $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'image', 'points');
    if(!$metadataDao)
      {
      $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'image', 'points', '');
      }
    $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 'image', 'points', $metadata[1]);

    $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'image', 'cells');
    if(!$metadataDao)
      {
      $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'image', 'cells', '');
      }
    $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 'image', 'cells', $metadata[2]);

    $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'image', 'polygons');
    if(!$metadataDao)
      {
      $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'image', 'polygons', '');
      }

    $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 'image', 'polygons', $metadata[3]);

    $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'image', 'x-range');
    if(!$metadataDao)
      {
      $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'image', 'x-range', '');
      }

    $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 'image', 'x-range', $metadata[4][0].' to '.$metadata[4][1]);
    $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'image', 'y-range');
    if(!$metadataDao)
      {
      $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'image', 'y-range', '');
      }

    $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 'image', 'y-range', $metadata[4][2].' to '.$metadata[4][3]);

    // create thumbnail
    try
      {
      $src = imagecreatefrompng($tmpPath.'/screenshot1.png');
      }
    catch(Exception $exc)
      {
      return;
      }

    $thumbnailPath = BASE_PATH.'/data/thumbnail/'.rand(1, 1000);
    if(!file_exists(BASE_PATH.'/data/thumbnail/'))
      {
      throw new Zend_Exception("Problem thumbnail path: ".BASE_PATH.'/data/thumbnail/');
      }
    if(!file_exists($thumbnailPath))
      {
      mkdir($thumbnailPath);
      }
    $thumbnailPath .= '/'.rand(1, 1000);
    if(!file_exists($thumbnailPath))
      {
      mkdir($thumbnailPath);
      }
    $destionation = $thumbnailPath."/".rand(1, 1000).'.jpeg';
    while(file_exists($destionation))
      {
      $destionation = $thumbnailPath."/".rand(1, 1000).'.jpeg';
      }
    $pathThumbnail = $destionation;

    list ($x, $y) = getimagesize($tmpPath.'/screenshot1.png');  //--- get size of img ---
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

    $thb = imagecreatetruecolor($tx, $ty);  //--- create thumbnail ---
    imagecopyresampled($thb, $src, 0, 0, 0, 0, $tx, $ty, $x, $y);
    imagejpeg($thb, $pathThumbnail, 80);
    imagedestroy($thb);
    imagedestroy($src);

    $oldThumbnail = $itemDao->getThumbnail();
    if(!empty($oldThumbnail))
      {
      unlink($oldThumbnail);
      }
    $itemDao->setThumbnail(substr($pathThumbnail, strlen(BASE_PATH) + 1));
    $itemModel->save($itemDao);

    $data_dir = BASE_PATH.'/data/visualize/';
    if(!file_exists($data_dir))
      {
      mkdir($data_dir);
      }
    rename($tmpPath.'/screenshot1.png', $data_dir.'_'.$itemDao->getKey().'_1.png');
    rename($tmpPath.'/screenshot2.png', $data_dir.'_'.$itemDao->getKey().'_2.png');
    rename($tmpPath.'/screenshot3.png', $data_dir.'_'.$itemDao->getKey().'_3.png');
    rename($tmpPath.'/screenshot4.png', $data_dir.'_'.$itemDao->getKey().'_4.png');

    }

  /** createParaviewPath*/
  public function createParaviewPath()
    {
    $modulesConfig=Zend_Registry::get('configsModules');
    $customtmp = $modulesConfig['visualize']->customtmp;
    if(isset($customtmp) && !empty($customtmp))
      {
      $tmp_dir = $customtmp;
      if(!file_exists($tmp_dir) || !is_writable($tmp_dir))
        {
        throw new Zend_Exception('Unable to access temp dir');
        }
      }
    else
      {
      if(!file_exists(UtilityComponent::getTempDirectory().'/visualize'))
        {
        mkdir(UtilityComponent::getTempDirectory().'/visualize');
        }
      $tmp_dir = UtilityComponent::getTempDirectory().'/visualize';
      }

    $dir = opendir($tmp_dir);
    while($entry = readdir($dir))
      {
      if(is_dir($tmp_dir.'/'.$entry) && filemtime($tmp_dir.'/'.$entry) < strtotime('-1 hours') && !in_array($entry, array('.','..')))
        {
        if(strpos($entry, 'Paraview') !== false)
          {
          $this->rrmdir($tmp_dir.'/'.$entry);
          }
        }
      }
    do
      {
      $tmpFolderName = 'ParaviewWeb_'.mt_rand(0, 9999999);
      $path = $tmp_dir.'/'.$tmpFolderName;
      }
    while (!mkdir($path));
    return array('path' => $path, 'foderName' => $tmpFolderName);
    }


  /** recursively delete a folder*/
  private function rrmdir($dir)
    {
    if(is_dir($dir))
      {
      $objects = scandir($dir);
      }

    foreach($objects as $object)
      {
      if($object != "." && $object != "..")
        {
        if(filetype($dir."/".$object) == "dir")
          {
          $this->rrmdir($dir."/".$object);
          }
        else
          {
          unlink($dir."/".$object);
          }
        }
      }
     reset($objects);
     rmdir($dir);
   }
} // end class
?>