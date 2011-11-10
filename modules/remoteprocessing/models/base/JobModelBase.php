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
/** Job Model*/
class Remoteprocessing_JobModelBase extends Remoteprocessing_AppModel
{
  /** construct */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'remoteprocessing_job';
    $this->_key = 'job_id';

    $this->_mainData = array(
        'job_id' =>  array('type' => MIDAS_DATA),
        'os' =>  array('type' => MIDAS_DATA),
        'condition' =>  array('type' => MIDAS_DATA),
        'script' =>  array('type' => MIDAS_DATA),
        'params' =>  array('type' => MIDAS_DATA),
        'status' =>  array('type' => MIDAS_DATA),
        'creator_id' =>  array('type' => MIDAS_DATA),
        'expiration_date' =>  array('type' => MIDAS_DATA),
        'creation_date' =>  array('type' => MIDAS_DATA),
        'start_date' =>  array('type' => MIDAS_DATA),
        'items' =>  array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Item', 'table' => 'remoteprocessing_job2item', 'parent_column' => 'job_id', 'child_column' => 'item_id'),
        'creator' =>  array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'creator_id', 'child_column' => 'user_id'),
        );
    $this->initialize(); // required
    } // end __construct()


  /** save */
  public function save($dao)
    {
    $dao->setCreationDate(date('c'));
    parent::save($dao);
    }

} // end class AssetstoreModelBase
