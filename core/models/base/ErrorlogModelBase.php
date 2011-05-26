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
