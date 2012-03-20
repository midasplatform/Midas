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

require_once BASE_PATH.'/core/models/base/ActivedownloadModelBase.php';

/**
 * \class ActivedownloadModel
 */
class ActivedownloadModel extends ActivedownloadModelBase
{
  /** Check whether an active download exists for the given ip */
  public function getByIp($ip)
    {
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('a' => 'activedownload'))
            ->where('ip = ?', $ip);
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      return $this->initDao('Activedownload', $row);
      }
    return false;
    }

}  // end class
