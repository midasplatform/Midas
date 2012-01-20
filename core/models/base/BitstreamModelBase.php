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
} // end class BitstreamModelBase