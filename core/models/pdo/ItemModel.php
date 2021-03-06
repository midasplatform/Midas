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

require_once BASE_PATH.'/core/models/base/ItemModelBase.php';

/** Pdo Model. */
class ItemModel extends ItemModelBase
{
    /**
     * Get the keyword from the search.
     *
     * @param string $searchterm
     * @param UserDao $userDao
     * @param int $limit
     * @param bool $group
     * @param string $order
     * @return array
     * @throws Zend_Exception
     */
    public function getItemsFromSearch($searchterm, $userDao, $limit = 14, $group = true, $order = 'view')
    {
        if ($userDao != null && !$userDao instanceof UserDao) {
            throw new Zend_Exception('Should be a user.');
        }

        // Allow modules to handle special search queries.  If any module accepts the query given its format,
        // its results are returned.
        $moduleSearch = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_ITEM_SEARCH',
            array('search' => $searchterm, 'user' => $userDao)
        );
        foreach ($moduleSearch as $results) {
            if ($results['status'] == 'accepted') {
                return $results['items'];
            }
        }

        // If a module wishes to override the default (slow) SQL-based item searching, it should register to this callback.
        // This overrides *all* queries, not just specific ones, so at most one module per instance should be handling this callback.
        $overrideSearch = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_ITEM_SEARCH_DEFAULT_BEHAVIOR_OVERRIDE',
            array('query' => $searchterm, 'user' => $userDao, 'limit' => $limit)
        );
        $override = false;
        foreach ($overrideSearch as $results) {
            $override = true;
            $queryResults = $results;
            break;
        }

        if (!$override) {
            // If no special search modules are enabled, we fall back to a naive/slow SQL search
            $sql = $this->database->select()->setIntegrityCheck(false)->from(array('item'), array('item_id'))->where(
                'name LIKE ? OR description LIKE ?',
                '%'.$searchterm.'%'
            )->limit($limit * 3); // extend limit to allow for policy-based result culling
            $rowset = $this->database->fetchAll($sql);
            $queryResults = array();
            foreach ($rowset as $row) {
                $queryResults[] = array('id' => $row['item_id'], 'score' => 0); // no boosting/scoring from sql search
            }
        }

        $i = 0;
        $results = array();
        foreach ($queryResults as $result) {
            $item = $this->load($result['id']);
            if ($item && $this->policyCheck($item, $userDao)) {
                $item->count = 1;
                $item->score = $result['score'];
                $results[] = $item;
                unset($item);
                ++$i;
                if ($i >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Get all.
     *
     * @return array
     * @throws Zend_Exception
     */
    public function getAll()
    {
        $rowset = $this->database->fetchAll($this->database->select()->order(array('item_id DESC')));
        $results = array();
        foreach ($rowset as $row) {
            $results[] = $this->initDao('Item', $row);
        }

        return $results;
    }

    /**
     * Get the total number of items in the database.
     *
     * @return int
     */
    public function getTotalCount()
    {
        $row = $this->database->fetchRow($this->database->select()->from('item', array('count' => 'count(*)')));

        return $row['count'];
    }

    /**
     * get by UUID.
     *
     * @param string $uuid
     * @return false|ItemDao
     * @throws Zend_Exception
     */
    public function getByUuid($uuid)
    {
        $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }

    /**
     * Get all items with the name provided.
     *
     * @param string $name
     * @return array
     * @throws Zend_Exception
     */
    public function getByName($name)
    {
        $rowset = $this->database->fetchAll($this->database->select()->where('name = ?', $name));
        $daos = array();
        foreach ($rowset as $row) {
            $daos[] = $this->initDao(ucfirst($this->_name), $row);
        }

        return $daos;
    }

    /**
     * Get all items with the given name and parent folder id.
     *
     * @param string $name
     * @param int $folderId
     * @return array
     * @throws Zend_Exception
     */
    public function getByNameAndFolderId($name, $folderId)
    {
        $select = $this->database->select()->from('item')->join(
            'item2folder',
            'item.item_id = item2folder.item_id',
            array()
        )->where(
            'item.name = ?',
            $name
        )->where('item2folder.folder_id = ?', $folderId);
        $rowset = $this->database->fetchAll($select);
        $daos = array();
        foreach ($rowset as $row) {
            $daos[] = $this->initDao(ucfirst($this->_name), $row);
        }

        return $daos;
    }

    /**
     * Get all items with the given name and parent folder name.
     *
     * @param string $name
     * @param string $folderName
     * @return array
     * @throws Zend_Exception
     */
    public function getByNameAndFolderName($name, $folderName)
    {
        $select = $this->database->select()->from('item')->join(
            'item2folder',
            'item.item_id = item2folder.item_id',
            array()
        )->join(
            'folder',
            'item2folder.folder_id = folder.folder_id',
            array()
        )->where('item.name = ?', $name)->where('folder.name = ?', $folderName);
        $rowset = $this->database->fetchAll($select);
        $daos = array();
        foreach ($rowset as $row) {
            $daos[] = $this->initDao(ucfirst($this->_name), $row);
        }

        return $daos;
    }

    /**
     * Check whether an item exists with the given name in the given folder.
     * If it does, returns the existing item dao. Otherwise returns false.
     *
     * @param string $name
     * @param FolderDao $folder
     * @return false|ItemDao
     * @throws Zend_Exception
     */
    public function existsInFolder($name, $folder)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array('i2f' => 'item2folder'),
            'i.item_id = i2f.item_id AND '.$this->database->getDB()->quoteInto(
                'i2f.folder_id = ?',
                $folder->getKey()
            ),
            array()
        )->where(
            'i.name = ?',
            $name
        )->limit(1);

        return $this->initDao('Item', $this->database->fetchRow($sql));
    }

    /**
     * Get the most popular items.
     *
     * @param UserDao $userDao
     * @param int $limit
     * @return array
     * @throws Zend_Exception
     */
    public function getMostPopulars($userDao, $limit = 20)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->where(
            'privacy_status = ?',
            MIDAS_PRIVACY_PUBLIC
        )->where('download != ?', 0)->where('view != ?', 0)->order(array('i.view DESC'))->limit($limit);
        $rowset = $this->database->fetchAll($sql);
        $results = array();
        foreach ($rowset as $row) {
            $tmp = $this->initDao('Item', $row);
            $results[] = $tmp;
        }

        return $results;
    }

    /**
     * Get items owned by the given user.
     *
     * @param UserDao $userDao
     * @param int $limit
     * @return array
     * @throws Zend_Exception
     */
    public function getOwnedByUser($userDao, $limit = 20)
    {
        $userId = $userDao->getKey();
        if (!is_numeric($userId)) {
            throw new Zend_Exception('Error parameter userId when getting items owned by user.');
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array('p' => 'itempolicyuser'),
            'i.item_id = p.item_id',
            array('p.policy', 'policy_date' => 'p.date')
        )->where('policy = ?', MIDAS_POLICY_ADMIN)->where(
            'user_id = ? ',
            $userId
        )->order(array('p.date DESC'))->limit($limit);
        $rowset = $this->database->fetchAll($sql);
        $results = array();
        foreach ($rowset as $row) {
            $tmp = $this->initDao('Item', $row);
            $tmp->policy = $row['policy'];
            $tmp->policy_date = $row['policy_date'];
            $results[] = $tmp;
        }

        return $results;
    }

    /**
     * Get items shared to the given user.
     *
     * @param UserDao $userDao
     * @param int $limit
     * @return array
     * @throws Zend_Exception
     */
    public function getSharedToUser($userDao, $limit = 20)
    {
        $userId = $userDao->getKey();
        if (!is_numeric($userId)) {
            throw new Zend_Exception('Error in parameter user Id when getting Items shared to user.');
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array('p' => 'itempolicyuser'),
            'i.item_id = p.item_id',
            array('p.policy', 'policy_date' => 'p.date')
        )->where('policy != ?', MIDAS_POLICY_ADMIN)->where(
            'user_id = ? ',
            $userId
        )->order(array('p.date DESC'))->limit($limit);
        $rowset = $this->database->fetchAll($sql);
        $results = array();
        foreach ($rowset as $row) {
            $tmp = $this->initDao('Item', $row);
            $tmp->policy = $row['policy'];
            $tmp->policy_date = $row['policy_date'];
            $results[] = $tmp;
        }

        return $results;
    }

    /**
     * Delete an item.
     *
     * @param ItemDao $itemdao
     * @throws Zend_Exception
     */
    public function delete($itemdao)
    {
        if (!$itemdao instanceof ItemDao) {
            throw new Zend_Exception('Error in parameter itemdao when deleting an Item.');
        }

        $deleteType = array(MIDAS_FEED_CREATE_ITEM, MIDAS_FEED_CREATE_LINK_ITEM);

        // explicitly typecast the itemId to a string, for postgres
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('p' => 'feed'))->where(
            'resource = ?',
            (string) $itemdao->getKey()
        );
        $rowset = $this->database->fetchAll($sql);

        /** @var FeedModel $feed_model */
        $feed_model = MidasLoader::loadModel('Feed');

        /** @var ItemRevisionModel $revision_model */
        $revision_model = MidasLoader::loadModel('ItemRevision');
        foreach ($rowset as $row) {
            $feed = $this->initDao('Feed', $row);
            if (in_array($feed->getType(), $deleteType)) {
                $feed_model->delete($feed);
            }
        }

        $folders = $itemdao->getFolders();
        foreach ($folders as $folder) {
            $this->database->removeLink('folders', $itemdao, $folder);
        }

        $revisions = $itemdao->getRevisions();
        foreach ($revisions as $revision) {
            $revision_model->delete($revision);
        }

        /** @var ItempolicygroupModel $policy_group_model */
        $policy_group_model = MidasLoader::loadModel('Itempolicygroup');
        $policiesGroup = $itemdao->getItempolicygroup();
        foreach ($policiesGroup as $policy) {
            $policy_group_model->delete($policy);
        }

        /** @var ItempolicyuserModel $policy_user_model */
        $policy_user_model = MidasLoader::loadModel('Itempolicyuser');
        $policiesUser = $itemdao->getItempolicyuser();
        foreach ($policiesUser as $policy) {
            $policy_user_model->delete($policy);
        }

        $thumbnailId = $itemdao->getThumbnailId();
        if ($thumbnailId !== null) {
            /** @var BitstreamModel $bitstreamModel */
            $bitstreamModel = MidasLoader::loadModel('Bitstream');
            $thumbnail = $bitstreamModel->load($thumbnailId);
            $bitstreamModel->delete($thumbnail);
        }

        parent::delete($itemdao);
        unset($itemdao->item_id);
        $itemdao->saved = false;
    }

    /**
     * Get the maximum policy level for the given item and user.
     *
     * @param int $itemId
     * @param UserDao $user
     * @return int|string
     */
    public function getMaxPolicy($itemId, $user)
    {
        $maxPolicy = -1;
        if ($user) {
            if ($user->isAdmin()) {
                return MIDAS_POLICY_ADMIN;
            }
            $userId = $user->getKey();
            $sql = $this->database->select()->setIntegrityCheck(false)->from(
                'itempolicyuser',
                array('maxpolicy' => 'max(policy)')
            )->where(
                'item_id = ?',
                $itemId
            )->where('user_id = ? ', $userId);
            $row = $this->database->fetchRow($sql);
            if ($row != null && $row['maxpolicy'] > $maxPolicy) {
                $maxPolicy = $row['maxpolicy'];
            }
        } else {
            $userId = -1;
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'itempolicygroup'),
            array('maxpolicy' => 'max(policy)')
        )->where('p.item_id = ?', $itemId)->where(
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
     * Check if the policy is valid.
     *
     * @param ItemDao $itemdao
     * @param null|UserDao $userDao
     * @param int $policy
     * @return bool
     * @throws Zend_Exception
     */
    public function policyCheck($itemdao, $userDao = null, $policy = 0)
    {
        if (!$itemdao instanceof ItemDao || !is_numeric($policy)) {
            throw new Zend_Exception('Error in parameter itemdao or policy when checking Item policy.');
        }
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception('Should be an user.');
        } else {
            $userId = $userDao->getUserId();
            if ($userDao->isAdmin()) {
                return true;
            }
        }

        $subqueryUser = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'itempolicyuser'),
            array('item_id')
        )->where(
            'policy >= ?',
            $policy
        )->where('p.item_id = ?', $itemdao->getKey())->where('user_id = ? ', $userId);

        $subqueryGroup = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'itempolicygroup'),
            array('item_id')
        )->where(
            'policy >= ?',
            $policy
        )->where('p.item_id = ?', $itemdao->getKey())->where(
            '( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
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
     * Get random items.
     *
     * @param null|UserDao $userDao
     * @param int $policy
     * @param int $limit
     * @param bool $thumbnailFilter
     * @return array
     * @throws Zend_Exception
     */
    public function getRandomThumbnails($userDao = null, $policy = 0, $limit = 10, $thumbnailFilter = false)
    {
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception('Should be an user.');
        } else {
            $userId = $userDao->getUserId();
        }

        if (Zend_Registry::get('configDatabase')->database->adapter === 'PDO_MYSQL'
        ) {
            $rand = 'RAND()';
        } else {
            $rand = 'random()';
        }
        if (Zend_Registry::get('configDatabase')->database->adapter == 'PDO_SQLITE') {
            $floor = 'CAST(tt.maxid*'.$rand.' AS INTEGER)';
        } else {
            $floor = 'FLOOR(tt.maxid*'.$rand.')';
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array(
                'tt' => $this->database->select()->from(
                    array('i' => 'item'),
                    array('maxid' => 'MAX(item_id)')
                ),
            ),
            ' i.item_id >= '.$floor
        )->joinLeft(
            array('ip' => 'itempolicyuser'),
            '
                  i.item_id = ip.item_id AND '.$this->database->getDB()->quoteInto('ip.policy >= ?', $policy).'
                     AND '.$this->database->getDB()->quoteInto('user_id = ? ', $userId).' ',
            array('userpolicy' => 'ip.policy')
        )->joinLeft(
            array('ipg' => 'itempolicygroup'),
            '
                        i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto(
                'ipg.policy >= ?',
                $policy
            ).'
                           AND ( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                group_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('u2g' => 'user2group'),
                    array('group_id')
                )->where('u2g.user_id = ?', $userId)
            ).'))',
            array('grouppolicy' => 'ipg.policy')
        )->where(
            '(
          ip.item_id is not null or
          ipg.item_id is not null)'
        )->limit($limit);
        if ($thumbnailFilter) {
            $sql->where('NOT thumbnail_id IS NULL', '');
        }

        $rowset = $this->database->fetchAll($sql);
        $rowsetAnalysed = array();
        foreach ($rowset as $row) {
            if ($row['userpolicy'] == null) {
                $row['userpolicy'] = 0;
            }
            if ($row['grouppolicy'] == null) {
                $row['grouppolicy'] = 0;
            }
            if (!isset($rowsetAnalysed[$row['item_id']]) || ($rowsetAnalysed[$row['item_id']]->policy < $row['userpolicy'] && $rowsetAnalysed[$row['item_id']]->policy < $row['grouppolicy'])
            ) {
                $tmpDao = $this->initDao('Item', $row);
                if ($row['userpolicy'] >= $row['grouppolicy']) {
                    $tmpDao->policy = $row['userpolicy'];
                } else {
                    $tmpDao->policy = $row['grouppolicy'];
                }
                $rowsetAnalysed[$row['item_id']] = $tmpDao;
                unset($tmpDao);
            }
        }

        return $rowsetAnalysed;
    }

    /**
     * Get the last revision.
     *
     * @return false|ItemRevisionDao
     * @throws Zend_Exception
     */
    public function getLastRevision($itemdao)
    {
        if (!$itemdao instanceof ItemDao || !$itemdao->saved) {
            throw new Zend_Exception('Error in param itemdao when getting last Item revision.');
        }

        return $this->initDao(
            'ItemRevision',
            $this->database->fetchRow(
                $this->database->select()->from('itemrevision')->where(
                    'item_id = ?',
                    $itemdao->getItemId()
                )->order(array('revision DESC'))->limit(1)->setIntegrityCheck(false)
            )
        );
    }

    /**
     * Get revision.
     *
     * @param ItemDao $itemdao
     * @param int $number
     * @return false|ItemRevisionDao
     * @throws Zend_Exception
     */
    public function getRevision($itemdao, $number)
    {
        if (!$itemdao instanceof ItemDao || !$itemdao->saved) {
            throw new Zend_Exception('Error in param itemdao when getting Item revision.');
        }

        return $this->initDao(
            'ItemRevision',
            $this->database->fetchRow(
                $this->database->select()->from('itemrevision')->where('item_id = ?', $itemdao->getItemId())->where(
                    'revision = ?',
                    $number
                )->limit(1)->setIntegrityCheck(false)
            )
        );
    }

    /**
     * Call a callback function on every item in the database.
     *
     * @param string $callback name of the callback
     * @param string $paramName parameter name the item should be passed as to the callback (default is 'item')
     * @param array $otherParams other parameters
     * @throws Zend_Exception
     */
    public function iterateWithCallback($callback, $paramName = 'item', $otherParams = array())
    {
        $rowset = $this->database->fetchAll();
        foreach ($rowset as $row) {
            $item = $this->initDao('Item', $row);
            $params = array_merge($otherParams, array($paramName => $item));
            Zend_Registry::get('notifier')->callback($callback, $params);
        }
    }

    /**
     * Used by the admin dashboard page. Counts the number of orphaned item
     * records in the database.
     *
     * @return int
     */
    public function countOrphans()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('i' => 'item'),
            array('count' => 'count(*)')
        )->where(
            '(NOT i.item_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('i2f' => 'item2folder'),
                    array('item_id')
                ).'))'
            )
        );
        $row = $this->database->fetchRow($sql);

        return $row['count'];
    }

    /**
     * Remove all orphaned item records.
     *
     * @param null|ProgressDao $progressDao
     * @throws Zend_Exception
     */
    public function removeOrphans($progressDao = null)
    {
        if ($progressDao) {
            $max = $this->countOrphans();
            $progressDao->setMaximum($max);
            $progressDao->setMessage('Removing orphaned items (0/'.$max.')');
            $this->Progress = MidasLoader::loadModel('Progress');
            $this->Progress->save($progressDao);
        }

        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'), array('item_id'))->where(
            'i.item_id > ?',
            0
        )->where(
            '(NOT i.item_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('i2f' => 'item2folder'),
                    array('item_id')
                ).'))'
            )
        );
        $rowset = $this->database->fetchAll($sql);
        $ids = array();
        foreach ($rowset as $row) {
            $ids[] = $row['item_id'];
        }
        $itr = 0;
        foreach ($ids as $id) {
            if ($progressDao) {
                ++$itr;
                $message = 'Removing orphaned items ('.$itr.'/'.$max.')';
                $this->Progress->updateProgress($progressDao, $itr, $message);
            }
            $item = $this->load($id);
            if (!$item) {
                continue;
            }
            $this->getLogger()->info('Deleting orphaned item '.$item->getName().' [id='.$item->getKey().']');
            $this->delete($item);
        }
    }

    /**
     * Update Item name to avoid two or more items have same name within their
     * parent folder.
     *
     * Check if an item with the same name already exists in the parent folder. If
     * it exists, add appendix to the original file name.
     * The following naming convention is used:
     * Assumption: if an item's name is like "aaa.txt (1)", the name should not be this item's real name, but its modified name in Midas when it is created.
     * This item's real name should be 'aaa.txt' which does not have / \(d+\)/ like appendix .
     * So when an item named "aaa.txt (1)" is duplicated, the newly created item will be called "aaa.txt (2)" instead of "aaa.txt (1) (1)"
     *
     * @param string $name name of the item
     * @param FolderDao $parent parent folder of the item
     * @return string new unique (within its parent folder) name assigned to the item
     * @throws Zend_Exception
     */
    public function updateItemName($name, $parent)
    {
        if (!$parent instanceof FolderDao && !is_numeric($parent)) {
            throw new Zend_Exception('Parent should be a folder.');
        }

        if (empty($name) && $name !== '0') {
            throw new Zend_Exception('Name cannot be empty.');
        }

        if ($parent instanceof FolderDao) {
            $parentId = $parent->getFolderId();
        } else {
            $parentId = $parent;
        }

        $curName = $name;

        $curRow = null;
        $count = 0;
        while (true) {
            $sql = $this->database->select()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
                array('i2f' => 'item2folder'),
                'i.item_id = i2f.item_id AND '.$this->database->getDB()->quoteInto('i2f.folder_id = ?', $parentId),
                array()
            )->where(
                'i.name = ?',
                $curName
            )->limit(1);
            $curRow = $this->database->fetchRow($sql);
            if ($curRow == null) {
                break;
            } else {
                ++$count;
                $curName = $name.' ('.$count.')';
            }
        }

        return $curName;
    }
}
