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

require_once BASE_PATH.'/core/models/base/CommunityModelBase.php';

/**
 *  UserModel
 *  Pdo Model
 */
class CommunityModel extends CommunityModelBase
{
    /**
     * Get by UUID.
     *
     * @param $uuid
     * @return false|CommunityDao
     */
    public function getByUuid($uuid)
    {
        $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }

    /**
     * Get a community by name.
     *
     * @param string $name
     * @return false|CommunityDao
     */
    public function getByName($name)
    {
        $row = $this->database->fetchRow($this->database->select()->where('name = ?', $name));
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }

    /**
     * Return a community given its root folder.
     *
     * @param FolderDao $folder
     * @return false|CommunityDao
     * @throws Zend_Exception
     */
    public function getByFolder($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder");
        }

        $row = $this->database->fetchRow(
            $this->database->select()->setIntegrityCheck(false)->from('community')->where(
                'folder_id=?',
                $folder->getFolderId()
            )
        );

        $community = $this->initDao('Community', $row);

        return $community;
    }

    /**
     * Get all.
     *
     * @return array
     */
    public function getAll()
    {
        $rowset = $this->database->fetchAll($this->database->select());
        $return = array();
        foreach ($rowset as $row) {
            $return[] = $this->initDao('Community', $row);
        }

        return $return;
    }

    /**
     * Get public communities.
     *
     * @param int $limit
     * @return array
     * @throws Zend_Exception
     */
    public function getPublicCommunities($limit = 20)
    {
        if (!is_numeric($limit)) {
            throw new Zend_Exception("limit should be numeric when getting public communities.");
        }
        $sql = $this->database->select()->from($this->_name)->where('privacy != ?', MIDAS_COMMUNITY_PRIVATE)->limit(
            $limit
        );

        $rowset = $this->database->fetchAll($sql);
        $return = array();
        foreach ($rowset as $row) {
            $return[] = $this->initDao('Community', $row);
        }

        return $return;
    }

    /**
     * Return a list of communities corresponding to the search.
     *
     * @param string $search
     * @param UserDao $userDao
     * @param int $limit
     * @param bool $group
     * @param string $order
     * @return array
     * @throws Zend_Exception
     */
    public function getCommunitiesFromSearch($search, $userDao, $limit = 14, $group = true, $order = 'view')
    {
        if (Zend_Registry::get('configDatabase')->database->adapter == 'PDO_PGSQL'
        ) {
            $group = false; // PostgreSQL does not like the SQL request with group by
        }
        $communities = array();
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $userDao->getUserId();
            $userGroups = $userDao->getGroups();
            foreach ($userGroups as $userGroup) {
                $communities[] = $userGroup->getCommunityId();
            }
        }

        $sql = $this->database->select();
        if ($group) {
            $sql->from(array('c' => 'community'), array('community_id', 'name', 'count(*)'));
        } else {
            $sql->from(array('c' => 'community'));
        }

        if ($userId != -1 && $userDao->isAdmin()) {
            $sql->where('c.name LIKE ?', '%'.$search.'%');
        } elseif (!empty($communities)) {
            $sql->where('c.name LIKE ?', '%'.$search.'%');
            $sql->where(
                '(c.privacy < '.MIDAS_COMMUNITY_PRIVATE.' OR '.$this->database->getDB()->quoteInto(
                    'c.community_id IN (?)',
                    $communities
                ).')'
            );
        } else {
            $sql->where('c.name LIKE ?', '%'.$search.'%');
            $sql->where('(c.privacy < '.MIDAS_COMMUNITY_PRIVATE.')');
        }

        $sql->limit($limit);

        if ($group) {
            $sql->group('c.name');
        }

        switch ($order) {
            case 'name':
                $sql->order(array('c.name ASC'));
                break;
            case 'date':
                $sql->order(array('c.creation ASC'));
                break;
            case 'view':
            default:
                $sql->order(array('c.view DESC'));
                break;
        }

        $rowset = $this->database->fetchAll($sql);
        $return = array();
        foreach ($rowset as $row) {
            $tmpDao = $this->initDao('Community', $row);
            if (isset($row['count(*)'])) {
                $tmpDao->count = $row['count(*)'];
            }
            $return[] = $tmpDao;
            unset($tmpDao);
        }

        return $return;
    }
}
