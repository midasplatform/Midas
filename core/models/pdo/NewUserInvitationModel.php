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

require_once BASE_PATH.'/core/models/base/NewUserInvitationModelBase.php';

/**
 * Pdo Model for a new user invitation
 */
class NewUserInvitationModel extends NewUserInvitationModelBase
{
    /**
     * Search the table for a matching record.  If any exists, returns the first dao.  Otherwise returns false.
     *
     * @param array $params
     * @return false|NewUserInvitationDao
     */
    public function getByParams($params)
    {
        $sql = $this->database->select()->setIntegrityCheck(false);
        foreach ($params as $column => $value) {
            $sql->where($column.' = ?', $value);
        }
        $row = $this->database->fetchRow($sql);

        return $this->initDao('NewUserInvitation', $row);
    }

    /**
     * Search the table for a matching record. Returns the matching set of daos.
     *
     * @param array $params
     * @return array
     */
    public function getAllByParams($params)
    {
        $sql = $this->database->select()->setIntegrityCheck(false);
        foreach ($params as $column => $value) {
            $sql->where($column.' = ?', $value);
        }

        $rows = $this->database->fetchAll($sql);
        $daos = array();
        foreach ($rows as $row) {
            $daos[] = $this->initDao('NewUserInvitation', $row);
        }

        return $daos;
    }

    /**
     * Deletes all new user invitations corresponding to a group.
     *
     * @param GroupDao $group
     * @throws Zend_Exception
     */
    public function deleteByGroup($group)
    {
        if (!$group instanceof GroupDao) {
            throw new Zend_Exception('Must pass a group dao');
        }
        Zend_Registry::get('dbAdapter')->delete($this->_name, 'group_id = '.$group->getKey());
    }

    /**
     * Deletes all new user invitations corresponding to a community.
     *
     * @param CommunityDao $community
     * @throws Zend_Exception
     */
    public function deleteByCommunity($community)
    {
        if (!$community instanceof CommunityDao) {
            throw new Zend_Exception('Must pass a community dao');
        }
        Zend_Registry::get('dbAdapter')->delete($this->_name, 'community_id = '.$community->getKey());
    }
}
