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

/** Download model base */
abstract class Statistics_DownloadModelBase extends Statistics_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'statistics_download';
        $this->_key = 'download_id';

        $this->_mainData = array(
            'download_id' => array('type' => MIDAS_DATA),
            'item_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'ip_location_id' => array('type' => MIDAS_DATA),
            'date' => array('type' => MIDAS_DATA),
            'user_agent' => array('type' => MIDAS_DATA),
            'item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'item_id',
                'child_column' => 'item_id',
            ),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
            'ip_location' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'IpLocation',
                'module' => 'statistics',
                'parent_column' => 'ip_location_id',
                'child_column' => 'ip_location_id',
            ),
        );
        $this->initialize(); // required
    }

    /** Remove user references */
    abstract public function removeUserReferences($userId);

    /** add a download record for the given item */
    public function addDownload($item, $user)
    {
        if (!$item instanceof ItemDao) {
            throw new Zend_Exception('Error: item parameter is not an item dao');
        }

        $userAgent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $ipLocationModel = MidasLoader::loadModel('IpLocation', 'statistics');
        $ipLocation = $ipLocationModel->getByIp($ip);

        if ($ipLocation == false) {
            $ipLocation = MidasLoader::newDao('IpLocationDao', 'statistics');
            $ipLocation->setIp($ip);
            // we will perform the geolocation later, since it can be slow
            $ipLocation->setLatitude('');
            $ipLocation->setLongitude('');
            $ipLocationModel->save($ipLocation);
        }

        $download = MidasLoader::newDao('DownloadDao', 'statistics');
        $download->setItemId($item->getKey());
        $download->setIpLocationId($ipLocation->getKey());
        $download->setDate(date('Y-m-d H:i:s'));
        $download->setUserAgent($userAgent);
        if ($user instanceof UserDao) {
            $download->setUserId($user->getKey());
        }

        $this->save($download);
    }
}
