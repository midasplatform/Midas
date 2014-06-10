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

/** Active Download Model Base*/
abstract class ActivedownloadModelBase extends AppModel
  {
  /** Constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'activedownload';
    $this->_key = 'activedownload_id';

    $this->_mainData = array(
      'activedownload_id' =>  array('type' => MIDAS_DATA),
      'ip' =>  array('type' => MIDAS_DATA),
      'date_creation' =>  array('type' => MIDAS_DATA),
      'last_update' =>  array('type' => MIDAS_DATA)
      );
    $this->initialize();
    }

  /** Check for an active download by ip address */
  public abstract function getByIp($ip);

  /**
   * Call this function to acquire the download lock.
   * This will return false if an active download already exists for
   * this ip address, otherwise it will return the active download
   * lock that was created.
   */
  public function acquireLock()
    {
    $ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
      {
      $ip .= '_'.$_SERVER['HTTP_X_FORWARDED_FOR'];
      }

    $oldLock = $this->getByIp($ip);
    if($oldLock !== false)
      {
      // If an old lock exists but has not been updated in more than 5 minutes, we
      // should release the old lock and create a new one.
      $lastUpdate = strtotime($oldLock->getLastUpdate());
      if(time() > $lastUpdate + 300)
        {
        $this->delete($oldLock);
        }
      else
        {
        return false;
        }
      }

    $activeDownload = MidasLoader::newDao('ActivedownloadDao');
    $activeDownload->setDateCreation(date("Y-m-d H:i:s"));
    $activeDownload->setLastUpdate(date("Y-m-d H:i:s"));
    $activeDownload->setIp($ip);
    $this->save($activeDownload);

    return $activeDownload;
    }

  /**
   * Call this to update an active download lock.  If a lock has not been updated
   * in more than 5 minutes, it is considered orphaned and will be removed.
   * @param lockDao The active download lock to update
   */
  public function updateLock($lockDao)
    {
    $lockDao->setLastUpdate(date("Y-m-d H:i:s"));
    $this->save($lockDao);
    }
  }
