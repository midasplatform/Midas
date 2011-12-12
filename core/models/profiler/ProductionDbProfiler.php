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
