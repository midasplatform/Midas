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
    $item = $this->Item->load($this->_getParam('id'));
    if(!$item)
      {
      throw new Zend_Exception("Item doesn't exist");
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('You do not have read permission on this item');
      }

    $header = '<img style="position: relative; top: 3px; margin-left: -10px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_bar.png" />';
    $header .= ' Statistics: ';
    $header .= '<a href="'.$this->view->webroot.'/item/'.$item->getKey().'">'.$item->getName().'</a> ';
    $header .= '<span class="headerSmall">['.$item->getDownload().' downloads, '.$item->getView().' views]</span>';
    $this->view->header = $header;
    $downloads = $this->Statistics_Download->getDownloads($item, date('c', strtotime('-20 day'.date( 'Y-m-j G:i:s'))), date('c'));

    $format = 'Y-m-j';
    $arrayDownload = array();
    for($i = 0; $i < 21; $i++)
      {
      $key = date($format, strtotime(date('c', strtotime('-'.$i.' day'.date( 'Y-m-j G:i:s')))));
      $arrayDownload[$key] = 0;
      }
    foreach($downloads as $download)
      {
      $key = date($format, strtotime($download->getDate()));
      $arrayDownload[$key]++;
      }

    $jqplotArray = array();
    foreach($arrayDownload as $key => $value)
      {
      $jqplotArray[] = array($key.' 8:00AM', $value);
      }
    $this->view->json['stats']['downloads'] = $jqplotArray;
    $this->view->itemDao = $item;
    $this->view->json['itemId'] = $item->getKey();
    $this->view->json['initialStartDate'] = date('m/d/Y', strtotime('-1 month'));
    $this->view->json['initialEndDate'] = date('m/d/Y');
    }

  /**
   * Outputs a json object of and item's download history filtered by the following parameters.
   * @param itemId The id of the item (key)
   * @param startdate Start of the date range (date string)
   * @param enddate End of the date range (date string)
   * @param limit Limit of the result count (integer)
   * @return An array with a "download" key, which is a list of tuples with the following data:
   *   -latitude
   *   -longitude
   *   -date
   */
  public function filterAction()
    {
    $this->disableLayout();
    $this->disableView();

    $item = $this->Item->load($this->_getParam('itemId'));
    if($this->_getParam('startdate') == '')
      {
      $startDate = date('Y-m-d');
      }
    else
      {
      $startDate = date('Y-m-d', strtotime($this->_getParam('startdate')));
      }
    if($this->_getParam('enddate') == '')
      {
      $endDate = date('Y-m-d');
      }
    else
      {
      $endDate = date('Y-m-d', strtotime($this->_getParam('enddate')));
      }
    $limit = $this->_getParam('limit');

    if(!isset($limit) || $limit < 0)
      {
      $limit = 1000;
      }
    if(!$item)
      {
      throw new Zend_Exception("Item doesn't exist");
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('You do not have read permission on this item');
      }

    $downloads = $this->Statistics_Download->getLocatedDownloads($item, $startDate, $endDate, $limit);

    $markers = array();
    foreach($downloads as $download)
      {
      $date = date('Y-m-d G:i:s', strtotime($download->getDate()));
      $latitude = $download->getIpLocation()->getLatitude();
      $longitude = $download->getIpLocation()->getLongitude();

      if($latitude || $longitude)
        {
        $markers[] = array('latitude' => $latitude,
                           'longitude' => $longitude,
                           'date' => $date);
        }
      }
    echo JsonComponent::encode(array('downloads' => $markers));
    }

}//end class
