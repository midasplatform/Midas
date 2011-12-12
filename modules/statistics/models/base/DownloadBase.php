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
class Statistics_DownloadModelBase extends Statistics_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'statistics_download';
    $this->_key = 'job_id';

    $this->_mainData = array(
        'download_id' => array('type' => MIDAS_DATA),
        'item_id' => array('type' => MIDAS_DATA),
        'user_id' => array('type' => MIDAS_DATA),
        'ip' => array('type' => MIDAS_DATA),
        'date' => array('type' => MIDAS_DATA),
        'latitude' => array('type' => MIDAS_DATA),
        'longitude' => array('type' => MIDAS_DATA),
        'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id')
        );
    $this->initialize(); // required
    } // end __construct()


  /** add a download record for the given item */
  public function addDownload($item, $user)
    {
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception('Error: item parameter is not an item dao');
      }
    $this->loadDaoClass('DownloadDao', 'statistics');
    $download = new Statistics_DownloadDao();
    $download->setItemId($item->getKey());
    if($user instanceof UserDao)
      {
      $download->setUserId($user->getKey());
      }
    $ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
      {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
    $download->setIp($ip);
    $download->setDate(date('c'));

    $position = $this->_getGeolocation($ip);
    $longitude = $position['longitude'];
    if($longitude != '')
      {
      $download->setLongitude($longitude);
      }
    $latitude = $position['latitude'];
    if($latitude != '')
      {
      $download->setLatitude($latitude);
      }

    $this->save($download);
    }

  /** Get the geolocation from IP address */
  private function _getGeolocation($ip)
    {
    if(function_exists('curl_init') == false)
      {
      $location['latitude'] = '';
      $location['longitude'] = '';
      return $location;
      }

    $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini', true);
    $apiKey = $applicationConfig['global']['ipinfodb.apikey'];
    $url = 'http://api.ipinfodb.com/v3/ip-city/?key='.$apiKey.'&ip='.$ip.'&format=json';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($curl);

    $result = array('latitude' => '', 'longitude' => '');
    if(!$resp || empty($resp))
      {
      return $result; // Failed to open connection
      }
    $answer = json_decode($resp);
    if($answer->statusCode == 'OK')
      {
      $result['latitude'] = $answer->latitude;
      $result['longitude'] = $answer->longitude;
      }
    return $result;
    }

} // end class
?>
