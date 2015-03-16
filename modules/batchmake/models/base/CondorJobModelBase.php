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

include_once BASE_PATH.'/modules/batchmake/constant/module.php';

/** CondorJob Base class */
class Batchmake_CondorJobModelBase extends Batchmake_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'condor_job';
        $this->_daoName = 'CondorJobDao';
        $this->_key = 'condor_job_id';

        $this->_mainData = array(
            'condor_job_id' => array('type' => MIDAS_DATA),
            'condor_dag_id' => array('type' => MIDAS_DATA),
            'jobdefinition_filename' => array('type' => MIDAS_DATA),
            'output_filename' => array('type' => MIDAS_DATA),
            'error_filename' => array('type' => MIDAS_DATA),
            'log_filename' => array('type' => MIDAS_DATA),
            'post_filename' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }
}
