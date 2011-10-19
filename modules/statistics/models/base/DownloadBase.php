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

class Statistics_DownloadModelBase extends Statistics_AppModel
{
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'statistics_download';
    $this->_key = 'job_id';

    $this->_mainData= array(
        'download_id'=>  array('type'=>MIDAS_DATA),
        'item_id'=>  array('type'=>MIDAS_DATA),
        'user_id'=>  array('type'=>MIDAS_DATA),
        'ip'=>  array('type'=>MIDAS_DATA),
        'date'=>  array('type'=>MIDAS_DATA),
        'latitude'=>  array('type'=>MIDAS_DATA),
        'longitude'=>  array('type'=>MIDAS_DATA),
        'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id')
        );
    $this->initialize(); // required
    } // end __construct()


  /** add a download */
  public function addDownload($item, $user)
    {
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Error Params");
      }
    $this->loadDaoClass('DownloadDao', 'statistics');
    $download = new Statistics_DownloadDao();
    $download->setItemId($item->getKey());
    if($user instanceof UserDao)
      {
      $download->setUserId($user->getKey());
      }
    $ip = $_SERVER['REMOTE_ADDR'];
    if( isset ( $_SERVER['HTTP_X_FORWARDED_FOR']))
      {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
    $download->setIp($ip);
    $download->setDate(date('c'));
    
    $position = $this->_getGeolocation( $ip);
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
  private function _getGeolocation( $ip)
    {
    if(function_exists( "curl_init") == FALSE)
       {
       $location['latitude'] = "";
       $location['longitude'] = "";
       return $location;
       }
       
    $curl = curl_init();
    // This url should not be hardcoded. It should be in a config file TODO FIXME HACK
    curl_setopt( $curl, CURLOPT_URL, "http://www.ipinfodb.com/ip_query.php?ip=$ip&output=xml");
    ob_start();
    curl_exec( $curl);
    $d = ob_get_contents();
    ob_end_clean();
    $result=array('latitude'=>'','longitude'=>'');
    if (!$d||empty( $d))        return $result; // Failed to open connection
    $answer = new SimpleXMLElement( $d);
    if ( $answer->Status != 'OK')  return $result; // Invalid status code
  
    $result['latitude']=$latitude = $answer->Latitude;
    $result['longitude']=$latitude = $answer->Longitude;
    return $result;
    }
} // end class AssetstoreModelBase
?>