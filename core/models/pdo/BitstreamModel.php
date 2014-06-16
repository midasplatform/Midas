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

require_once BASE_PATH.'/core/models/base/BitstreamModelBase.php';

/**
 * \class BitstreamModel
 * \brief Pdo Model
 */
class BitstreamModel extends BitstreamModelBase
  {

  /** Get bitstream by checksum */
  function getByChecksum($checksum, $getAll = false)
    {
    $sql = $this->database->select()
                ->setIntegrityCheck(false)
                ->where('checksum = ?', $checksum);
    if($getAll)
      {
      $results = array();
      $rowset = $this->database->fetchAll($sql);
      foreach($rowset as $row)
        {
        $results[] = $this->initDao(ucfirst($this->_name), $row);
        }
      return $results;
      }
    else
      {
      $row = $this->database->fetchRow($sql);
      $dao = $this->initDao(ucfirst($this->_name), $row);
      return $dao;
      }
    }

  /**
   * Used by the admin dashboard page. Counts the number of orphaned bitstream
   * records in the database.
   */
  function countOrphans()
    {
    $sql = $this->database->select()->setIntegrityCheck(false)
                   ->from(array('b' => 'bitstream'), array('count' => 'count(*)'))
                   ->where('b.itemrevision_id > ?', 0)
                   ->where('(NOT b.itemrevision_id IN ('.new Zend_Db_Expr(
                            $this->database->select()->setIntegrityCheck(false)
                                 ->from(array('subr' => 'itemrevision'), array('itemrevision_id'))
                           .'))'));
    $row = $this->database->fetchRow($sql);
    return $row['count'];
    }

  /**
   * Count the total number of bitstreams in the system. Provide an assetstore
   * dao if you wish to count the number in a single assetstore.
   */
  function countAll($assetstoreDao = null)
    {
    $sql = $this->database->select()->setIntegrityCheck(false)
                ->from('bitstream', array('count' => 'count(*)'));
    if($assetstoreDao)
      {
      $sql->where('assetstore_id = ?', $assetstoreDao->getKey());
      }
    $row = $this->database->fetchRow($sql);
    return $row['count'];
    }

  /**
   * Call this to remove all orphaned item revision records
   */
  function removeOrphans($progressDao = null)
    {
    if($progressDao)
      {
      $max = $this->countOrphans();
      $progressDao->setMaximum($max);
      $progressDao->setMessage('Removing orphaned bitstreams (0/'.$max.')');
      $this->Progress = MidasLoader::loadModel('Progress');
      $this->Progress->save($progressDao);
      }

    $sql = $this->database->select()->setIntegrityCheck(false)
                   ->from(array('b' => 'bitstream'), array('bitstream_id'))
                   ->where('b.itemrevision_id > ?', 0)
                   ->where('(NOT b.itemrevision_id IN ('.new Zend_Db_Expr(
                            $this->database->select()->setIntegrityCheck(false)
                                 ->from(array('subr' => 'itemrevision'), array('itemrevision_id'))
                           .'))'));
    $rowset = $this->database->fetchAll($sql);
    $ids = array();
    foreach($rowset as $row)
      {
      $ids[] = $row['bitstream_id'];
      }
    $itr = 0;
    foreach($ids as $id)
      {
      if($progressDao)
        {
        $itr++;
        $message = 'Removing orphaned bitstreams ('.$itr.'/'.$max.')';
        $this->Progress->updateProgress($progressDao, $itr, $message);
        }
      $bitstream = $this->load($id);
      if(!$bitstream)
        {
        continue;
        }
      $this->getLogger()->info('Deleting orphaned bitstream '.$bitstream->getName(). ' [revision id='.$bitstream->getItemrevisionId().']');
      $this->delete($bitstream);
      }
    }
  }
