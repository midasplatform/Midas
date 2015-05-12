<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
 * Bitstream DAO.
 *
 * @method int getBitstreamId()
 * @method void setBitstreamId(int $bitstreamId)
 * @method int getItemrevisionId()
 * @method void setItemrevisionId(int $itemRevisionId)
 * @method int getAssetstoreId()
 * @method void setAssetstoreId(int $assetStoreId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getMimetype()
 * @method void setMimetype(string $mimeType)
 * @method int getSizebytes()
 * @method void setSizebytes(int $sizeBytes)
 * @method string getChecksum()
 * @method void setChecksum(string $checksum)
 * @method string getPath()
 * @method void setPath(string $path)
 * @method string getDate()
 * @method void setDate(string $date)
 * @method ItemrevisionDao getItemrevision()
 * @method void setItemrevision(ItemrevisionDao $itemRevision)
 * @method AssetstoreDao getAssetstore()
 * @method void setAssetstore(AssetstoreDao $assetStore)
 */
class BitstreamDao extends AppDao
{
    /** @var string */
    public $_model = 'Bitstream';

    /** @var array */
    public $_components = array('MimeType', 'Utility');

    /**
     * Fill in the properties of this bitstream given its path. The file must
     * accessible by the web server.
     *
     * @deprecated
     * @throws Zend_Exception
     */
    public function fillPropertiesFromPath()
    {
        // Check if the path exists
        if (!isset($this->path) || empty($this->path)) {
            throw new Zend_Exception('BitstreamDao path is not set in fillPropertiesFromPath()');
        }

        // TODO: Compute the full path from the asset store. For now using the path.
        $this->setMimetype($this->Component->MimeType->getType($this->path));
        // clear the stat cache, as the underlying file might have changed
        // since the last time filesize was called on the same filepath
        clearstatcache();
        $this->setSizebytes(UtilityComponent::fileSize($this->path));
        if (!isset($this->checksum) || empty($this->checksum)) {
            $this->setChecksum(UtilityComponent::md5file($this->path));
        }
    }

    /**
     * Return the full path of this bitstream from its asset store and the
     * relative path of the bitstream.
     *
     * @deprecated
     * @return string
     */
    public function getFullPath()
    {
        $assetstore = $this->get('assetstore');

        return $assetstore->getPath().'/'.$this->getPath();
    }
}
