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
require_once BASE_PATH.'/modules/packages/models/base/ExtensionModelBase.php';

/**
 * Package PDO Model
 */
class Packages_ExtensionModel extends Packages_ExtensionModelBase
{
  /**
   * Return all the records in the table
   * @param params Optional associative array specifying 'extension_id', 'os', 'arch',
   *               'submissiontype', 'packagetype', 'slicer_revision', 'revision',
   *               'productname', 'codebase', 'release' and 'category'.
   *               Can also specify 'order', 'direction', 'limit', and 'offset'.
   * @return array('extensions' => list of matching extensions,
   *               'total' => number of total matching extensions
   */
  function get($params = array('extension_id' => 'any', 'os' => 'any', 'arch' => 'any',
                               'submissiontype' => 'any', 'packagetype' => 'any',
                               'slicer_revision' => 'any', 'revision' => 'any',
                               'productname' => 'any', 'codebase' => 'any',
                               'release' => 'any', 'category' => 'any'))
    {
    $sql = $this->database->select();
    $sqlCount = $this->database->select()
                     ->from(array($this->_name), array('count' => 'count(*)'));
    foreach(array('extension_id', 'os', 'arch', 'submissiontype', 'packagetype', 'revision', 'application_revision', 'productname', 'codebase', 'release', 'category') as $option)
      {
      if(array_key_exists($option, $params) && $params[$option] != 'any')
        {
        if($option == 'category') //category searches by prefix and among a list of categories
          {
          $category = $params['category'];
          $filterClause = "packages_extension.category = '".$category."'"
                      ." OR packages_extension.category LIKE '".$category.".%'"
                      ." OR packages_extension.category LIKE '".$category.";%'"
                      ." OR packages_extension.category LIKE '%;".$category.".%'"
                      ." OR packages_extension.category LIKE '%;".$category.";%'"
                      ." OR packages_extension.category LIKE '%;".$category."'";

          $sql->where($filterClause);
          $sqlCount->where($filterClause);
          }
        else
          {
          $fieldname = $option;
          $filterClause = 'slicerpackages_extension.'.$fieldname.' = ?';
          $sql->where($filterClause, $params[$option]);
          $sqlCount->where($filterClause, $params[$option]);
          }
        }
      }
    if(array_key_exists('order', $params))
      {
      $direction = array_key_exists('direction', $params) ? strtoupper($params['direction']) : 'ASC';
      $sql->order($params['order'].' '.$direction);
      }
    if(array_key_exists('limit', $params) && is_numeric($params['limit']) && $params['limit'] > 0)
      {
      $offset = isset($params['offset']) ? $params['offset'] : 0;
      $sql->limit($params['limit'], $offset);
      }
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed = array();
    foreach($rowset as $keyRow => $row)
      {
      $tmpDao = $this->initDao('Extension', $row, 'packages');
      $rowsetAnalysed[] = $tmpDao;
      }
    $countRow = $this->database->fetchRow($sqlCount);
    return array('extensions' => $rowsetAnalysed, 'total' => $countRow['count']);
    }

  /** get all extension records */
  public function getAll()
    {
    return $this->database->getAll('Extension', 'packages');
    }

  /**
   * Return a slicerpackage_extension dao based on an itemId.
   */
  public function getByItemId($itemId)
    {
    $sql = $this->database->select()->where('item_id = ?', $itemId);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Extension', $row, 'packages');
    return $dao;
    }

  /**
   * Return a list of all distinct categories for the given application
   */
  public function getAllCategories($applicationId)
    {
    $sql = $this->database->select()
                ->from(array('e' => 'packages_extension'), array('category'))
                ->where('application_id = ?', $applicationId)
                ->where('category != ?', '')
                ->distinct();
    $categories = array();
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $row)
      {
      $categoryList = explode(';', $row['category']);
      foreach($categoryList as $category)
        {
        $categories[$category] = 1;
        }
      }
    return array_keys($categories);
    }

  /**
   * Return a list of all distinct releases
   * @param applicationId What application id to search on
   */
  public function getAllReleases($applicationId)
    {
    $sql = $this->database->select()
                ->from(array('e' => 'packages_extension'), array('release'))
                ->where('application_id = ?', $applicationId)
                ->where('e.release != ?', '')
                ->distinct();
    $releases = array();
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $row)
      {
      $releases[] = $row['release'];
      }
    return $releases;
    }
}
