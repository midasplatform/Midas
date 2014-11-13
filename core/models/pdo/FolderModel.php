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

require_once BASE_PATH.'/core/models/base/FolderModelBase.php';

/**
 * Pdo Model
 */
class FolderModel extends FolderModelBase
{
    /** get All */
    public function getAll()
    {
        $rowset = $this->database->fetchAll($this->database->select()->order(array('folder_id DESC')));
        $results = array();
        foreach ($rowset as $row) {
            $results[] = $this->initDao('Folder', $row);
        }

        return $results;
    }

    /** Get the total number of folders in the database */
    public function getTotalCount()
    {
        $row = $this->database->fetchRow($this->database->select()->from('folder', array('count' => 'count(*)')));

        return $row['count'];
    }

    /** get by uuid */
    public function getByUuid($uuid)
    {
        $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }

    /** get by name */
    public function getByName($name)
    {
        $row = $this->database->fetchRow($this->database->select()->where('name = ?', $name));
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }

    /**
     * Call a callback function on every folder in the database
     *
     * @param callback Name of the Midas callback to call
     * @param paramName what parameter name the folder should be passed as to the callback (default is 'folder')
     */
    public function iterateWithCallback($callback, $paramName = 'folder', $otherParams = array())
    {
        $rowset = $this->database->fetchAll();
        foreach ($rowset as $row) {
            $folder = $this->initDao('Folder', $row);
            $params = array_merge($otherParams, array($paramName => $folder));
            Zend_Registry::get('notifier')->callback($callback, $params);
        }
    }

    /** check if the policy is valid */
    public function policyCheck($folderDao, $userDao = null, $policy = 0)
    {
        if (!$folderDao instanceof FolderDao || !is_numeric($policy)) {
            throw new Zend_Exception("Error in params when checking Folder Policy.");
        }
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $userDao->getUserId();
            if ($userDao->isAdmin()) {
                return true;
            }
        }

        $subqueryUser = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'folderpolicyuser'),
            array('folder_id')
        )->where(
            'policy >= ?',
            $policy
        )->where('p.folder_id = ?', $folderDao->getKey())->where('user_id = ? ', $userId);

        $subqueryGroup = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'folderpolicygroup'),
            array('folder_id')
        )->where(
            'policy >= ?',
            $policy
        )->where('p.folder_id = ?', $folderDao->getKey())->where(
            '( '.$this->database->getDB()->quoteInto(
                'group_id = ? ',
                MIDAS_GROUP_ANONYMOUS_KEY
            ).' OR
                              group_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('u2g' => 'user2group'),
                    array('group_id')
                )->where('u2g.user_id = ?', $userId).'))'
            )
        );

        $sql = $this->database->select()->union(array($subqueryUser, $subqueryGroup));
        $row = $this->database->fetchRow($sql);
        if ($row == null) {
            return false;
        }

        return true;
    }

    /**
     * Get the maximum policy level for the given folder and user.
     */
    public function getMaxPolicy($folderId, $user)
    {
        $maxPolicy = -1;
        if ($user) {
            if ($user->isAdmin()) {
                return MIDAS_POLICY_ADMIN;
            }
            $userId = $user->getKey();
            $sql = $this->database->select()->setIntegrityCheck(false)->from(
                'folderpolicyuser',
                array('maxpolicy' => 'max(policy)')
            )->where(
                'folder_id = ?',
                $folderId
            )->where('user_id = ? ', $userId);
            $row = $this->database->fetchRow($sql);
            if ($row != null && $row['maxpolicy'] > $maxPolicy) {
                $maxPolicy = $row['maxpolicy'];
            }
        } else {
            $userId = -1;
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'folderpolicygroup'),
            array('maxpolicy' => 'max(policy)')
        )->where('p.folder_id = ?', $folderId)->where(
            '( '.$this->database->getDB()->quoteInto(
                'group_id = ?',
                MIDAS_GROUP_ANONYMOUS_KEY
            ).' OR group_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('u2g' => 'user2group'),
                    array('group_id')
                )->where('u2g.user_id = ?', $userId).'))'
            )
        );
        $row = $this->database->fetchRow($sql);
        if ($row != null && $row['maxpolicy'] > $maxPolicy) {
            $maxPolicy = $row['maxpolicy'];
        }

        return $maxPolicy;
    }

    /**
     * Get the total number of folders and items contained within this folder,
     * irrespective of policies
     */
    public function getRecursiveChildCount($folder)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('f' => 'folder'),
            array('count' => 'count(*)')
        )->where(
            'left_index > ?',
            $folder->getLeftIndex()
        )->where('right_index < ?', $folder->getRightIndex());
        $row = $this->database->fetchRow($sql);
        $folderChildCount = $row['count'];

        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('i2f' => 'item2folder'),
            array('count' => 'count(*)')
        )->where(
            'i2f.folder_id = '.$folder->getKey().' OR i2f.folder_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('f' => 'folder'),
                    array('folder_id')
                )->where('left_index > ?', $folder->getLeftIndex())->where(
                    'right_index < ?',
                    $folder->getRightIndex()
                )
            ).')'
        );
        $row = $this->database->fetchRow($sql);
        $itemChildCount = $row['count'];

        return $folderChildCount + $itemChildCount;
    }

    /** get the size and the number of item in a folder */
    public function getSizeFiltered($folders, $userDao = null, $policy = 0)
    {
        $isAdmin = false;
        if (!is_array($folders)) {
            $folders = array($folders);
        }
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $userDao->getUserId();
            if ($userDao->isAdmin()) {
                $isAdmin = true;
            }
        }
        foreach ($folders as $key => $folder) {
            if (!$folder instanceof FolderDao) {
                throw new Zend_Exception("Should be a folder");
            }
            $subqueryUser = $this->database->select()->setIntegrityCheck(false)->from(
                array('f' => 'folder'),
                array('folder_id')
            );
            if (!$isAdmin) {
                $subqueryUser->join(
                    array('fpu' => 'folderpolicyuser'),
                    '
                            f.folder_id = fpu.folder_id AND '.$this->database->getDB()->quoteInto(
                        'fpu.policy >= ?',
                        $policy
                    ).'
                               AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ',
                    array()
                );
            }
            $subqueryUser->where('left_index > ?', $folder->getLeftIndex())->where(
                'right_index < ?',
                $folder->getRightIndex()
            );

            $subqueryGroup = $this->database->select()->setIntegrityCheck(false)->from(
                array('f' => 'folder'),
                array('folder_id')
            );
            if (!$isAdmin) {
                $subqueryGroup->join(
                    array('fpg' => 'folderpolicygroup'),
                    '
                                f.folder_id = fpg.folder_id  AND '.$this->database->getDB()->quoteInto(
                        'fpg.policy >= ?',
                        $policy
                    ).'
                                   AND ( '.$this->database->getDB()->quoteInto(
                        'group_id = ? ',
                        MIDAS_GROUP_ANONYMOUS_KEY
                    ).' OR
                                        group_id IN ('.new Zend_Db_Expr(
                        $this->database->select()->setIntegrityCheck(false)->from(
                            array('u2g' => 'user2group'),
                            array('group_id')
                        )->where('u2g.user_id = ?', $userId)
                    ).'))',
                    array()
                );
            }

            $subqueryGroup->where('left_index > ?', $folder->getLeftIndex())->where(
                'right_index < ?',
                $folder->getRightIndex()
            );

            $subSqlFolders = $this->database->select()->union(array($subqueryUser, $subqueryGroup));

            $sql = $this->database->select()->distinct()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
                array('i2f' => 'item2folder'),
                '( '.$this->database->getDB()->quoteInto('i2f.folder_id IN (?)', $subSqlFolders).'
                          OR i2f.folder_id = '.$folder->getKey().'
                          )
                          AND i2f.item_id = i.item_id',
                array()
            );
            if (!$isAdmin) {
                $sql->joinLeft(
                    array('ip' => 'itempolicyuser'),
                    '
                          i.item_id = ip.item_id AND '.$this->database->getDB()->quoteInto('ip.policy >= ?', $policy).'
                             AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ',
                    array()
                )->joinLeft(
                    array('ipg' => 'itempolicygroup'),
                    '
                                i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto(
                        'ipg.policy >= ?',
                        $policy
                    ).'
                                   AND ( '.$this->database->getDB()->quoteInto(
                        'group_id = ? ',
                        MIDAS_GROUP_ANONYMOUS_KEY
                    ).' OR
                                        group_id IN ('.new Zend_Db_Expr(
                        $this->database->select()->setIntegrityCheck(false)->from(
                            array('u2g' => 'user2group'),
                            array('group_id')
                        )->where('u2g.user_id = ?', $userId)
                    ).'))',
                    array()
                )->where(
                    '(
                  ip.item_id is not null or
                  ipg.item_id is not null)'
                )->group('i.item_id');
            }

            $sql = $this->database->select()->setIntegrityCheck(false)->from(
                array('i' => $sql),
                array('sum' => 'sum(i.sizebytes)', 'count' => 'count(i.item_id)')
            );

            $row = $this->database->fetchRow($sql);
            $folders[$key]->count = $row['count'];
            $folders[$key]->size = $row['sum'];
            if ($folders[$key]->size == null) {
                $folders[$key]->size = 0;
            }
        }

        return $folders;
    }

    /** get the total size for a folder (with no filtered results) */
    public function getSize($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder");
        }
        $folders = $this->database->select()->setIntegrityCheck(false)->from(
            array('f' => 'folder'),
            array('folder_id')
        )->where(
            'left_index > ?',
            $folder->getLeftIndex()
        )->where('right_index < ?', $folder->getRightIndex());

        $sql = $this->database->select()->distinct()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array('i2f' => 'item2folder'),
            '( '.$this->database->getDB()->quoteInto('i2f.folder_id IN (?)', $folders).'
                  OR i2f.folder_id = '.$folder->getKey().'
                  )
                  AND i2f.item_id = i.item_id',
            array()
        );

        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('i' => $sql),
            array('sum' => 'sum(i.sizebytes)')
        );

        $row = $this->database->fetchRow($sql);

        return $row['sum'];
    }

    /** Get the folder tree */
    public function getAllChildren($folder, $userDao, $admin = false, $policy = 0)
    {
        $isAdmin = false;
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $userDao->getUserId();
            if ($userDao->isAdmin()) {
                $isAdmin = true;
            }
        }

        if ($admin) {
            $isAdmin = true;
        }

        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder");
        }
        $subqueryUser = $this->database->select()->setIntegrityCheck(false)->from(array('f' => 'folder'));
        if (!$isAdmin) {
            $subqueryUser->join(
                array('fpu' => 'folderpolicyuser'),
                '
                          f.folder_id = fpu.folder_id AND '.$this->database->getDB()->quoteInto(
                    'fpu.policy >= ?',
                    $policy
                ).'
                             AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ',
                array()
            );
        }
        $subqueryUser->where('left_index > ?', $folder->getLeftIndex())->where(
            'right_index < ?',
            $folder->getRightIndex()
        );

        $subqueryGroup = $this->database->select()->setIntegrityCheck(false)->from(array('f' => 'folder'));
        if (!$isAdmin) {
            $subqueryGroup->join(
                array('fpg' => 'folderpolicygroup'),
                '
                              f.folder_id = fpg.folder_id  AND '.$this->database->getDB()->quoteInto(
                    'fpg.policy >= ?',
                    $policy
                ).'
                                 AND ( '.$this->database->getDB()->quoteInto(
                    'group_id = ? ',
                    MIDAS_GROUP_ANONYMOUS_KEY
                ).' OR
                                      group_id IN ('.new Zend_Db_Expr(
                    $this->database->select()->setIntegrityCheck(false)->from(
                        array('u2g' => 'user2group'),
                        array('group_id')
                    )->where('u2g.user_id = ?', $userId)
                ).'))',
                array()
            );
        }

        $subqueryGroup->where('left_index > ?', $folder->getLeftIndex())->where(
            'right_index < ?',
            $folder->getRightIndex()
        );

        $subSqlFolders = $this->database->select()->union(array($subqueryUser, $subqueryGroup));

        $rowset = $this->database->fetchAll($subSqlFolders);

        $folders = array();

        foreach ($rowset as $row) {
            $folders[] = $this->initDao('Folder', $row);
        }

        return $folders;
    }

    /**
     * Custom delete function.
     * Pass a progressDao with pre-computed maximum to keep track of delete progress
     */
    public function delete($folder, $progressDao = null)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder");
        }
        if (!$folder->saved) {
            throw new Zend_Exception("The dao should be saved first ...");
        }
        $key = $folder->getKey();
        if (!isset($key)) {
            throw new Zend_Exception("Unable to find the key");
        }

        if ($progressDao && !isset($this->Progress)) {
            $this->Progress = MidasLoader::loadModel('Progress');
        }
        $items = $folder->getItems();
        foreach ($items as $item) {
            if ($progressDao) {
                $message = 'Removing item '.$item->getName();
                $this->Progress->updateProgress($progressDao, $progressDao->getCurrent() + 1, $message);
            }
            $this->removeItem($folder, $item);
        }

        $children = $folder->getFolders();
        foreach ($children as $child) {
            $this->delete($child, $progressDao);
        }

        if ($progressDao) {
            $message = 'Removing folder '.$folder->getName();
            $this->Progress->updateProgress($progressDao, $progressDao->getCurrent() + 1, $message);
        }

        $policy_group_model = MidasLoader::loadModel('Folderpolicygroup');
        $policiesGroup = $folder->getFolderpolicygroup();
        foreach ($policiesGroup as $policy) {
            $policy_group_model->delete($policy);
        }

        $policy_user_model = MidasLoader::loadModel('Folderpolicyuser');
        $policiesUser = $folder->getFolderpolicyuser();
        foreach ($policiesUser as $policy) {
            $policy_user_model->delete($policy);
        }

        $leftIndex = $folder->getLeftIndex();
        $this->database->getDB()->update(
            'folder',
            array('left_index' => new Zend_Db_Expr('left_index - 2')),
            array('left_index >= ?' => $leftIndex)
        );
        $this->database->getDB()->update(
            'folder',
            array('right_index' => new Zend_Db_Expr('right_index - 2')),
            array('right_index >= ?' => $leftIndex)
        );

        Zend_Registry::get('notifier')->callback('CALLBACK_CORE_FOLDER_DELETED', array('folder' => $folder));
        parent::delete($folder);
        unset($folder->folder_id);
        $folder->saved = false;

        return true;
    }

    /** move a folder */
    public function move($folder, $parent)
    {
        if ($folder->getKey() == $parent->getKey()) {
            throw new Zend_Exception("Folder == Parent");
        }

        $tmpParent = $parent->getParent();
        $currentParent = $folder->getParent();
        if ($currentParent->getKey() == $parent->getKey()) {
            return; // no-op
        }

        while ($tmpParent != false) {
            if ($tmpParent->getKey() == $folder->getKey()) {
                throw new Zend_Exception("Parent is a child of Folder");
            }
            $tmpParent = $tmpParent->getParent();
        }

        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Error in parameter folder when moving folder.");
        }
        if (!$parent instanceof FolderDao) {
            throw new Zend_Exception("Error in parameter parent when moving folder.");
        }

        // Check ifa folder with the same name already exists for the same parent
        if ($this->getFolderExists($folder->getName(), $parent)) {
            throw new Zend_Exception('This name is already used');
        }

        $node_pos_left = $folder->getLeftIndex();
        $node_pos_right = $folder->getRightIndex();

        $parent_pos_right = $parent->getRightIndex();
        $node_size = $node_pos_right - $node_pos_left + 1;

        // step 1: temporary "remove" moving node
        $this->database->getDB()->update(
            'folder',
            array(
                'left_index' => new Zend_Db_Expr('0 - left_index'),
                'right_index' => new Zend_Db_Expr('0 - right_index'),
            ),
            'left_index >= '.$node_pos_left.' AND right_index <= '.$node_pos_right
        );

        // step 2: decrease left and/or right position values of currently 'lower' items (and parents)
        $this->database->getDB()->update(
            'folder',
            array('left_index' => new Zend_Db_Expr('left_index - '.$node_size)),
            array('left_index > ?' => $node_pos_right)
        );
        $this->database->getDB()->update(
            'folder',
            array('right_index' => new Zend_Db_Expr('right_index - '.$node_size)),
            array('right_index > ?' => $node_pos_right)
        );

        // step 3: increase left and/or right position values of future 'lower' items (and parents)
        $cond = ($parent_pos_right > $node_pos_right ? $parent_pos_right - $node_size : $parent_pos_right);
        $this->database->getDB()->update(
            'folder',
            array('left_index' => new Zend_Db_Expr('left_index + '.$node_size)),
            array('left_index >= ?' => $cond)
        );
        $this->database->getDB()->update(
            'folder',
            array('right_index' => new Zend_Db_Expr('right_index + '.$node_size)),
            array('right_index >= ?' => $cond)
        );

        // step 4: move node (ant it's subnodes) and update it's parent item id
        $cond = ($parent_pos_right > $node_pos_right) ? $parent_pos_right - $node_pos_right - 1 : $parent_pos_right - $node_pos_right - 1 + $node_size;
        $this->database->getDB()->update(
            'folder',
            array(
                'left_index' => new Zend_Db_Expr('0 - left_index + '.$cond),
                'right_index' => new Zend_Db_Expr('0 - right_index + '.$cond),
            ),
            'left_index <= '.(0 - $node_pos_left).' AND right_index >= '.(0 - $node_pos_right)
        );

        $folder = $this->load($folder->getKey());
        $folder->setParentId($parent->getKey());
        parent::save($folder);
    }

    /** Custom save function */
    public function save($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }

        if (!isset($folder->uuid) || empty($folder->uuid)) {
            $folder->setUuid(uniqid().md5(mt_rand()));
        }
        $name = $folder->getName();
        if (empty($name) && $name !== '0') {
            throw new Zend_Exception("Please set a name.");
        }
        if ($folder->getParentId() <= 0) {
            $rightParent = 0;
        } else {
            $parentFolder = $folder->getParent();
            if (!$parentFolder) {
                return false; // deleting orphaned folder
            }
            $rightParent = $parentFolder->getRightIndex();
        }
        $data = array();
        foreach ($this->_mainData as $key => $var) {
            if (isset($folder->$key)) {
                $data[$key] = $folder->$key;
            }
            if ($key == 'right_index') {
                $folder->$key = $rightParent + 1;
                $data[$key] = $rightParent + 1;
            } elseif ($key == 'left_index') {
                $data[$key] = $rightParent;
            } elseif ($key == 'description') {
                $data[$key] = UtilityComponent::filterHtmlTags($folder->getDescription());
            }
        }

        if (isset($data['folder_id'])) {
            $key = $data['folder_id'];
            unset($data['folder_id']);
            unset($data['left_index']);
            unset($data['right_index']);
            $data['date_update'] = date('Y-m-d H:i:s');
            $this->database->update($data, array('folder_id = ?' => $key));

            Zend_Registry::get('notifier')->callback('CALLBACK_CORE_FOLDER_SAVED', array('folder' => $folder));

            return $key;
        } else {
            if (!isset($data['date_creation']) || empty($data['date_creation'])) {
                $data['date_creation'] = date('Y-m-d H:i:s');
            }
            $data['date_update'] = date('Y-m-d H:i:s');

            $this->database->getDB()->update(
                'folder',
                array('right_index' => new Zend_Db_Expr('2 + right_index')),
                array('right_index >= ?' => $rightParent)
            );
            $this->database->getDB()->update(
                'folder',
                array('left_index' => new Zend_Db_Expr('2 + left_index')),
                array('left_index >= ?' => $rightParent)
            );

            $insertedid = $this->database->insert($data);
            if (!$insertedid) {
                return false;
            }
            $folder->folder_id = $insertedid;
            $folder->saved = true;

            Zend_Registry::get('notifier')->callback('CALLBACK_CORE_FOLDER_SAVED', array('folder' => $folder));

            return true;
        }
    }

    /** Get community if the folder is the main folder of one */
    public function getCommunity($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $dao = $this->initDao(
            'Community',
            $this->database->fetchRow(
                $this->database->select()->setIntegrityCheck(false)->from('community')->where(
                    'folder_id = ?',
                    $folder->getFolderId()
                )
            )
        );

        return $dao;
    }

    /** Get user if the folder is the main folder of one */
    public function getUser($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $dao = $this->initDao(
            'User',
            $this->database->fetchRow(
                $this->database->select()->setIntegrityCheck(false)->from('user')->where(
                    'folder_id = ?',
                    $folder->getFolderId()
                )
            )
        );

        return $dao;
    }

    /**
     * Check whether folder exists by name in the given parent folder. If so, returns the dao,
     * otherwise returns false.
     */
    public function getFolderExists($name, $parent)
    {
        return $this->initDao(
            'Folder',
            $this->database->fetchRow(
                $this->database->select()->setIntegrityCheck(false)->from('folder')->where('name = ?', $name)->where(
                    'parent_id = ?',
                    $parent->getKey()
                )
            )
        );
    }

    /**
     * Get child items of a folder filtered by policy check for the provided user
     *
     * @param folder The parent folder
     * @param [userDao] The user requesting the folder children (default anonymous)
     * @param [policy] What policy to filter by (default MIDAS_POLICY_READ)
     * @param [sortfield] What field to sort the results by (name | date_update | sizebytes, default = name)
     * @param [sortdir] Sort direction (asc | desc, default = asc)
     * @param [limit] Result limit. Default is no limit.
     * @param [offset] Offset into result list. Default is 0.
     */
    public function getItemsFiltered(
        $folder,
        $userDao = null,
        $policy = 0,
        $sortfield = 'name',
        $sortdir = 'asc',
        $limit = 0,
        $offset = 0
    ) {
        if (is_array($folder)) {
            $folderIds = array();
            foreach ($folder as $f) {
                $folderIds[] = $f->getKey();
            }
        } else {
            $folderIds = array($folder->getKey());
        }

        $sql = $this->_buildChildItemsQuery($userDao, $folderIds, $policy, $sortfield, $sortdir);

        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }

        $rowset = $this->database->fetchAll($sql);
        $return = array();
        foreach ($rowset as $row) {
            $item = $this->initDao('Item', $row);
            $item->parent_id = $row['folder_id'];
            $return[] = $item;
        }

        return $return;
    }

    /**
     * Helper function to build the child selection query
     *
     * @param userDao The current user
     * @param folderIds Array of parent folder ids
     */
    private function _buildChildItemsQuery(&$userDao, &$folderIds, $policy, $sortfield = 'name', $sortdir = 'asc')
    {
        $userId = $userDao instanceof UserDao ? $userDao->getKey() : -1;
        $isAdmin = $userDao instanceof UserDao ? $userDao->isAdmin() : false;

        // Step 1: Create an intermediate query that selects all child items from the specified folders.
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array('i2f' => 'item2folder'),
            $this->database->getDB()->quoteInto('i2f.folder_id IN (?)', $folderIds).'
                  AND i2f.item_id = i.item_id',
            array('i2f.folder_id')
        )->order(array($sortfield.' '.strtoupper($sortdir)));

        // Step 2: For non admin users, we want to cull the intermediate set by policy checking.
        if (!$isAdmin) {
            $usrSql = $this->database->select()->setIntegrityCheck(false)->from(
                array('ipu' => 'itempolicyuser'),
                array('item_id')
            )->where('ipu.item_id = i.item_id')->where('ipu.user_id = ?', $userId)->where('policy >= ?', $policy);
            $grpSql = $this->database->select()->setIntegrityCheck(false)->from(
                array('ipg' => 'itempolicygroup'),
                array('item_id')
            )->where('ipg.item_id = i.item_id')->where(
                'policy >= ?',
                $policy
            )->where(
                '('.$this->database->getDB()->quoteInto(
                    'ipg.group_id = ?',
                    MIDAS_GROUP_ANONYMOUS_KEY
                ).' OR ipg.group_id IN ('.new Zend_Db_Expr(
                    $this->database->select()->setIntegrityCheck(false)->from(
                        array('u2g' => 'user2group'),
                        array('group_id')
                    )->where('u2g.user_id = ?', $userId).'))'
                )
            );
            $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => $sql))->where(
                'i.item_id IN ('.new Zend_Db_Expr($usrSql).') OR '.'i.item_id IN ('.new Zend_Db_Expr(
                    $grpSql
                ).')'
            );
        }

        return $sql;
    }

    /**
     * Get child folders of a folder filtered by policy check for the provided user
     *
     * @param folder The parent folder
     * @param userDao The user requesting the folder children (default anonymous)
     * @param policy What policy to filter by (default MIDAS_POLICY_READ)
     * @param sortfield What field to sort the results by (name | date, default = name)
     * @param sortdir Sort direction (asc | desc, default = asc)
     */
    public function getChildrenFoldersFiltered(
        $folder,
        $userDao = null,
        $policy = 0,
        $sortfield = 'name',
        $sortdir = 'asc',
        $limit = 0,
        $offset = 0
    ) {
        if (is_array($folder)) {
            $folderIds = array();
            foreach ($folder as $f) {
                $folderIds[] = $f->getKey();
            }
        } else {
            $folderIds = array($folder->getKey());
        }

        $sql = $this->_buildChildFoldersQuery($userDao, $folderIds, $policy, $sortfield, $sortdir);

        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }

        $rowset = $this->database->fetchAll($sql);
        $return = array();
        foreach ($rowset as $row) {
            $return[] = $this->initDao('Folder', $row);
        }

        return $return;
    }

    /**
     * Helper function to build the child selection query
     *
     * @param userDao The current user
     * @param folderIds Array of parent folder ids
     */
    private function _buildChildFoldersQuery(&$userDao, &$folderIds, $policy, $sortfield = 'name', $sortdir = 'asc')
    {
        $userId = $userDao instanceof UserDao ? $userDao->getKey() : -1;
        $isAdmin = $userDao instanceof UserDao ? $userDao->isAdmin() : false;

        // Step 1: Create an intermediate query that selects all child folders from the specified folders.
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('f' => 'folder'))->where(
            'f.parent_id IN (?)',
            $folderIds
        )->order(array($sortfield.' '.strtoupper($sortdir)));

        // Step 2: For non admin users, we want to cull the intermediate set by policy checking.
        if (!$isAdmin) {
            $usrSql = $this->database->select()->setIntegrityCheck(false)->from(
                array('fpu' => 'folderpolicyuser'),
                array('folder_id')
            )->where('fpu.folder_id = f.folder_id')->where('fpu.user_id = ?', $userId)->where('policy >= ?', $policy);
            $grpSql = $this->database->select()->setIntegrityCheck(false)->from(
                array('fpg' => 'folderpolicygroup'),
                array('folder_id')
            )->where('fpg.folder_id = f.folder_id')->where(
                'policy >= ?',
                $policy
            )->where(
                '('.$this->database->getDB()->quoteInto(
                    'fpg.group_id = ?',
                    MIDAS_GROUP_ANONYMOUS_KEY
                ).' OR fpg.group_id IN ('.new Zend_Db_Expr(
                    $this->database->select()->setIntegrityCheck(false)->from(
                        array('u2g' => 'user2group'),
                        array('group_id')
                    )->where('u2g.user_id = ?', $userId).'))'
                )
            );
            $sql = $this->database->select()->setIntegrityCheck(false)->from(array('f' => $sql))->where(
                'f.folder_id IN ('.new Zend_Db_Expr($usrSql).') OR '.'f.folder_id IN ('.new Zend_Db_Expr(
                    $grpSql
                ).')'
            );
        }

        return $sql;
    }

    /**
     * Get the child folder
     *
     * @return FolderDao
     */
    public function getFolderByName($folder, $foldername)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $row = $this->database->fetchRow(
            $this->database->select()->from($this->_name)->where('parent_id=?', $folder->getFolderId())->where(
                'name=?',
                $foldername
            )
        );

        return $this->initDao('Folder', $row);
    }

    /** Add an item to a folder
     *
     * @return void
     */
    public function addItem($folder, $item, $update = true)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }
        $itemModel = MidasLoader::loadModel('Item');
        // Update item name to avoid duplicated names within the same folder
        if ($update) {
            $item->setName($itemModel->updateItemName($item->getName(), $folder));
            $itemModel->save($item);
        }
        $this->database->link('items', $folder, $item);
    }

    /** Remove an item from a folder
     *
     * @return void
     */
    public function removeItem($folder, $item)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }
        $this->database->removeLink('items', $folder, $item);
        if (count($item->getFolders()) == 0) {
            $itemModel = MidasLoader::loadModel('Item');
            $itemModel->delete($item);
        }
    }

    /** Return an item by its name
     *
     * @return ItemDao
     */
    public function getItemByName($folder, $itemname, $caseSensitive = true)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }

        if ($caseSensitive) {
            $row = $this->database->fetchRow(
                $this->database->select()->setIntegrityCheck(false)->from('item')->join(
                    'item2folder',
                    'item2folder.item_id = item.item_id'
                )->where('item2folder.folder_id=?', $folder->getFolderId())->where('item.name=?', $itemname)
            );
        } else {
            $row = $this->database->fetchRow(
                $this->database->select()->setIntegrityCheck(false)->from('item')->join(
                    'item2folder',
                    'item2folder.item_id = item.item_id'
                )->where('item2folder.folder_id=?', $folder->getFolderId())->where('lower(item.name)=?', $itemname)
            );
        }

        return $this->initDao('Item', $row);
    }

    /** Return a list of folders corresponding to the search
     *
     * @return Array of FolderDao
     */
    public function getFoldersFromSearch($search, $userDao, $limit = 14, $group = true, $order = 'view')
    {
        if (Zend_Registry::get('configDatabase')->database->adapter == 'PDO_PGSQL'
        ) {
            $group = false; // Postgresql don't like the sql request with group by
        }
        $isAdmin = false;
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $userDao->getUserId();
            if ($userDao->isAdmin()) {
                $isAdmin = true;
            }
        }

        // If a module wishes to override the default (slow) SQL-based item searching, it should register to this callback.
        // This overrides *all* queries, not just specific ones, so at most one module per instance should be handling this callback.
        $overrideSearch = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_FOLDER_SEARCH_DEFAULT_BEHAVIOR_OVERRIDE',
            array('query' => $search, 'user' => $userDao, 'limit' => $limit)
        );
        $override = false;
        foreach ($overrideSearch as $results) {
            $override = true;
            $queryResults = $results;
            break;
        }

        if (!$override) {
            // If no special search modules are enabled, we fall back to a naive/slow SQL search
            $sql = $this->database->select();
            if ($group) {
                $sql->from(array('f' => 'folder'), array('folder_id', 'name', 'count(*)'))->distinct();
            } else {
                $sql->from(array('f' => 'folder'))->distinct();
            }

            if (!$isAdmin) {
                $sql->joinLeft(
                    array('fpu' => 'folderpolicyuser'),
                    '
                    f.folder_id = fpu.folder_id AND '.$this->database->getDB()->quoteInto(
                        'fpu.policy >= ?',
                        MIDAS_POLICY_READ
                    ).'
                       AND '.$this->database->getDB()->quoteInto('fpu.user_id = ? ', $userId).' ',
                    array()
                )->joinLeft(
                    array('fpg' => 'folderpolicygroup'),
                    '
                         f.folder_id = fpg.folder_id AND '.$this->database->getDB()->quoteInto(
                        'fpg.policy >= ?',
                        MIDAS_POLICY_READ
                    ).'
                             AND ( '.$this->database->getDB()->quoteInto(
                        'fpg.group_id = ? ',
                        MIDAS_GROUP_ANONYMOUS_KEY
                    ).' OR
                                  fpg.group_id IN ('.new Zend_Db_Expr(
                        $this->database->select()->setIntegrityCheck(false)->from(
                            array('u2g' => 'user2group'),
                            array('group_id')
                        )->where('u2g.user_id = ?', $userId)
                    ).'))',
                    array()
                )->where(
                    '(
              fpu.folder_id is not null or
              fpg.folder_id is not null)'
                );
            }
            $sql->setIntegrityCheck(false)->where(
                $this->database->getDB()->quoteInto('name LIKE ?', '%'.$search.'%')
            )->where(
                'name != ?',
                "Public"
            )->where('name != ?', "Private")->limit($limit);

            if ($group) {
                $sql->group('f.name');
            }

            switch ($order) {
                case 'name':
                    $sql->order(array('f.name ASC'));
                    break;
                case 'date':
                    $sql->order(array('f.date_update ASC'));
                    break;
                case 'view':
                default:
                    $sql->order(array('f.view DESC'));
                    break;
            }
            $rowset = $this->database->fetchAll($sql);
            $queryResults = array();
            foreach ($rowset as $row) {
                $count = isset($row['count(*)']) ? $row['count(*)'] : 1;
                $queryResults[] = array(
                    'id' => $row['folder_id'],
                    'count' => $count,
                    'score' => 0,
                ); // no boosting/scoring from sql search
            }
        }

        $i = 0;
        $results = array();
        foreach ($queryResults as $result) {
            $folder = $this->load($result['id']);
            if ($folder && $this->policyCheck($folder, $userDao)) {
                $folder->count = isset($result['count']) ? $result['count'] : 1;
                $folder->score = $result['score'];
                $results[] = $folder;
                unset($folder);
                $i++;
                if ($i >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Returns whether the folder is able to be deleted.
     * Any folder can be deleted unless it is a base Folder
     * of a User or Community.
     */
    public function isDeleteable($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception('Should be a folder.');
        }
        $id = $folder->getFolderId();
        if ($this->database->fetchRow(
            $this->database->select()->setIntegrityCheck(false)->from('community')->where('folder_id=?', $id)
        )
        ) {
            return false;
        }

        if ($this->database->fetchRow(
            $this->database->select()->setIntegrityCheck(false)->from('user')->where('folder_id=?', $id)
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * This will zip stream the filtered contents of the fold
     *
     * @param zip ZipStream object to write to (pass-by-reference, should already be started)
     * @param path The current path in the zip archive
     * @param folder The folder to recurse through
     * @param userDao The session user dao (pass-by-reference)
     * @param overrideOutputFunction A PHP callable object to call that will override
     * the default behavior of writing out the bitstream's contents
     */
    public function zipStream(&$zip, $path, $folder, &$userDao, &$overrideOutputFunction = null)
    {
        $folderIds = array($folder->getKey());
        $this->Item = MidasLoader::loadModel('Item');

        $sql = $this->_buildChildItemsQuery($userDao, $folderIds, MIDAS_POLICY_READ);
        $rows = $this->database->fetchAll($sql);
        foreach ($rows as $row) {
            $item = $this->initDao('Item', $row);
            $rev = $this->Item->getLastRevision($item);

            if (!$rev) {
                $zip->add_file($path.'/'.$item->getName(), ''); // add empty item
                continue;
            }
            $bitstreams = $rev->getBitstreams();
            $count = count($bitstreams);

            foreach ($bitstreams as $bitstream) {
                if ($overrideOutputFunction && is_callable($overrideOutputFunction)
                ) {
                    // caller has chosen to override output behavior
                    call_user_func_array($overrideOutputFunction, array($zip, $path, $item, $bitstream, $count));
                } else { // default behavior, just write out the file
                    if ($count > 1 || $bitstream->getName() != $item->getName()
                    ) {
                        $currPath = $path.'/'.$item->getName().'/'.$bitstream->getName();
                    } else {
                        $currPath = $path.'/'.$bitstream->getName();
                    }
                    $zip->add_file_from_path(
                        $currPath,
                        $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath()
                    );
                }
            }
            $this->Item->incrementDownloadCount($item);
            unset($bitstreams);
            unset($item);
        }
        unset($sql);
        unset($rows);

        $sql = $this->_buildChildFoldersQuery($userDao, $folderIds, MIDAS_POLICY_READ);
        $rows = $this->database->fetchAll($sql);
        foreach ($rows as $row) {
            $subfolder = $this->initDao('Folder', $row);
            $this->zipStream($zip, $path.'/'.$subfolder->getName(), $subfolder, $userDao, $overrideOutputFunction);
            unset($subfolder);
        }
    }

    /**
     * Will return all root folder daos.  There is a root folder for each
     * user, and one for each community.
     */
    public function getRootFolders()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('parent_id < ?', 0);
        $rowset = $this->database->fetchAll($sql);
        $rootFolders = array();
        foreach ($rowset as $row) {
            $rootFolders[] = $this->initDao('Folder', $row);
        }

        return $rootFolders;
    }

    /**
     * Return the total number of folders in this Midas instance (for admin use)
     */
    public function countAll()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('f' => 'folder'),
            array('count' => 'count(*)')
        );
        $row = $this->database->fetchRow($sql);

        return $row['count'];
    }

    /**
     * Used by the admin dashboard page. Counts the number of orphaned folder
     * records in the database.
     */
    public function countOrphans()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('f' => 'folder'),
            array('count' => 'count(*)')
        )->where(
            'f.parent_id > ?',
            0
        )->where(
            '(NOT f.parent_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('subf' => 'folder'),
                    array('folder_id')
                ).'))'
            )
        );
        $row = $this->database->fetchRow($sql);

        return $row['count'];
    }

    /**
     * Call this to delete all folders whose parent folder no longer exists.
     * After orphans are deleted, the tree indexes will be recomputed so they
     * will be consistent.  As such, server should not be written to
     * during this operation.
     */
    public function removeOrphans($progressDao = null)
    {
        if ($progressDao) {
            $max = $this->countOrphans();
            $progressDao->setMaximum($max);
            $progressDao->setMessage('Removing orphaned folders (0/'.$max.')');
            $this->Progress = MidasLoader::loadModel('Progress');
            $this->Progress->save($progressDao);
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('f' => 'folder'),
            array('folder_id')
        )->where(
            'f.parent_id > ?',
            0
        )->where(
            '(NOT f.parent_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('subf' => 'folder'),
                    array('folder_id')
                ).'))'
            )
        );
        $rowset = $this->database->fetchAll($sql);
        $ids = array();
        foreach ($rowset as $row) {
            $ids[] = $row['folder_id'];
        }
        $itr = 0;
        foreach ($ids as $id) {
            if ($progressDao) {
                $itr++;
                $message = 'Removing orphaned folders ('.$itr.'/'.$max.')';
                $this->Progress->updateProgress($progressDao, $itr, $message);
            }
            $folder = $this->load($id);
            if (!$folder) {
                continue;
            }
            $this->getLogger()->info(
                'Deleting orphaned folder '.$folder->getName().' [parent id='.$folder->getParentId().']'
            );
            $this->delete($folder);
        }

        $max = $this->countAll();
        if ($progressDao) {
            $progressDao->setMaximum($max);
            $progressDao->setMessage('Rebuilding entire folder tree index (0/'.$max.')');
            $this->Progress->save($progressDao);
        }
        // Wipe the current tree indexes from all rows
        $this->database->update(array('left_index' => 0, 'right_index' => 0), array());

        // Recompute the indexes for all rows
        $rootFolders = $this->getRootFolders();
        $count = 0;
        foreach ($rootFolders as $rootFolder) {
            $this->_recomputeSubtree($rootFolder, $count, $max, $progressDao);
        }
    }

    /**
     * Will recompute the left and right indexes of the subtree of the given node.
     */
    protected function _recomputeSubtree($folder, &$count, $max, $progressDao = null)
    {
        $count++;
        if ($progressDao && $count % 10 == 0) { // only update progress every 10 folders
            $message = 'Rebuilding entire folder tree index ('.$count.'/'.$max.')';
            $this->Progress->updateProgress($progressDao, $count, $message);
        }
        if ($folder->getParentId() <= 0) {
            $rightParent = 0;
        } else {
            $parentFolder = $folder->getParent();
            $rightParent = $parentFolder->getRightIndex();
        }

        $folder->setLeftIndex($rightParent);
        $folder->setRightIndex($rightParent + 1);

        $this->database->getDB()->update(
            'folder',
            array('right_index' => new Zend_Db_Expr('2 + right_index')),
            array('right_index >= ? AND left_index != right_index' => $rightParent)
        );
        $this->database->getDB()->update(
            'folder',
            array('left_index' => new Zend_Db_Expr('2 + left_index')),
            array('left_index >= ? AND left_index != right_index' => $rightParent)
        );

        parent::save($folder);

        $children = $folder->getFolders();
        foreach ($children as $child) {
            $this->_recomputeSubtree($child, $count, $max, $progressDao);
        }
    }
}
