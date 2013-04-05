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

/** Base class for instance model */
abstract class Pvw_InstanceModelBase extends Pvw_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'pvw_instance';
    $this->_daoName = 'InstanceDao';
    $this->_key = 'instance_id';

    $this->_mainData = array(
      'instance_id' => array('type' => MIDAS_DATA),
      'item_id' => array('type' => MIDAS_DATA),
      'pid' => array('type' => MIDAS_DATA),
      'sid' => array('type' => MIDAS_DATA),
      'port' => array('type' => MIDAS_DATA),
      'creation_date' => array('type' => MIDAS_DATA),
      'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id')
      );
    $this->initialize();
    }
}
?>
