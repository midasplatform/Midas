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

require_once BASE_PATH.'/modules/packages/models/base/PackageModelBase.php';

/**
 * Package PDO Model
 */
class Packages_PackageModel extends Packages_PackageModelBase
  {
  /**
   * Return all the record in the table
   * @param params Optional associative array specifying an 'os', 'arch', 'submissiontype' and 'packagetype'.
   * @return Array of package Daos
   */
  function get($params = array('os' => 'any', 'arch' => 'any',
                               'submissiontype' => 'any', 'packagetype' => 'any',
                               'revision' => 'any', 'productname' => 'any',
                               'codebase' => 'any', 'release' => 'any'))
    {
    $sql = $this->database->select();
    foreach(array('os', 'arch', 'submissiontype', 'packagetype', 'revision', 'productname', 'codebase', 'release', 'application_id') as $option)
      {
      if(array_key_exists($option, $params) && $params[$option] != 'any')
        {
        $sql->where('packages_package.'.$option.' = ?', $params[$option]);
        }
      }
    if(array_key_exists('order', $params))
      {
      $direction = array_key_exists('direction', $params) ? strtoupper($params['direction']) : 'ASC';
      $sql->order($params['order'].' '.$direction);
      }
    if(array_key_exists('limit', $params) && is_numeric($params['limit']) && $params['limit'] > 0)
      {
      $sql->limit($params['limit']);
      }
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Package', $row, 'packages');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

  /** get all package records */
  public function getAll()
    {
    return $this->database->getAll('Package', 'packages');
    }

  /**
   * Return a package_Package dao based on an itemId.
   */
  public function getByItemId($itemId)
    {
    $sql = $this->database->select()->where('item_id = ?', $itemId);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Package', $row, 'packages');
    return $dao;
    }

  /**
   * For the given os, arch, and application (and optionally submission type),
   * return the most recent package of each package type
   */
  public function getLatestOfEachPackageType($application, $os, $arch, $submissiontype = null)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->from('packages_package', array('packagetype'))
                ->where('application_id = ?', $application->getKey())
                ->where('os = ?', $os)
                ->where('arch = ?', $arch)
                ->distinct();
    if($submissiontype)
      {
      $sql->where('submissiontype = ?', $submissiontype);
      }
    $rowset = $this->database->fetchAll($sql);
    $types = array();
    foreach($rowset as $row)
      {
      $types[] = $row['packagetype'];
      }

    // For each distinct package type, get the most recent matching dao
    $latestPackages = array();
    foreach($types as $type)
      {
      $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('application_id = ?', $application->getKey())
                ->where('os = ?', $os)
                ->where('arch = ?', $arch)
                ->where('packagetype = ?', $type)
                ->order('checkoutdate DESC')
                ->limit(1);
      if($submissiontype)
        {
        $sql->where('submissiontype = ?', $submissiontype);
        }
      $row = $this->database->fetchRow($sql);
      $latestPackages[] = $this->initDao('Package', $row, 'packages');
      }
    return $latestPackages;
    }
  } // end class
