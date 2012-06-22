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
require_once BASE_PATH.'/modules/packages/models/base/ApplicationModelBase.php';

/**
 * Application PDO Model
 */
class Packages_ApplicationModel extends Packages_ApplicationModelBase
{
  /**
   * Get all applications under a given project
   */
  public function getAllByProjectId($projectId)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('project_id = ?', $projectId)
                ->order('name', 'ASC');
    $rowset = $this->database->fetchAll($sql);
    $results = array();
    foreach($rowset as $row)
      {
      $dao = $this->initDao('Application', $row, 'packages');
      $results[] = $dao;
      }
    return $results;
    }

  /**
   * Get all distinct releases for an application.
   * Sorting of release names is left to the caller.
   */
  public function getAllReleases($application)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from('packages_package', array('release'))
                ->where('application_id = ?', $application->getKey())
                ->distinct();
    $rowset = $this->database->fetchAll($sql);
    $releases = array();
    foreach($rowset as $row)
      {
      $release = $row['release'];
      if($release != '')
        {
        $releases[] = $release;
        }
      }
    return $releases;
    }

  /**
   * Return all distinct (os, arch) tuples corresponding to this application
   * @param application The application dao
   * @return set of tuples with 'os' and 'arch' keys
   */
  public function getDistinctPlatforms($application)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from('packages_package', array('os', 'arch'))
                ->where('application_id = ?', $application->getKey())
                //->where('submissiontype LIKE ?', 'nightly')
                ->distinct();
    $rowset = $this->database->fetchAll($sql);
    $platforms = array();
    foreach($rowset as $row)
      {
      $platforms[] = array(
        'os' => $row['os'],
        'arch' => $row['arch']);
      }
    return $platforms;
    }

  /**
   * Deletes the application, as well as all associated package and extension records
   */
  public function delete($application)
    {
    $this->database->getDB()->delete('packages_package', 'application_id = '.$application->getKey());
    $this->database->getDB()->delete('packages_extension', 'application_id = '.$application->getKey());
    parent::delete($application);
    }
}
