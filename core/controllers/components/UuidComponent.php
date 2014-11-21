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

/** UUID component for generating UUIDs and searching by UUID. */
class UuidComponent extends AppComponent
{
    /**
     * Generate a version 4 UUID.
     *
     * @return string
     */
    public function generate()
    {
        return str_replace('-', '', \Rhumsaa\Uuid\Uuid::uuid4()->toString());
    }

    /**
     * Return a resource given its unique id.
     *
     * @param string $uuid UUID
     * @return false|CommunityDao|FolderDao|ItemDao|ItemRevisionDao|UserDao
     */
    public function getByUid($uuid)
    {
        /** @var CommunityModel $model */
        $model = MidasLoader::loadModel('Community');
        $dao = $model->getByUuid($uuid);

        if ($dao !== false) {
            $dao->resourceType = MIDAS_RESOURCE_COMMUNITY;

            return $dao;
        }

        /** @var FolderModel $model */
        $model = MidasLoader::loadModel('Folder');
        $dao = $model->getByUuid($uuid);

        if ($dao !== false) {
            $dao->resourceType = MIDAS_RESOURCE_FOLDER;

            return $dao;
        }

        /** @var ItemModel $model */
        $model = MidasLoader::loadModel('Item');
        $dao = $model->getByUuid($uuid);

        if ($dao !== false) {
            $dao->resourceType = MIDAS_RESOURCE_ITEM;

            return $dao;
        }

        /** @var ItemRevisionModel $model */
        $model = MidasLoader::loadModel('ItemRevision');
        $dao = $model->getByUuid($uuid);

        if ($dao !== false) {
            $dao->resourceType = MIDAS_RESOURCE_REVISION;

            return $dao;
        }

        /** @var UserModel $model */
        $model = MidasLoader::loadModel('User');
        $dao = $model->getByUuid($uuid);

        if ($dao !== false) {
            $dao->resourceType = MIDAS_RESOURCE_USER;

            return $dao;
        }

        return false;
    }
}
