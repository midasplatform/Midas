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

require_once BASE_PATH.'/modules/thumbnailcreator/models/base/ItemthumbnailModelBase.php';

/**
 * Item thumbnail pdo model
 */
class Thumbnailcreator_ItemthumbnailModel extends Thumbnailcreator_ItemthumbnailModelBase
  {
  /**
   * Return an itemthumbnail dao based on an itemId.
   */
  public function getByItemId($itemId)
    {
    $sql = $this->database->select()->where('item_id = ?', $itemId);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Itemthumbnail', $row, 'thumbnailcreator');
    return $dao;
    }

  /**
   * Delete thumbnails on a given item. Called when item is about to be deleted.
   */
  public function deleteByItem($item)
    {
    Zend_Registry::get('dbAdapter')->delete($this->_name, 'item_id = '.$item->getKey());
    }
}
