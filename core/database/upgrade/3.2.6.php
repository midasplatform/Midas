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
 * Upgrade the core to version 3.2.6. Move item thumbnails into the default
 * asset store as bitstreams.
 */
class Upgrade_3_2_6 extends MIDASUpgrade
{
    public $assetstore;

    /** Pre database upgrade. */
    public function preUpgrade()
    {
        /** @var AssetstoreModel $assetStoreModel */
        $assetStoreModel = MidasLoader::loadModel('Assetstore');
        try {
            $this->assetstore = $assetStoreModel->getDefault();
        } catch (Exception $e) {
            // DO NOTHING
        }
    }

    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query('ALTER TABLE `item` ADD COLUMN `thumbnail_id` bigint(20) NULL DEFAULT NULL;');

        $this->_moveAllThumbnails();

        $this->db->query('ALTER TABLE `item` DROP `thumbnail`;');
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query('ALTER TABLE item ADD COLUMN thumbnail_id bigint NULL DEFAULT NULL;');

        $this->_moveAllThumbnails();

        $this->db->query('ALTER TABLE item DROP COLUMN thumbnail;');
    }

    private function _moveAllThumbnails()
    {
        // Iterate through all existing items that have thumbnails
        $sql = $this->db->select()
            ->from(array('item'))
            ->where('thumbnail != ?', '');
        $rowset = $this->db->fetchAll($sql);
        foreach ($rowset as $row) {
            $itemId = $row['item_id'];
            $thumbnailBitstream = $this->_moveThumbnailToAssetstore($row['thumbnail']);
            if ($thumbnailBitstream !== null) {
                $this->db->update('item',
                    array('thumbnail_id' => $thumbnailBitstream->getKey()),
                    array('item_id = ?' => $itemId));
            }
        }
    }

    private function _moveThumbnailToAssetstore($thumbnail)
    {
        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');

        $oldpath = BASE_PATH.'/'.$thumbnail;
        if (!file_exists($oldpath)) { //thumbnail file no longer exists, so we remove its reference
            return;
        }

        $bitstreamDao = $bitstreamModel->createThumbnail($this->assetstore, $oldpath);

        return $bitstreamDao;
    }
}
