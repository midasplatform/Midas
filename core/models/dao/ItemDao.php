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
 * Item DAO.
 *
 * @method int getItemId()
 * @method void setItemId(int $itemId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method int getType()
 * @method void setType(int $type)
 * @method int getSizebytes()
 * @method void setSizebytes(int $sizeBytes)
 * @method string getDateCreation()
 * @method void setDateCreation(string $dateCreation)
 * @method string getDateUpdate()
 * @method void setDateUpdate(string $dateUpdate)
 * @method int getThumbnailId()
 * @method void setThumbnailId(int $thumbnailId)
 * @method int getView()
 * @method void setView(int $view)
 * @method int getDownload()
 * @method void setDownload(int $download)
 * @method int getPrivacyStatus()
 * @method void setPrivacyStatus(int $privacyStatus)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method array getFolders()
 * @method void setFolders(array $folders)
 * @method array getRevisions()
 * @method void setRevisions(array $revisions)
 * @method array getItempolicygroup()
 * @method void setItempolicygroup(array $itemPolicyGroup)
 * @method array getItempolicyuser()
 * @method void setItempolicyuser(array $itemPolicyUser)
 * @package Core\DAO
 */
class ItemDao extends AppDao
{
    /** @var string */
    public $_model = 'Item';
}
