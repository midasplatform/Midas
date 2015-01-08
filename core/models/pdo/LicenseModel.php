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

require_once BASE_PATH.'/core/models/base/LicenseModelBase.php';

/** License model. */
class LicenseModel extends LicenseModelBase
{
    /**
     * Return all licenses.
     *
     * @return array list of license DAOs
     */
    public function getAll()
    {
        return $this->database->getAll('License');
    }

    /**
     * Return a license given its name.
     *
     * @param string $name name of the license
     * @return array list of license DAOs
     */
    public function getByName($name)
    {
        $rows = $this->database->fetchAll($this->database->select()->where('name = ?', $name));
        $licenseDaos = array();

        foreach ($rows as $row) {
            $licenseDaos[] = $this->initDao('License', $row);
        }

        return $licenseDaos;
    }

    /**
     * Delete a license. All revisions that pointed to this license will now
     * have their license set to null.
     *
     * @param LicenseDao $licenseDao license DAO
     * @return true on success
     * @throws Zend_Exception
     */
    public function delete($licenseDao)
    {
        if (!$licenseDao instanceof LicenseDao) {
            throw new Zend_Exception('Must be a license DAO');
        }

        if (!$licenseDao->saved) {
            throw new Zend_Exception('DAO must be saved');
        }

        // Replace references to this license with null values
        $this->database->getDB()->update(
            'itemrevision',
            array('license_id' => null),
            array('license_id = ?' => $licenseDao->getKey())
        );

        parent::delete($licenseDao);
        unset($licenseDao->license_id);
        $licenseDao->saved = false;

        return true;
    }
}
