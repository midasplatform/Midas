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

require_once BASE_PATH.'/core/models/dao/ModuleDao.php';

/** Module base model class. */
abstract class ModuleModelBase extends AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'module';
        $this->_key = 'module_id';

        $this->_mainData = array(
            'module_id' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'uuid' => array('type' => MIDAS_DATA),
            'current_major_version' => array('type' => MIDAS_DATA),
            'current_minor_version' => array('type' => MIDAS_DATA),
            'current_patch_version' => array('type' => MIDAS_DATA),
            'enabled' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }

    /**
     * Return a module given its name.
     *
     * @param string $name name
     * @return false|ModuleDao or false on failure
     */
    abstract public function getByName($name);

    /**
     * Return a module given its UUID.
     *
     * @param string $uuid UUID
     * @return false|ModuleDao module DAO or false on failure
     */
    abstract public function getByUuid($uuid);

    /**
     * Return the modules that are enabled.
     *
     * @param bool $enabled true if a module is enabled
     * @return array module DAOs
     */
    abstract public function getEnabled($enabled = true);
}
