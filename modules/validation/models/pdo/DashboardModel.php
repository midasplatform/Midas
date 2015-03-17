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

require_once BASE_PATH.'/modules/validation/models/base/DashboardModelBase.php';

/**
 * Dashboard PDO Model
 */
class Validation_DashboardModel extends Validation_DashboardModelBase
{
    /**
     * Return all the records in the table.
     *
     * @return array of validation DAOs
     * @throws Zend_Exception
     */
    public function getAll()
    {
        $sql = $this->database->select();
        $rowset = $this->database->fetchAll($sql);
        $rowsetAnalysed = array();
        foreach ($rowset as $row) {
            $tmpDao = $this->initDao('Dashboard', $row, 'validation');
            $rowsetAnalysed[] = $tmpDao;
        }

        return $rowsetAnalysed;
    }

    /**
     * Add a results folder to the dashboard.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     * @throws Zend_Exception
     */
    public function addResult($dashboard, $folder)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $this->database->link('results', $dashboard, $folder);
    }

    /**
     * Remove a results folder from the dashboard.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     * @throws Zend_Exception
     */
    public function removeResult($dashboard, $folder)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('d' => 'validation_dashboard'))->join(
            array('j' => 'validation_dashboard2scalarresult'),
            'd.dashboard_id = j.dashboard_id'
        )->join(
            array('r' => 'validation_scalarresult'),
            'j.scalarresult_id = r.scalarresult_id'
        )->where('r.folder_id = '.$folder->getKey())->where('d.dashboard_id = '.$dashboard->getKey());
        $rowset = $this->database->fetchAll($sql);
        foreach ($rowset as $row) {
            $tmpDao = $this->initDao('ScalarResult', $row, 'validation');
            $this->database->removeLink('scores', $dashboard, $tmpDao);
        }
        $this->database->removeLink('results', $dashboard, $folder);
    }

    /**
     * Set a single row of result values for a dashboard.
     *
     * @param Validation_DashboardDao $dashboard target dashboard
     * @param FolderDao $folder result folder with which the values are associated
     * @param array $values array where the keys are item ids and the values are scalar results
     * @throws Zend_Exception
     */
    public function setScores($dashboard, $folder, $values)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }

        /** @var Validation_ScalarResultModel $scalarResultModel */
        $scalarResultModel = MidasLoader::loadModel('ScalarResult', 'validation');

        $items = $folder->getItems();
        $numItems = count($items);
        for ($i = 0; $i < $numItems; ++$i) {
            $curItemKey = $items[$i]->getKey();

            /** @var Validation_ScalarResultDao $scalarResult */
            $scalarResult = MidasLoader::newDao('ScalarResultDao', 'validation');
            $scalarResult->setFolderId($folder->getKey());
            $scalarResult->setItemId($curItemKey);
            $scalarResult->setValue($values[$curItemKey]);
            $scalarResultModel->save($scalarResult);
            $this->database->link('scores', $dashboard, $scalarResult);
        }
    }

    /**
     * Set a single result value.
     *
     * @param Validation_DashboardDao $dashboard target dashboard
     * @param FolderDao $folder result folder with which the value is associated
     * @param ItemDao $item item associated with the result
     * @param mixed $value scalar value representing a result
     * @return Validation_ScalarResultDao
     * @throws Zend_Exception
     */
    public function setScore($dashboard, $folder, $item, $value)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception("Should be an item.");
        }

        /** @var Validation_ScalarResultModel $scalarResultModel */
        $scalarResultModel = MidasLoader::loadModel('ScalarResult', 'validation');
        $items = $folder->getItems();
        $tgtItem = null;
        foreach ($items as $curItem) {
            if ($curItem->getKey() == $item->getKey()) {
                $tgtItem = $curItem;
                break;
            }
        }
        if (!$tgtItem) {
            throw new Zend_Exception('Target item not part of result set.');
        }

        // remove a previous scalar value if there is one.
        $oldResults = $scalarResultModel->findBy('item_id', $tgtItem->getKey());
        if (count($oldResults) == 1) {
            $oldResult = $oldResults[0];
            $this->database->removeLink('scores', $dashboard, $oldResult);
        }

        /** @var Validation_ScalarResultDao $scalarResult */
        $scalarResult = MidasLoader::newDao('ScalarResultDao', 'validation');
        $scalarResult->setFolderId($folder->getKey());
        $scalarResult->setItemId($tgtItem->getKey());
        $scalarResult->setValue($value);
        $scalarResultModel->save($scalarResult);
        $this->database->link('scores', $dashboard, $scalarResult);

        return $scalarResult;
    }

    /**
     * Get a single set of scores for a dashboard.
     *
     * @param Validation_DashboardDao $dashboard target dashboard
     * @param FolderDao $folder folder that corresponds to the results
     * @return array array where the keys are item ids and the values are scores
     * @throws Zend_Exception
     */
    public function getScores($dashboard, $folder)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('d' => 'validation_dashboard'))->join(
            array('j' => 'validation_dashboard2scalarresult'),
            'd.dashboard_id = j.dashboard_id'
        )->join(
            array('r' => 'validation_scalarresult'),
            'j.scalarresult_id = r.scalarresult_id'
        )->where('r.folder_id = '.$folder->getKey())->where('d.dashboard_id = '.$dashboard->getKey());
        $rowset = $this->database->fetchAll($sql);
        $results = array();
        foreach ($rowset as $row) {
            $results[$row["item_id"]] = $row["value"];
        }

        return $results;
    }

    /**
     * Get all sets of scores for a dashboard
     *
     * @param Validation_DashboardDao $dashboard target dashboard
     * @return array array of arrays where the keys are folder ids and the values are arrays where the keys are
     *               item ids and the values are scores
     * @throws Zend_Exception
     */
    public function getAllScores($dashboard)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }

        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('d' => 'validation_dashboard'))->join(
            array('j' => 'validation_dashboard2scalarresult'),
            'd.dashboard_id = j.dashboard_id'
        )->join(
            array('r' => 'validation_scalarresult'),
            'j.scalarresult_id = r.scalarresult_id'
        )->where('d.dashboard_id = '.$dashboard->getKey());
        $rowset = $this->database->fetchAll($sql);
        $results = array();
        foreach ($rowset as $row) {
            if (isset($results[$row["folder_id"]])) {
                $results[$row["folder_id"]][$row["item_id"]] = $row["value"];
            } else {
                $results[$row["folder_id"]] = array();
                $results[$row["folder_id"]][$row["item_id"]] = $row["value"];
            }
        }

        return $results;
    }
}
