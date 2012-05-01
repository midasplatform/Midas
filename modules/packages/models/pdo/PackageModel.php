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
require_once BASE_PATH.'/modules/slicerpackages/models/base/PackageModelBase.php';

/**
 * Package PDO Model
 */
class Slicerpackages_PackageModel extends Slicerpackages_PackageModelBase
{
  /**
   * Return all the record in the table
   * @param params Optional associative array specifying an 'os', 'arch', 'submissiontype' and 'packagetype'.
   * @return Array of SlicerpackagesDao
   */
  function get($params = array('os' => 'any', 'arch' => 'any',
                               'submissiontype' => 'any', 'packagetype' => 'any',
                               'revision' => 'any', 'productname' => 'any',
                               'codebase' => 'any', 'release' => 'any'))
    {
    $sql = $this->database->select();
    foreach(array('os', 'arch', 'submissiontype', 'packagetype', 'revision', 'productname', 'codebase', 'release') as $option)
      {
      if(array_key_exists($option, $params) && $params[$option] != 'any')
        {
        $sql->where('slicerpackages_package.'.$option.' = ?', $params[$option]);
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
      $tmpDao = $this->initDao('Package', $row, 'slicerpackages');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }

  /** get all package records */
  public function getAll()
    {
    return $this->database->getAll('Package', 'slicerpackages');
    }

  /**
   * Return a slicerpackage_Package dao based on an itemId.
   */
  public function getByItemId($itemId)
    {
    $sql = $this->database->select()->where('item_id = ?', $itemId);
    $row = $this->database->fetchRow($sql);
    $dao = $this->initDao('Package', $row, 'slicerpackages');
    return $dao;
    }

  private function _getMostRecentCreatedPackages($folderDaos, $operatingSystems = array(), $architectures = array())
    {
    if(!is_array($folderDaos))
      {
      $folderDaos = array($folderDaos);
      }

    $skipOperatingSystems = $operatingSystems === false;
    if($skipOperatingSystems and !is_array($operatingSystems))
      {
      $operatingSystems = array($operatingSystems);
      }

    $skipArchitecture = $architectures === false;
    if(!$skipArchitecture and !is_array($architectures))
      {
      $architectures = array($architectures);
      }

    $folderIds = array();
    foreach($folderDaos as $key => $folderDao)
      {
      if(!$folderDao instanceof FolderDao)
        {
        throw new Zend_Exception("Should be a folder.");
        }
      $folderIds[] = $folderDao->getKey();
      }
    $subSelect = $this->database->select()
                      ->setIntegrityCheck(false)
                      ->from(array('i' => 'item'), array('date_creation'))
                      ->join(array('i2f' => 'item2folder'), 'i2f.item_id = i.item_id', array())
                      ->join(array('f' => 'folder'), 'f.folder_id = i2f.folder_id', array())
                      ->join(array('sp' => 'slicerpackages_package'),
                             'sp.item_id = i.item_id', array('package_id'));
    if(!$skipOperatingSystems)
      {
      $subSelect->columns('sp.os');
      }
    if(!$skipArchitecture)
      {
      $subSelect->columns('sp.arch');
      }
    if(count($folderIds) > 0)
      {
      $subSelect->where('f.folder_id IN (?)', $folderIds);
      }
    if(!$skipOperatingSystems and count($operatingSystems) > 0)
      {
      $subSelect->where('sp.os IN (?)', $operatingSystems);
      }
    if(!$skipArchitecture and count($architectures) > 0)
      {
      $subSelect->where('sp.arch IN (?)', $architectures);
      }
    $orderColumns = array();
    if(!$skipOperatingSystems)
      {
      $orderColumns[] = 'sp.os';
      }
    if(!$skipArchitecture)
      {
      $orderColumns[] = 'sp.arch';
      }
    $orderColumns[] = 'i.date_creation DESC';
    $subSelect->order($orderColumns);


    $select = $this->database->select()
                   ->setIntegrityCheck(false)
                   ->from(array('ordered_list'=>$subSelect));
    $groupByColumns = array();
    if(!$skipOperatingSystems)
      {
      $groupByColumns[] = 'ordered_list.os';
      }
    if(!$skipArchitecture)
      {
      $groupByColumns[] = 'ordered_list.arch';
      }
    if(count($groupByColumns) > 0)
      {
      $select->group($groupByColumns);
      }

    return $this->database->fetchAll($select)->toArray();
    }

  function getMostRecentCreatedItem($folderDaos)
    {
    $mostRecentCreatedItems = $this->_getMostRecentCreatedPackages($folderDaos, false, false);
    if(count($mostRecentCreatedItems) > 0)
      {
      return $mostRecentCreatedItems[0];
      }
    else
      {
      return array();
      }
    }

  function getMostRecentCreatedItemsByOs($folderDaos, $operatingSystems = array())
    {
    return $this->_getMostRecentCreatedPackages($folderDaos, $operatingSystems, false);
    }

  function getMostRecentCreatedItemsByOsAndArch($folderDaos, $operatingSystems = array(), $architectures = array())
    {
    return $this->_getMostRecentCreatedPackages($folderDaos, $operatingSystems, $architectures);
    }

  function getReleasedPackages($folderDaos, $releases = array())
    {
    if(!is_array($folderDaos))
      {
      $folderDaos = array($folderDaos);
      }
    if(!is_array($releases))
      {
      $releases = array($releases);
      }
    $select = $this->database->select()
                   ->setIntegrityCheck(false)
                   ->from(array('i' => 'item'), array())
                   ->join(array('i2f' => 'item2folder'), 'i2f.item_id = i.item_id', array())
                   ->join(array('f' => 'folder'), 'f.folder_id = i2f.folder_id', array())
                   ->join(array('sp' => 'slicerpackages_package'), 'sp.item_id = i.item_id');

    if (count($releases) > 0)
      {
      $select->where('sp.release IN (?)', $releases);
      }
    else
      {
      $select->where('sp.release != ""');
      }
    $rowset = $this->database->fetchAll($select);
    $packageDaos = array();
    foreach($rowset as $row)
      {
      $packageDaos[] = $this->initDao('Slicerpackages_Package', $row);
      }
    return $packageDaos;
    }

}  // end class
