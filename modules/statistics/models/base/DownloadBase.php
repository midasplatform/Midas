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
        'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
        'ip_location' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'IpLocation', 'module' => 'statistics', 'parent_column' => 'ip_location_id', 'child_column' => 'ip_location_id')
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

    $ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
      {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }

    $modelLoader = new MIDAS_ModelLoader();
    $ipLocationModel = $modelLoader->loadModel('IpLocation', 'statistics');
    $ipLocation = $ipLocationModel->getByIp($ip);

    if($ipLocation == false)
      {
      $this->loadDaoClass('IpLocationDao', 'statistics');
      $ipLocation = new Statistics_IpLocationDao();
      $ipLocation->setIp($ip);
      // we will perform the geolocation later, since it can be slow
      $ipLocation->setLatitude('');
      $ipLocation->setLongitude('');
      $ipLocationModel->save($ipLocation);
      }

    $this->loadDaoClass('DownloadDao', 'statistics');
    $download = new Statistics_DownloadDao();
    $download->setItemId($item->getKey());
    $download->setIpLocationId($ipLocation->getKey());
    $download->setDate(date('c'));
    if($user instanceof UserDao)
      {
      $download->setUserId($user->getKey());
      }

    $this->save($download);
    }

} // end class
?>
