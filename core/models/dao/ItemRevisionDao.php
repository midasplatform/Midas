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
 * Item revision DAO.
 *
 * @method int getItemrevisionId()
 * @method void setItemrevisionId(int $itemRevisionId)
 * @method int getItemId()
 * @method void setItemId(int $itemId)
 * @method int getRevision()
 * @method void setRevision(int $revision)
 * @method string getDate()
 * @method void setDate(string $date)
 * @method string getChanges()
 * @method void setChanges(string $changes)
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method int getLicenseId()
 * @method void setLicenseId(int $licenseId)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method array getBitstreams()
 * @method void setBitstreams(array $bitstreams)
 * @method ItemDao getItem()
 * @method void setItem(ItemDao $item)
 * @method UserDao getUser()
 * @method void setUser(UserDao $user)
 * @method LicenseDao getLicense()
 * @method void setLicense(LicenseDao $license)
 * @package Core\DAO
 */
class ItemRevisionDao extends AppDao
{
    /** @var string */
    public $_model = 'ItemRevision';
}
