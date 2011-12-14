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
/** CondorDag Base class */
class Batchmake_CondorDagModelBase extends Batchmake_AppModel {




  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'condor_dag';
    $this->_daoName = 'CondorDagDao';
    $this->_key = 'condor_dag_id';

    $this->_mainData = array(
      'condor_dag_id' => array('type' => MIDAS_DATA),
      'batchmake_task_id' => array('type' => MIDAS_DATA),
      'out_filename' => array('type' => MIDAS_DATA),
      'dag_filename' => array('type' => MIDAS_DATA));
    $this->initialize(); // required
    }






}  // end class Batchmake_CondorDagModelBase
