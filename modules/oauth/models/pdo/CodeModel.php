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

require_once BASE_PATH.'/modules/oauth/models/base/CodeModelBase.php';

/** pdo model implementation */
class Oauth_CodeModel extends Oauth_CodeModelBase
{
    /**
     * Return all code DAOs for the given user.
     *
     * @param UserDao $userDao
     * @return array
     * @throws Zend_Exception
     */
    public function getByUser($userDao)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('user_id = ?', $userDao->getKey());
        $rows = $this->database->fetchAll($sql);
        $daos = array();
        foreach ($rows as $row) {
            $daos[] = $this->initDao('Code', $row, $this->moduleName);
        }

        return $daos;
    }

    /**
     * Return the code DAO corresponding to this code string if it exists.
     *
     * @param string $code
     * @return false|Oauth_CodeDao
     * @throws Zend_Exception
     */
    public function getByCode($code)
    {
        $row = $this->database->fetchRow($this->database->select()->setIntegrityCheck(false)->where('code = ?', $code));

        return $this->initDao('Code', $row, $this->moduleName);
    }

    /** Remove expired access tokens from the database. */
    public function cleanExpired()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('expiration_date < ?', date('Y-m-d H:i:s'));

        $rows = $this->database->fetchAll($sql);
        foreach ($rows as $row) {
            $tmpDao = $this->initDao('Code', $row, $this->moduleName);
            $this->delete($tmpDao);
            $tmpDao = null; // mark for memory reclamation
        }
    }
}
