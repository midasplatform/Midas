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

require_once BASE_PATH.'/core/models/base/ErrorlogModelBase.php';

/**
 * \class ErrorlogModel
 * \brief Pdo Model
 */
class ErrorlogModel extends ErrorlogModelBase
{

  /**
   *  Return a list of log
   * @param type $startDate
   * @param type $endDate
   * @param type $module
   * @param type $priority
   * @param type $limit
   * @return array ErrorlogDao
   */
  function getLog($startDate, $endDate, $module = 'all', $priority = 'all', $limit = 99999)
    {
    $result = array();
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'errorlog'))
            ->where('datetime >= ?', $startDate)
            ->where('datetime <= ?', $endDate)
            ->order('datetime DESC')
            ->limit($limit);
    if($module != 'all')
      {
      $sql->where('module = ?', $module);
      }
    if($priority != 'all')
      {
      $sql->where('priority = ?', $priority);
      }
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      $result[] = $this->initDao('Errorlog', $row);
      }
    return $result;
    }//getLog

} // end class
?>
