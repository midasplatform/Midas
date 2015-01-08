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

/** statistics Ip location model base */
abstract class Statistics_IpLocationModelBase extends Statistics_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'statistics_ip_location';
        $this->_key = 'ip_location_id';
        $this->_daoName = 'IpLocationDao';

        $this->_mainData = array(
            'ip_location_id' => array('type' => MIDAS_DATA),
            'ip' => array('type' => MIDAS_DATA),
            'latitude' => array('type' => MIDAS_DATA),
            'longitude' => array('type' => MIDAS_DATA),
            'downloads' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Download',
                'module' => 'statistics',
                'parent_column' => 'ip_location_id',
                'child_column' => 'ip_location_id',
            ),
        );
        $this->initialize(); // required
    }

    /** Get all entries that have not yet been geolocated */
    abstract public function getAllUnlocated();

    /** Get an entry by ip address, or return false if none exists */
    abstract public function getByIp($ip);

    /**
     * Performs the geolocation job on any ip entries that haven't been
     * geolocated yet
     */
    public function performGeolocation($apiKey)
    {
        if (empty($apiKey)) {
            return 'Empty API key. No geolocations performed';
        }

        $log = '';
        $locations = $this->getAllUnlocated();
        foreach ($locations as $location) {
            $location->setLatitude('0');
            $location->setLongitude('0'); // only try geolocation once per ip
            if ($location->getIp()) {
                $url = 'https://api.ipinfodb.com/v3/ip-city/?key='.$apiKey.'&ip='.$location->getIp().'&format=json';

                if (extension_loaded('curl')) {
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_PORT, 443);
                    $response = curl_exec($curl);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                    if ($status != 200) {
                        $response = false;
                    }
                } else {
                    $response = file_get_contents($url, false);
                }

                if ($status !== false) {
                    $answer = json_decode($response);
                    if ($answer && strtoupper($answer->statusCode) == 'OK') {
                        $location->setLatitude($answer->latitude);
                        $location->setLongitude($answer->longitude);
                    } else {
                        $this->save($location);
                        $log .= 'IpInfoDb lookup failed for ip '.$location->getIp().' (id='.$location->getKey().")\n";
                        continue;
                    }
                } else {
                    $this->save($location);
                    $log .= 'IpInfoDb lookup failed (empty response) for ip '.$location->getIp(
                        ).' (id='.$location->getKey().")\n";
                    continue;
                }
            }
            $this->save($location);
        }

        return $log;
    }
}
