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

/** Controller for statistics about an item */
class Statistics_ItemController extends Statistics_AppController
{
  public $_moduleModels = array('Download');
  public $_models = array('Item');
  public $_components = array('Utility');

  /** index action*/
  function indexAction()
    {
    $item = $this->Item->load($_GET['id']);
    if(!$item)
      {
      throw new Zend_Exception("Item doesn't exist");
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('You do not have read permission on this item');
      }

    //TODO instead of last 21 days, have that number be an adjustable parameter to the controller
    $downloads = $this->Statistics_Download->getDownloads($item, date('c', strtotime('-20 day'.date( 'Y-m-j G:i:s'))), date('c'));

    $arrayDownload = array();
    $format = 'Y-m-j';
    $markers = array();
    for($i = 0; $i < 21; $i++)
      {
      $key = date($format, strtotime(date('c', strtotime('-'.$i.' day'.date( 'Y-m-j G:i:s')))));
      $arrayDownload[$key] = 0;
      }
    foreach($downloads as $download)
      {
      $key = date($format, strtotime($download->getDate()));
      $arrayDownload[$key]++;
      $latitude = $download->getLatitude();
      $longitude = $download->getLongitude();

      if($latitude || $longitude)
        {
        $markers[] = array('latitude' => $latitude,
                           'longitude' => $longitude,
                           'date' => $key);
        }
      }

    $jqplotArray = array();
    foreach($arrayDownload as $key => $value)
      {
      $jqplotArray[] = array($key.' 8:00AM', $value);
      }
    $this->view->json['stats']['downloads'] = $jqplotArray;
    $this->view->markers = $markers;
    $this->view->itemDao = $item;
    }

}//end class
