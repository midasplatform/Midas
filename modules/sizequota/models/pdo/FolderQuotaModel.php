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

require_once BASE_PATH.'/modules/sizequota/models/base/FolderQuotaModelBase.php';

/**
 * Folder quota pdo model
 */
class Sizequota_FolderQuotaModel extends Sizequota_FolderQuotaModelBase
{
  /** Returns the quota dao corresponding to the given folder, or false if none is set */
  public function getQuota($folder)
    {
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception('Parameter should be a folder.');
      }
    $sql = $this->database->select()->where('folder_id = ?', $folder->getKey());
    $row = $this->database->fetchRow($sql);
    return $this->initDao('FolderQuota', $row, 'sizequota');
    }

}
