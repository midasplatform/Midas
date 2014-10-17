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
 * Base model for storing scalar values related to a dashboard result. The
 * scalar is related to the result by its associated folder and item.
 */
abstract class Validation_ScalarResultModelBase extends Validation_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'validation_scalarresult';
        $this->_key = 'scalarresult_id';
        // need to add a_daoName because of conflict between
        // camel case ScalarResult class name
        // and
        // lowercase table name validation_scalarresult
        // so we need to explicitly set the capitalization in _daoName
        $this->_daoName = 'ScalarResultDao';

        $this->_mainData = array(
            'scalarresult_id' => array('type' => MIDAS_DATA),
            'folder_id' => array('type' => MIDAS_DATA),
            'item_id' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
            'folder' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Folder',
                'parent_column' => 'folder_id',
                'child_column' => 'folder_id',
            ),
            'item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'item_id',
                'child_column' => 'item_id',
            ),
        );
        $this->initialize(); // required
    }

    /**
     * Set the folder of the scalar result
     *
     * @param scalarResult the target scalar result
     * @param folder the target folder
     * @return void
     */
    public function setFolder($scalarResult, $folder)
    {
        if (!$scalarResult instanceof Validation_ScalarResultDao) {
            throw new Zend_Exception("Should be a scalar result.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $scalarResult->setFolderId($folder->getKey());
        parent::save($scalarResult);
    }

    /**
     * Set the item of the scalar result
     *
     * @param scalarResult the target scalar result
     * @param item the target item
     * @return void
     */
    public function setItem($scalarResult, $item)
    {
        if (!$scalarResult instanceof Validation_ScalarResultDao) {
            throw new Zend_Exception("Should be a scalar result.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }
        $scalarResult->setItemId($item->getKey());
        parent::save($scalarResult);
    }
}
