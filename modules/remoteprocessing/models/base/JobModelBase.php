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
        'name' =>  array('type' => MIDAS_DATA),
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
