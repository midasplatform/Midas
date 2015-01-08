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

/** Apiitem Component for api methods */
class Thumbnailcreator_ApiitemComponent extends AppComponent
{
    /**
     * Create a big thumbnail for the given bitstream with the given width. It is used as the main image of the given item and shown in the item view page.
     *
     * @path /thumbnailcreator/item/bigthumbnail/{id}
     * @http PUT
     * @param bitstreamId The bitstream to create the thumbnail from
     * @param id The item to set the thumbnail on
     * @param width (Optional) The width in pixels to resize to (aspect ratio will be preserved). Defaults to 575
     * @return The ItemthumbnailDao object that was created
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function createBigThumbnail($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->renameParamKey($args, 'itemId', 'id', false);
        $apihelperComponent->validateParams($args, array('id'));

        /** @var Thumbnailcreator_ImagemagickComponent $imComponent */
        $imComponent = MidasLoader::loadComponent('Imagemagick', 'thumbnailcreator');

        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);

        $itemId = $args['id'];
        $bitstreamId = $args['bitstreamId'];
        $width = '575';
        if (isset($args['width'])) {
            $width = $args['width'];
        }

        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var Thumbnailcreator_ItemthumbnailModel $itemthumbnailModel */
        $itemthumbnailModel = MidasLoader::loadModel('Itemthumbnail', 'thumbnailcreator');

        $bitstream = $bitstreamModel->load($bitstreamId);
        $item = $itemModel->load($itemId);

        if (!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception(
                'You didn\'t log in or you don\'t have the write permission for the given item.',
                MIDAS_INVALID_POLICY
            );
        }

        $itemThumbnail = $itemthumbnailModel->getByItemId($item->getKey());
        if (!$itemThumbnail) {
            /** @var Thumbnailcreator_ItemthumbnailDao $itemThumbnail */
            $itemThumbnail = MidasLoader::newDao('ItemthumbnailDao', 'thumbnailcreator');
            $itemThumbnail->setItemId($item->getKey());
        } else {
            $oldThumb = $bitstreamModel->load($itemThumbnail->getThumbnailId());
            $bitstreamModel->delete($oldThumb);
        }

        try {
            $thumbnail = $imComponent->createThumbnailFromPath(
                $bitstream->getName(),
                $bitstream->getFullPath(),
                (int) $width,
                0,
                false
            );
            if (!file_exists($thumbnail)) {
                throw new Exception('Could not create thumbnail from the bitstream', MIDAS_INTERNAL_ERROR);
            }

            /** @var AssetstoreModel $assetstoreModel */
            $assetstoreModel = MidasLoader::loadModel('Assetstore');
            $thumb = $bitstreamModel->createThumbnail($assetstoreModel->getDefault(), $thumbnail);
            $itemThumbnail->setThumbnailId($thumb->getKey());
            $itemthumbnailModel->save($itemThumbnail);

            return $itemThumbnail->toArray();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), MIDAS_INTERNAL_ERROR);
        }
    }

    /**
     * Create a 100x100 small thumbnail for the given item. It is used for preview purpose and displayed in the 'preview' and 'thumbnails' sidebar sections.
     *
     * @path /thumbnailcreator/item/smallthumbnail/{id}
     * @http PUT
     * @param id The item to set the thumbnail on
     * @return The Item object (with the new thumbnail_id) and the path where the newly created thumbnail is stored
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function createSmallThumbnail($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->renameParamKey($args, 'itemId', 'id', false);
        $apihelperComponent->validateParams($args, array('id'));
        $itemId = $args['id'];

        /** @var Thumbnailcreator_ImagemagickComponent $imComponent */
        $imComponent = MidasLoader::loadComponent('Imagemagick', 'thumbnailcreator');

        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $item = $itemModel->load($itemId);
        if (!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception(
                'You didn\'t log in or you don\'t have the write permission for the given item.',
                MIDAS_INVALID_POLICY
            );
        }
        $revision = $itemModel->getLastRevision($item);
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) < 1) {
            throw new Exception(
                'The head revision of the item does not contain any bitstream', MIDAS_INVALID_PARAMETER
            );
        }
        $bitstream = $bitstreams[0];
        $name = $bitstream->getName();
        $fullPath = $bitstream->getFullPath();

        try {
            $pathThumbnail = $imComponent->createThumbnailFromPath($name, $fullPath, 100, 100, true);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), MIDAS_INTERNAL_ERROR);
        }

        if (file_exists($pathThumbnail)) {
            $itemModel->replaceThumbnail($item, $pathThumbnail);
        }

        return $item->toArray();
    }
}
