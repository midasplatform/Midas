<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
