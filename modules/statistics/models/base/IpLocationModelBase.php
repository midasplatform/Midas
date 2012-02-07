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
        'downloads' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Download', 'module' => 'statistics', 'parent_column' => 'ip_location_id', 'child_column' => 'ip_location_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  /** Get all entries that have not yet been geolocated */
  abstract function getAllUnlocated();
  /** Get an entry by ip address, or return false if none exists */
  abstract function getByIp($ip);

  /**
   * Performs the geolocation job on any ip entries that haven't been
   * geolocated yet
   */
  public function performGeolocation($apiKey)
    {
    if(function_exists('curl_init') == false)
      {
      return;
      }

    if(empty($apiKey))
      {
      return;
      }

    $locations = $this->getAllUnlocated();
    foreach($locations as $location)
      {
      $location->setLatitude('0');
      $location->setLongitude('0'); //only try geolocation once per ip
      if($location->getIp())
        {
        $url = 'http://api.ipinfodb.com/v3/ip-city/?key='.$apiKey.'&ip='.$location->getIp().'&format=json';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curl);

        if($resp && !empty($resp))
          {
          $answer = json_decode($resp);
          if($answer && strtoupper($answer->statusCode) == 'OK')
            {
            $location->setLatitude($answer->latitude);
            $location->setLongitude($answer->longitude);
            }
          else
            {
            $this->save($location);
            continue;
            }
          }
        else
          {
          $this->save($location);
          continue;
          }
        }
      $this->save($location);
      }
    }

} // end class
?>
