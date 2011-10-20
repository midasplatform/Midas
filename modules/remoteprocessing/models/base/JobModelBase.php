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

class Remoteprocessing_JobModelBase extends Remoteprocessing_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'remoteprocessing_job';
    $this->_key = 'job_id';

    $this->_mainData= array(
        'job_id'=>  array('type'=>MIDAS_DATA),
        'os'=>  array('type'=>MIDAS_DATA),
        'condition'=>  array('type'=>MIDAS_DATA),
        'script'=>  array('type'=>MIDAS_DATA),
        'params'=>  array('type'=>MIDAS_DATA)
        );
    $this->initialize(); // required
    } // end __construct()

} // end class AssetstoreModelBase
?>