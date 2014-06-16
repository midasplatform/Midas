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

require_once BASE_PATH.'/modules/packages/models/base/ProjectModelBase.php';

/**
 * Package PDO Model
 */
class Packages_ProjectModel extends Packages_ProjectModelBase
  {
  /**
   * Get all enabled project communities
   */
  public function getAllEnabled()
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from(array('p' => 'packages_project'))
                ->where('enabled = ?', 1)
                ->joinLeft(array('c' => 'community'), 'p.community_id=c.community_id')
                ->order('c.name', 'ASC');
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $row)
      {
      $dao = $this->initDao('Project', $row, 'packages');
      $dao->name = $row['name'];
      $results[] = $dao;
      }
    return $results;
    }

  /**
   * Return a package dao based on a community Id.
   */
  public function getByCommunityId($communityId)
    {
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->where('community_id = ?', $communityId);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Project', $row, 'packages');
    return $dao;
    }
  }
