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
      throw new Zend_Exception("Error param.");
      }
    $checksum = $bitstream->getChecksum();
    $path = $bitstream->getFullPath();
    $assetstore = $bitstream->getAssetstore();    
    parent::delete($bitstream);
    if($assetstore->getType() != MIDAS_ASSETSTORE_REMOTE && $this->getByChecksum($checksum) == false)
      {
      unlink($path);
      }
    $bitstream->saved = false;
    unset($bitstream->bitstream_id);
    }
} // end class BitstreamModelBase