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

/**
 * \class ItemDao
 * \brief DAO Bitstream (table bitstream)
 */
class BitstreamDao extends AppDao
{
  public $_model = 'Bitstream';
  public $_components = array('MimeType', 'Utility');

  /** Fill the properties of the bitstream given the path.
   *  The file should be accessible from the web server.
   */
  function fillPropertiesFromPath()
    {
    // Check if the path exists
    if(!isset($this->path) || empty($this->path))
      {
      throw new Zend_Exception('BitstreamDao path is not set in fillPropertiesFromPath()');
      }

    // TODO Compute the full path from the assetstore. For now using the path
    $this->setMimetype($this->Component->MimeType->getType($this->path));
    // clear the stat cache, as the underlying file might have changed
    // since the last time filesize was called on the same filepath
    clearstatcache();
    $this->setSizebytes(filesize($this->path));
    if(!isset($this->checksum) || empty($this->checksum))
      {
      $this->setChecksum(UtilityComponent::md5file($this->path));
      }
    } // end fillPropertiesFromPath()

  /** Returns the full path of the bitstream based on the assetstore and the path of the bitstream */
  function getFullPath()
    {
    $assetstore = $this->get('assetstore');
    return $assetstore->getPath().'/'.$this->getPath();
    } // end function getFullPath()


} // end class
?>
