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

/** FolderModelBase */
abstract class FolderModelBase extends AppModel
{
    /** Constrcutor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'folder';
        $this->_key = 'folder_id';

        $this->_components = array('Sortdao');

        $this->_mainData = array(
            'folder_id' => array('type' => MIDAS_DATA),
            'left_indice' => array('type' => MIDAS_DATA),
            'right_indice' => array('type' => MIDAS_DATA),
            'parent_id' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'description' => array('type' => MIDAS_DATA),
            'date_update' => array('type' => MIDAS_DATA),
            'date_creation' => array('type' => MIDAS_DATA),
            'view' => array('type' => MIDAS_DATA),
            'teaser' => array('type' => MIDAS_DATA),
            'privacy_status' => array('type' => MIDAS_DATA),
            'uuid' => array('type' => MIDAS_DATA),
            'items' => array(
                'type' => MIDAS_MANY_TO_MANY,
                'model' => 'Item',
                'table' => 'item2folder',
                'parent_column' => 'folder_id',
                'child_column' => 'item_id',
            ),
            'folderpolicygroup' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Folderpolicygroup',
                'parent_column' => 'folder_id',
                'child_column' => 'folder_id',
            ),
            'folderpolicyuser' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Folderpolicyuser',
                'parent_column' => 'folder_id',
                'child_column' => 'folder_id',
            ),
            'folders' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Folder',
                'parent_column' => 'folder_id',
                'child_column' => 'parent_id',
            ),
            'parent' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Folder',
                'parent_column' => 'parent_id',
                'child_column' => 'folder_id',
            ),
        );
        $this->initialize(); // required
    }

    /** Get children folders filtered */
    abstract public function getChildrenFoldersFiltered(
        $folder,
        $userDao = null,
        $policy = 0,
        $sortfield = 'name',
        $sortdir = 'asc',
        $limit = 0,
        $offset = 0
    );

    /** Get items filtered */
    abstract public function getItemsFiltered(
        $folder,
        $userDao = null,
        $policy = 0,
        $sortfield = 'name',
        $sortdir = 'asc',
        $limit = 0,
        $offset = 0
    );

    /** Get item by name */
    abstract public function getItemByName($folder, $itemname, $caseSensitive = true);

    /** Get size filtered */
    abstract public function getSizeFiltered($folders, $userDao = null, $policy = 0);

    /** Get community */
    abstract public function getCommunity($folder);

    /** Get user */
    abstract public function getUser($folder);

    /** Add item */
    abstract public function addItem($folder, $item);

    /** Move */
    abstract public function move($folder, $parent);

    /** Remove item */
    abstract public function removeItem($folder, $item);

    /** Check policy */
    abstract public function policyCheck($folderDao, $userDao = null, $policy = 0);

    /** Get folder exists */
    abstract public function getFolderExists($name, $parent);

    /** Get by UUID */
    abstract public function getByUuid($uuid);

    /** Get all */
    abstract public function getAll();

    /** Is deletable */
    abstract public function isDeleteable($folder);

    /** Get size */
    abstract public function getSize($folder);

    /** Get by name */
    abstract public function getByName($name);

    /** Iterate with callback */
    abstract public function iterateWithCallback($callback, $paramName = 'folder', $otherParams = array());

    /** Get recursive child count */
    abstract public function getRecursiveChildCount($folder);

    /** Get max policy */
    abstract public function getMaxPolicy($folderId, $user);

    /** Zip stream */
    abstract public function zipStream(&$zip, $path, $folder, &$userDao, &$overrideOutputFunction = null);

    /** Get the root folder */
    public function getRoot($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder");
        }

        $root = $folder;
        $parent = $folder->getParent();

        while ($parent !== false && intval($parent->getKey()) > 0) {
            $root = $parent;
            $parent = $parent->getParent();
        }

        return $root;
    }

    /** Increment the view count */
    public function incrementViewCount($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("folder should be instance of FolderDao.");
        }
        $user = Zend_Registry::get('userSession');
        if (isset($user)) {
            if (isset($user->viewedFolder[$folder->getKey()])) {
                return;
            } else {
                $user->viewedFolder[$folder->getKey()] = true;
            }
        }
        $folder->view++;
        parent::save($folder);
    }

    /** Create a folder */
    public function createFolder($name, $description, $parent, $uuid = '')
    {
        if (!$parent instanceof FolderDao && !is_numeric($parent)) {
            throw new Zend_Exception("Should be a folder.");
        }

        if (!is_string($name)) {
            throw new Zend_Exception("Name should be a string.");
        }

        if (empty($name) && $name !== '0') {
            throw new Zend_Exception("Name cannot be empty.");
        }

        if ($parent instanceof FolderDao) {
            $parentId = $parent->getFolderId();
        } else {
            $parentId = $parent;
            $parent = $this->load($parentId);
        }

        // Check if a folder with the same name already exists for the same parent
        if ($parent !== false && $this->getFolderExists($name, $parent)) {
            $existingfolder = $this->getFolderExists($name, $parent);

            return $existingfolder;
        }

        $folder = MidasLoader::newDao('FolderDao');
        $folder->setName($name);
        $folder->setDescription($description);
        $folder->setUuid($uuid);
        $folder->setParentId($parentId);
        $folder->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE); // Must default privacy flag to private
        $this->save($folder);

        return $folder;
    }

    /**
     * Count the bitstreams under this folder.
     * Returns array('size'=>size_in_bytes, 'count'=>total_number_of_bitstreams)
     */
    public function countBitstreams($folderDao, $userDao = null)
    {
        $totalSize = 0;
        $totalCount = 0;
        $subfolders = $this->getChildrenFoldersFiltered($folderDao, $userDao);
        foreach ($subfolders as $subfolder) {
            $subtotal = $this->countBitstreams($subfolder, $userDao);
            $totalSize += $subtotal['size'];
            $totalCount += $subtotal['count'];
        }

        $itemModel = MidasLoader::loadModel('Item');
        $items = $this->getItemsFiltered($folderDao, $userDao);
        foreach ($items as $item) {
            $subtotal = $itemModel->countBitstreams($item);
            $totalSize += $subtotal['size'];
            $totalCount += $subtotal['count'];
        }

        return array('size' => $totalSize, 'count' => $totalCount);
    }
}
