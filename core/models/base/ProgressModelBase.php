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
 * Progress model base class
 *
 * The progress object is used to store a generic representation
 * of the progress of some event.  It is used within Midas to keep track
 * of events that can take a long time to run.  As the event is running on the server,
 * clients may asynchronously poll the server to request the current progress
 * of the event's execution.
 *
 * If the progress object has a maximum value of 0, that means that the
 * progress is indeterminate.
 */
abstract class ProgressModelBase extends AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'progress';
        $this->_key = 'progress_id';

        $this->_mainData = array(
            'progress_id' => array('type' => MIDAS_DATA),
            'current' => array('type' => MIDAS_DATA),
            'maximum' => array('type' => MIDAS_DATA),
            'message' => array('type' => MIDAS_DATA),
            'date_creation' => array('type' => MIDAS_DATA),
            'last_update' => array('type' => MIDAS_DATA),
        );
        $this->initialize();
    }

    /**
     * Create a new progress record beginning with current value 0
     *
     * @param max The max (default is 0 for indeterminate)
     * @param message The initial progress message (defaults to empty)
     * @return The progress dao that was created
     */
    public function createProgress($max = 0, $message = '')
    {
        $progress = MidasLoader::newDao('ProgressDao');
        $progress->setCurrent(0);
        $progress->setMaximum($max);
        $progress->setMessage($message);
        $progress->setDateCreation(date("Y-m-d H:i:s"));
        $progress->setLastUpdate(date("Y-m-d H:i:s"));

        $this->save($progress);

        return $progress;
    }

    /**
     * Update a progress record.  Touches its update timestamp and sets its value.
     *
     * @param progressDao The progress record to update
     * @param currentValue The current value of the progress
     */
    public function updateProgress($progressDao, $currentValue, $message = '')
    {
        $progressDao->setCurrent((int)$currentValue);
        $progressDao->setMessage($message);
        $progressDao->setLastUpdate(date("Y-m-d H:i:s"));

        $this->save($progressDao);
    }

    /**
     * Override default save so that we can unlock the session,
     * which is required for concurrent progress polling.  See documentation of
     * session_write_close for explanation.
     */
    public function save($dao)
    {
        session_write_close();
        parent::save($dao);
    }
}
