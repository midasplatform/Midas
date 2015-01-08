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
 * Generic database interface.
 *
 * @package Core\Database
 */
interface MIDASDatabaseInterface
{
    /**
     * Save the an array of data to the database.
     *
     * @param array $dataArray array of data
     */
    public function save($dataArray);

    /**
     * Delete a DAO from the database.
     *
     * @param MIDAS_GlobalDao $dao DAO
     */
    public function delete($dao);

    /**
     * Fetch a value from the database.
     *
     * @param string $var variable name
     * @param string $key key
     * @param MIDAS_GlobalDao $dao DAO
     * @return mixed value
     */
    public function getValue($var, $key, $dao);

    /**
     * Fetch all values from the database.
     *
     * @param array $keys list of keys
     * @return array list of values
     */
    public function getAllByKey($keys);
}
