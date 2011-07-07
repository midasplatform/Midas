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

/** Custom production profiler */
class ProductionDbProfiler extends Zend_Db_Profiler  
  {  
  protected $_lastQueryText;  
  protected $_lastQueryType;  
  
  /** queryStart*/
  public function queryStart($queryText, $queryType = null)  
    {  
    $this->_lastQueryText = $queryText;  
    $this->_lastQueryType = $queryType;  
  
    return null;  
    }  
  
  /** queryEnd*/
  public function queryEnd($queryId)  
    {  
    $queryId++;
    return;  
    }  
  
  /** getQueryProfile*/
  public function getQueryProfile($queryId)  
    {  
    $queryId++;
    return null;  
    }  
  
  /** getLastQueryProfile*/
  public function getLastQueryProfile()  
    {  
    $queryId = parent::queryStart($this->_lastQueryText, $this->_lastQueryType);  
  
    return parent::getLastQueryProfile();  
    }  
}  
?>
