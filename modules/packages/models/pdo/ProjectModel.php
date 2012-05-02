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
