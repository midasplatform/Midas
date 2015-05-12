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

require_once BASE_PATH.'/core/models/base/SettingModelBase.php';

/**
 *  SettingModel
 *  Pdo Model.
 */
class SettingModel extends SettingModelBase
{
    /**
     * Get by name.
     *
     * @param string $name
     * @param string $module
     * @return false|SettingDao
     * @throws Zend_Exception
     */
    public function getDaoByName($name, $module = 'core')
    {
        if (!is_string($name) || !is_string($module)) {
            throw new Zend_Exception('Error in Parameters when getting Setting dao by name.');
        }
        $row = $this->database->fetchRow(
            $this->database->select()->where('name = ?', $name)->where('module = ?', $module)
        );
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }
}
