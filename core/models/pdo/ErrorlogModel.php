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

require_once BASE_PATH.'/core/models/base/ErrorlogModelBase.php';

/**
 * Pdo Model
 */
class ErrorlogModel extends ErrorlogModelBase
{
    /**
     * Return a list of logs.
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $module
     * @param int $priority
     * @param int $limit
     * @param int $offset
     * @param string $operator
     * @return array
     */
    public function getLog(
        $startDate,
        $endDate,
        $module = 'all',
        $priority = MIDAS_PRIORITY_WARNING,
        $limit = 99999,
        $offset = 0,
        $operator = '<='
    ) {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('e' => 'errorlog'))->where(
            'datetime >= ?',
            $startDate
        )->where(
            'datetime <= ?',
            $endDate
        )->where('priority '.$operator.' ?', $priority)->order('datetime DESC')->limit($limit, $offset);
        $sqlCount = $this->database->select()->setIntegrityCheck(false)->from(
            array('e' => 'errorlog'),
            array('count' => 'count(*)')
        )->where(
            'datetime >= ?',
            $startDate
        )->where('datetime <= ?', $endDate)->where('priority '.$operator.' ?', $priority);
        if ($module != 'all') {
            $sql->where('module = ?', $module);
            $sqlCount->where('module = ?', $module);
        }

        $rowset = $this->database->fetchAll($sql);
        $result = array('logs' => array());
        foreach ($rowset as $row) {
            $result['logs'][] = $this->initDao('Errorlog', $row);
        }
        $countrow = $this->database->fetchRow($sqlCount);
        $result['total'] = $countrow['count'];

        return $result;
    }

    /**
     * Count the number of log entries since a certain date
     *
     * @param string $startDate start date
     * @param null|array $priorities priorities to filter by. If null, selects all.
     * @return int
     */
    public function countSince($startDate, $priorities = null)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('e' => 'errorlog'),
            array('count(*)')
        )->where('datetime >= ?', $startDate);

        if ($priorities != null) {
            $sql->where('priority IN (?)', $priorities);
        }

        $row = $this->database->fetchRow($sql);
        if (isset($row['count(*)'])) {
            return $row['count(*)'];
        }
        if (isset($row['count'])) { // for pgsql

            return $row['count'];
        }

        return 0;
    }
}
