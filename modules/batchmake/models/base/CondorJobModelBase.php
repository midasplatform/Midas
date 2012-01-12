<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
include_once BASE_PATH . '/modules/batchmake/constant/module.php';
/** CondorJob Base class */
class Batchmake_CondorJobModelBase extends Batchmake_AppModel {




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
      'post_filename' => array('type' => MIDAS_DATA));
    $this->initialize(); // required
    }




}  // end class Batchmake_CondorJobModelBase
