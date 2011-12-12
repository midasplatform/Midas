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

/** Error log Model Base*/
abstract class ErrorlogModelBase extends AppModel
{
  /** Contructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'errorlog';
    $this->_key = 'errorlog_id';
    $this->_mainData = array(
      'errorlog_id' => array('type' => MIDAS_DATA),
      'module' => array('type' => MIDAS_DATA),
      'message' => array('type' => MIDAS_DATA),
      'datetime' => array('type' => MIDAS_DATA),
      'priority' => array('type' => MIDAS_DATA),
      );
    $this->initialize(); // required
    } // end __construct()

  /** get Log Error */
  abstract function getLog($startDate, $endDate, $module = 'all', $priority = 'all', $limit = 99999);
} // end class FeedModelBase
