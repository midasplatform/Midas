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
 * Base Model for validation dashboards. These consist of testing, training,
 * and truth data along with a simple name a description. Additionally, a
 * metric for assessing the quality of submitted results as well as a relation
 * for linking submitted result folders to the dashboard are defined. Finally,
 * an owner is specified as the user in charge of running the dashboard. Only
 * he or a site administrator may modify the dashboard in any way.
 */
abstract class Validation_DashboardModelBase extends Validation_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'validation_dashboard';
        $this->_key = 'dashboard_id';

        $this->_mainData = array(
            'dashboard_id' => array('type' => MIDAS_DATA),
            'owner_id' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'description' => array('type' => MIDAS_DATA),
            'truthfolder_id' => array('type' => MIDAS_DATA),
            'testingfolder_id' => array('type' => MIDAS_DATA),
            'trainingfolder_id' => array('type' => MIDAS_DATA),
            'metric_id' => array('type' => MIDAS_DATA),
            'owner' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'owner_id',
                'child_column' => 'user_id',
            ),
            'truth' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Folder',
                'parent_column' => 'truthfolder_id',
                'child_column' => 'folder_id',
            ),
            'training' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Folder',
                'parent_column' => 'trainingfolder_id',
                'child_column' => 'folder_id',
            ),
            'testing' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Folder',
                'parent_column' => 'testingfolder_id',
                'child_column' => 'folder_id',
            ),
            'results' => array(
                'type' => MIDAS_MANY_TO_MANY,
                'model' => 'Folder',
                'table' => 'validation_dashboard2folder',
                'parent_column' => 'dashboard_id',
                'child_column' => 'folder_id',
            ),
            'scores' => array(
                'type' => MIDAS_MANY_TO_MANY,
                'model' => 'ScalarResult',
                'module' => 'validation',
                'table' => 'validation_dashboard2scalarresult',
                'parent_column' => 'dashboard_id',
                'child_column' => 'scalarresult_id',
            ),
        );
        $this->initialize(); // required
    }

    /**
     * Set the truth folder of the dashboard
     *
     * @param dashboard the target dashboard
     * @param folder the target folder
     * @return void
     */
    public function setTruth($dashboard, $folder)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dasboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $dashboard->setTruthfolderId($folder->getKey());
        parent::save($dashboard);
    }

    /**
     * Set the training folder of the dashboard
     *
     * @param dashboard the target dashboard
     * @param folder the target folder
     * @return void
     */
    public function setTraining($dashboard, $folder)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dasboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $dashboard->setTrainingfolderId($folder->getKey());
        parent::save($dashboard);
    }

    /**
     * Set the testing folder of the dashboard
     *
     * @param dashboard the target dashboard
     * @param folder the target folder
     * @return void
     */
    public function setTesting($dashboard, $folder)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dasboard.");
        }
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception("Should be a folder.");
        }
        $dashboard->setTestingfolderId($folder->getKey());
        parent::save($dashboard);
    }

    /**
     * Verify that the testing, truth, and training folders contain the same
     * number of items and that the item names correspond.
     *
     * @param dashboard the target dashboard
     * @return boolean true if valid, false if invalid
     */
    public function checkConsistency($dashboard)
    {
        if (!$dashboard instanceof Validation_DashboardDao) {
            throw new Zend_Exception("Should be a dashboard.");
        }
        $testing = $dashboard->getTesting();
        $training = $dashboard->getTraining();
        $truth = $dashboard->getTruth();

        $testingItems = $testing->getItems();
        $trainingItems = $training->getItems();
        $truthItems = $truth->getItems();

        if (count($testingItems) == count($trainingItems) && count($trainingItems) == count($truthItems)
        ) {
            $fn = create_function(
                '$first,$second',
                'return strcmp( $first->getName(),
                                            $second->getName());'
            );
            usort($testingItems, $fn);
            usort($trainingItems, $fn);
            usort($truthItems, $fn);
            $len = count($truthItems);
            for ($i = 0; $i < $len; ++$i) {
                if (strcmp($testingItems[$i]->getName(), $trainingItems[$i]->getName()) != 0
                ) {
                    return false;
                }
                if (strcmp($trainingItems[$i]->getName(), $truthItems[$i]->getName()) != 0
                ) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a result folder to the dashboard. The names of the items in the
     * folder should correspond to the ones in testing, training, and truth.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     */
    abstract public function addResult($dashboard, $folder);

    /**
     * Remove a result folder from the dashboard.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     */
    abstract public function removeResult($dashboard, $folder);

    /**
     * Set a single row of result values for a dashboard.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     * @param array $values
     */
    abstract public function setScores($dashboard, $folder, $values);

    /**
     * Set a single result value.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     * @param ItemDao $item
     * @param array $values
     * @return Validation_ScalarResultDao
     */
    abstract public function setScore($dashboard, $folder, $item, $values);

    /**
     * Get a single set of scores for a dashboard.
     *
     * @param Validation_DashboardDao $dashboard
     * @param FolderDao $folder
     * @return array
     */
    abstract public function getScores($dashboard, $folder);

    /**
     * Get all sets of scores for a dashboard.
     *
     * @param Validation_DashboardDao $dashboard
     * @return array
     */
    abstract public function getAllScores($dashboard);
}
