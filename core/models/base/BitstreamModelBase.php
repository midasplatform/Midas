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

/** Bitstream Base Model*/
abstract class BitstreamModelBase extends AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'bitstream';
    $this->_key = 'bitstream_id';

    $this->_mainData = array(
      'bitstream_id' =>  array('type' => MIDAS_DATA),
      'itemrevision_id' =>  array('type' => MIDAS_DATA),
      'assetstore_id' =>  array('type' => MIDAS_DATA),
      'name' =>  array('type' => MIDAS_DATA),
      'mimetype' =>  array('type' => MIDAS_DATA),
      'sizebytes' =>  array('type' => MIDAS_DATA),
      'checksum' =>  array('type' => MIDAS_DATA),
      'path' =>  array('type' => MIDAS_DATA),
      'assetstore_id' =>  array('type' => MIDAS_DATA),
      'date' =>  array('type' => MIDAS_DATA),
      'itemrevision' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'ItemRevision', 'parent_column' => 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      'assetstore' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Assetstore', 'parent_column' => 'assetstore_id', 'child_column' => 'assetstore_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getByChecksum($checksum);

  /** save */
  public function save($dao)
    {
    if(!isset($dao->date) || empty($dao->date))
      {
      $dao->setDate(date('c'));
      }
    parent::save($dao);
    }

  /** delete a Bitstream*/
  function delete($bitstream)
    {
    if(!$bitstream instanceof BitstreamDao)
      {
      throw new Zend_Exception('Must pass a bitstream dao');
      }
    $checksum = $bitstream->getChecksum();
    $path = $bitstream->getFullPath();
    $assetstore = $bitstream->getAssetstore();
    parent::delete($bitstream);
    if(file_exists($path) && $assetstore->getType() != MIDAS_ASSETSTORE_REMOTE
       && $this->getByChecksum($checksum) == false)
      {
      unlink($path);
      }
    $bitstream->saved = false;
    unset($bitstream->bitstream_id);
    }

  /**
   * Create a thumbnail bitstream in the provided assetstore using the
   * passed tempThumbnailFile, which will be moved to the assetstore.
   * @return The bitstream dao that was created for the thumbnail
   */
  public function createThumbnail($assetstore, $tempThumbnailFile)
    {
    $this->loadDaoClass('BitstreamDao');
    $bitstreamDao = new BitstreamDao;

    $md5 = md5_file($tempThumbnailFile);
    $bitstreamDao->setName('thumbnail.jpeg');
    $bitstreamDao->setItemrevisionId(-1); //-1 indicates this does not belong to any revision
    $bitstreamDao->setMimetype('image/jpeg');
    $bitstreamDao->setSizebytes(filesize($tempThumbnailFile));
    $bitstreamDao->setDate(date('c'));
    $bitstreamDao->setChecksum($md5);

    $existing = $this->getByChecksum($md5);
    if($existing)
      {
      unlink($tempThumbnailFile);
      $bitstreamDao->setPath($existing->getPath());
      $bitstreamDao->setAssetstoreId($existing->getAssetstoreId());
      }
    else
      {
      $path = substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.$md5;
      $fullpath = $assetstore->getPath().'/'.$path;

      $currentdir = $assetstore->getPath().'/'.substr($md5, 0, 2);
      $this->_createAssetstoreDirectory($currentdir);
      $currentdir .= '/'.substr($md5, 2, 2);
      $this->_createAssetstoreDirectory($currentdir);
      rename($tempThumbnailFile, $fullpath);

      $bitstreamDao->setAssetstoreId($assetstore->getKey());
      $bitstreamDao->setPath($path);
      }

    $this->save($bitstreamDao);
    return $bitstreamDao;
    }

  /** Helper function to create the two-level hierarchy in the assetstore */
  private function _createAssetstoreDirectory($directorypath)
    {
    if(!file_exists($directorypath))
      {
      if(!mkdir($directorypath))
        {
        throw new Zend_Exception("Cannot create directory: ".$directorypath);
        }
      chmod($directorypath, 0777);
      }
    }
} // end class BitstreamModelBase
