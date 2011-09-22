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
 * Base model for storing scalar values related to a dashboard result. The
 * scalar is related to the result by its associated folder and item.
 */
abstract class Validation_ScalarResultModelBase extends Validation_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'validation_scalarresult';
    $this->_key = 'scalarresult_id';

    $this->_mainData = array(
        'scalarresult_id' => array('type' => MIDAS_DATA),
        'folder_id' => array('type' => MIDAS_DATA),
        'item_id' => array('type' => MIDAS_DATA),
        'value' => array('type' => MIDAS_DATA),
        'folder' =>  array('type' => MIDAS_MANY_TO_ONE,
                          'model' => 'Folder',
                          'parent_column' => 'folder_id',
                          'child_column' => 'folder_id'),
        'item' =>  array('type' => MIDAS_MANY_TO_ONE,
                          'model' => 'Item',
                          'parent_column' => 'item_id',
                          'child_column' => 'item_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  /**
   * Set the folder of the scalar result
   * @param scalarResult the target scalar result
   * @param folder the target folder
   * @return void
   */
  function setFolder($scalarResult, $folder)
    {
    if(!$scalarResult instanceof Validation_ScalarResultDao)
      {
      throw new Zend_Exception("Should be a scalar result.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $scalarResult->setFolderId($folder->getKey());
    parent::save($scalarResult);
    }

  /**
   * Set the item of the scalar result
   * @param scalarResult the target scalar result
   * @param item the target item
   * @return void
   */
  function setItem($scalarResult, $item)
    {
    if(!$scalarResult instanceof Validation_ScalarResultDao)
      {
      throw new Zend_Exception("Should be a scalar result.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    $scalarResult->setItemId($item->getKey());
    parent::save($scalarResult);
    }
}
