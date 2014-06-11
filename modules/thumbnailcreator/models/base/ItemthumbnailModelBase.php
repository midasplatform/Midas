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

/**
 * Item thumbnail model base
 */
abstract class Thumbnailcreator_ItemthumbnailModelBase extends Thumbnailcreator_AppModel
  {
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'thumbnailcreator_itemthumbnail';
    $this->_key = 'itemthumbnail_id';

    $this->_mainData = array(
       'itemthumbnail_id' => array('type' => MIDAS_DATA),
       'item_id' => array('type' => MIDAS_DATA),
       'thumbnail_id' => array('type' => MIDAS_DATA),
       'item' => array('type' => MIDAS_MANY_TO_ONE,
                       'model' => 'Item',
                       'parent_column' => 'item_id',
                       'child_column' => 'item_id'));
    $this->initialize();
    }

  abstract public function getByItemId($itemId);
  abstract public function deleteByItem($item);
  }
