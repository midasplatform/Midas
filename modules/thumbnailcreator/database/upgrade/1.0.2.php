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
 * Moves all of our large item thumbnails into the default assetstore
 * as bitstreams.
 */
class Thumbnailcreator_Upgrade_1_0_2 extends MIDASUpgrade
{
    public $assetstore;

    public function preUpgrade()
    {
        $assetstoreModel = MidasLoader::loadModel('Assetstore');
        $this->assetstore = $assetstoreModel->getDefault();
    }

    public function mysql()
    {
        $this->db->query(
            "ALTER TABLE `thumbnailcreator_itemthumbnail` ADD COLUMN `thumbnail_id` bigint(20) NULL DEFAULT NULL"
        );

        $this->_moveAllThumbnails();

        $this->db->query("ALTER TABLE `thumbnailcreator_itemthumbnail` DROP `thumbnail`");
    }

    public function pgsql()
    {
        $this->db->query("ALTER TABLE thumbnailcreator_itemthumbnail ADD COLUMN thumbnail_id bigint NULL DEFAULT NULL");

        $this->_moveAllThumbnails();

        $this->db->query("ALTER TABLE thumbnailcreator_itemthumbnail DROP COLUMN thumbnail");
    }

    private function _moveAllThumbnails()
    {
        // Iterate through all existing item thumbnails
        $sql = $this->db->select()->from(array('thumbnailcreator_itemthumbnail'))->where('NOT thumbnail IS NULL', '');
        $rowset = $this->db->fetchAll($sql);
        foreach ($rowset as $row) {
            $id = $row['itemthumbnail_id'];
            $thumbnailBitstream = $this->_moveThumbnailToAssetstore($row['thumbnail']);
            if ($thumbnailBitstream !== null) {
                $this->db->update(
                    'thumbnailcreator_itemthumbnail',
                    array('thumbnail_id' => $thumbnailBitstream->getKey()),
                    array('itemthumbnail_id = ?' => $id)
                );
            }
        }
    }

    private function _moveThumbnailToAssetstore($thumbnail)
    {
        $bitstreamModel = MidasLoader::loadModel('Bitstream');

        $oldpath = BASE_PATH.'/'.$thumbnail;
        if (!file_exists($oldpath)
        ) { // thumbnail file no longer exists, so we remove its reference

            return null;
        }

        $bitstreamDao = $bitstreamModel->createThumbnail($this->assetstore, $oldpath);

        return $bitstreamDao;
    }
}
