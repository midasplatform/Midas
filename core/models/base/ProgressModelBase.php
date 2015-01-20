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
 * Producer base model class.
 *
 * The progress object is used to store a generic representation of the
 * progress of some event. It is used within the Midas Server to keep track of
 * events that can take a long time to run. As the event is running on the
 * server, clients may asynchronously poll the server to request the current
 * progress of the event's execution.
 *
 * If the progress object has a maximum value of 0, then the progress is
 * indeterminate.
 *
 * @package Core\Model
 */
abstract class ProgressModelBase extends AppModel
{
    /** Constructor. */
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
     * Create a new progress record beginning with the current value equal to 0.
     *
     * @param int $max maximum value of the progress (default is 0 for indeterminate)
     * @param string $message initial progress message (default is empty)
     * @return ProgressDao progress DAO
     */
    public function createProgress($max = 0, $message = '')
    {
        /** @var ProgressDao $progress */
        $progress = MidasLoader::newDao('ProgressDao');
        $progress->setCurrent(0);
        $progress->setMaximum($max);
        $progress->setMessage($message);
        $progress->setDateCreation(date('Y-m-d H:i:s'));
        $progress->setLastUpdate(date('Y-m-d H:i:s'));

        $this->save($progress);

        return $progress;
    }

    /**
     * Update a progress record. Touches its update timestamp and sets its value.
     *
     * @param ProgressDao $progressDao progress record to update
     * @param int $currentValue current value of the progress
     * @param string $message progress message
     */
    public function updateProgress($progressDao, $currentValue, $message = '')
    {
        $progressDao->setCurrent((int) $currentValue);
        $progressDao->setMessage($message);
        $progressDao->setLastUpdate(date('Y-m-d H:i:s'));

        $this->save($progressDao);
    }

    /**
     * Override the default save so that we can unlock the session, which is
     * required for concurrent progress polling.  See documentation of
     * session_write_close for an explanation.
     *
     * @param ProgressDao $progressDao
     */
    public function save($progressDao)
    {
        session_write_close();
        parent::save($progressDao);
    }
}
