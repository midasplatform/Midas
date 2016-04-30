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

/** Trend Threshold base model class for the tracker module. */
abstract class Tracker_TrendThresholdModelBase extends Tracker_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();

        $this->_name = 'tracker_trend_threshold';
        $this->_daoName = 'TrendThresholdDao';
        $this->_key = 'trend_threshold_id';
        $this->_mainData = array(
            'trend_threshold_id' => array('type' => MIDAS_DATA),
            'producer_id' => array('type' => MIDAS_DATA),
            'metric_name' => array('type' => MIDAS_DATA),
            'abbreviation' => array('type' => MIDAS_DATA),
            'warning' => array('type' => MIDAS_DATA),
            'fail' => array('type' => MIDAS_DATA),
            'max' => array('type' => MIDAS_DATA),
            'producer' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Producer',
                'module' => $this->moduleName,
                'parent_column' => 'producer_id',
                'child_column' => 'producer_id',
            ),
        );

        $this->initialize();
    }
}
