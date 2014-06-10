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

require_once BASE_PATH.'/core/models/base/AssetstoreModelBase.php';

/**
 * \class AssetstoreModel
 * \brief Pdo Model
 */
class AssetstoreModel extends AssetstoreModelBase
  {
  /** get All */
  function getAll()
    {
    return $this->database->getAll('Assetstore');
    }

  /**
   * Move all bitstreams from one assetstore to another
   * @param srcAssetstore The source assetstore
   * @param dstAssetstore The destination assetstore
   * @param [progressDao] Progress dao for asynchronous updating
   */
  public function moveBitstreams($srcAssetstore, $dstAssetstore, $progressDao = null)
    {
    $current = 0;
    $progressModel = MidasLoader::loadModel('Progress');
    $bitstreamModel = MidasLoader::loadModel('Bitstream');

    $sql = $this->database->select()->setIntegrityCheck(false)
                ->from('bitstream')
                ->where('assetstore_id = ?', $srcAssetstore->getKey());
    $rows = $this->database->fetchAll($sql);

    $srcPath = $srcAssetstore->getPath();
    $dstPath = $dstAssetstore->getPath();

    foreach($rows as $row)
      {
      $bitstream = $this->initDao('Bitstream', $row);
      if($progressDao)
        {
        $current++;
        $message = $current.' / '.$progressDao->getMaximum().': Moving '.$bitstream->getName().
          ' ('.UtilityComponent::formatSize($bitstream->getSizebytes()).')';
        $progressModel->updateProgress($progressDao, $current, $message);
        }

      // Move the file on disk to its new location
      $dir1 = substr($bitstream->getChecksum(), 0, 2);
      $dir2 = substr($bitstream->getChecksum(), 2, 2);
      if(!is_dir($dstPath.'/'.$dir1))
        {
        if(!mkdir($dstPath.'/'.$dir1))
          {
          throw new Zend_Exception('Failed to mkdir '.$dstPath.'/'.$dir1);
          }
        }
      if(!is_dir($dstPath.'/'.$dir1.'/'.$dir2))
        {
        if(!mkdir($dstPath.'/'.$dir1.'/'.$dir2))
          {
          throw new Zend_Exception('Failed to mkdir '.$dstPath.'/'.$dir1.'/'.$dir2);
          }
        }

      if(is_file($dstPath.'/'.$bitstream->getPath()))
        {
        if(is_file($srcPath.'/'.$bitstream->getPath()))
          {
          unlink($srcPath.'/'.$bitstream->getPath());
          }
        }
      else
        {
        if(!rename($srcPath.'/'.$bitstream->getPath(), $dstPath.'/'.$bitstream->getPath()))
          {
          throw new Zend_Exception('Error moving '.$bitstream->getPath());
          }
        }

      // Update the assetstore id on the bitstream record once it has been moved
      $bitstream->setAssetstoreId($dstAssetstore->getKey());
      $bitstreamModel->save($bitstream);
      }
    }
  } // end class
