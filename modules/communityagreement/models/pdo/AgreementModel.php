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
require_once BASE_PATH.'/modules/communityagreement/models/base/AgreementModelBase.php';

/**
 * Communityagreement_AgreementModel
 *
 * agreement pdo model
 *
 * @category   Midas modules
 * @package    communityagreement
 */
class Communityagreement_AgreementModel extends Communityagreement_AgreementModelBase
{
  /**
   * get all the community agreements
   *
   * @return array of agreementDao
   */
  function getAll()
    {
    $sql = $this->database->select();
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Agreement', $row, 'communityagreement');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

  /**
   * Get an agreement by communityid
   *
   * @param string $community_id
   * @return agreementDao
   */
  function getByCommunityId($community_id)
    {
    $row = $this->database->fetchRow($this->database->select()->where('community_id=?', $community_id));
    $dao = $this->initDao('Agreement', $row, 'communityagreement');
    return $dao;
    }
}  // end class