<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

    /**
     * Render the statistics view for a set of items
     *
     * @param [startDate] The start of the date range (inclusive, default = 1 month ago)
     * @param [endDate] The end of the date range (inclusive, default = today)
     * @param [limit] Result limit for the map (default = 1000)
     * @throws Zend_Exception
     */
    public function indexAction()
    {
        $itemIds = $this->getParam('id');
        $ids = explode(',', $itemIds);
        $count = 0;
        $totalview = 0;
        $totaldownload = 0;
        $idArray = array();
        foreach ($ids as $id) {
            if ($id != '') {
                $item = $this->Item->load($id);
                if (!$item) {
                    throw new Zend_Exception("Item ".$id." doesn't exist", 404);
                }
                if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('You do not have read permission on item '.$id);
                }
                $count++;
                $totalview += $item->getView();
                $totaldownload += $item->getDownload();
                $idArray[] = $item->getKey();
            }
        }

        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/chart_bar.png" />';
        $header .= ' Statistics: ';
        if ($count == 1) {
            $header .= '<a href="'.$this->view->webroot.'/item/'.$item->getKey().'">'.$item->getName().'</a> ';
        } else {
            $header .= $count.' items ';
        }
        $header .= '<span class="headerSmall">['.$totaldownload.' downloads, '.$totalview.' views]</span>';
        $this->view->header = $header;
        $arrayDownload = $this->Statistics_Download->getDailyCounts(
            $idArray,
            date('Y-m-d H:i:s', strtotime('-20 day'.date('Y-m-d H:i:s'))),
            date('Y-m-d H:i:s')
        );
        for ($i = 20; $i >= 0; $i--) {
            $dateKey = date('Y-m-d', strtotime('-'.$i.' day'));
            if (!array_key_exists($dateKey, $arrayDownload)) {
                $arrayDownload[$dateKey] = 0;
            }
        }

        $limit = $this->getParam('limit');
        if (isset($limit) && is_numeric($limit) && $limit > 0) {
            $limit = (int) $limit;
        } else {
            $limit = 1000;
        }

        $startDate = $this->getParam('startDate');
        if (!isset($startDate)) {
            $startDate = date('m/d/Y', strtotime('-1 month'));
        }
        $endDate = $this->getParam('endDate');
        if (!isset($endDate)) {
            $endDate = date('m/d/Y');
        }

        foreach ($arrayDownload as $key => $value) {
            $jqplotArray[] = array($key.' 8:00AM', $value);
        }
        $this->view->json['stats']['downloads'] = $jqplotArray;
        $this->view->itemIds = $itemIds;
        $this->view->json['itemId'] = $itemIds;
        $this->view->json['initialStartDate'] = $startDate;
        $this->view->json['initialEndDate'] = $endDate;
        $this->view->json['limit'] = $limit;
    }

    /**
     * Outputs a json object of and item's download history filtered by the following parameters.
     *
     * @param itemId The id of the item (key)
     * @param startdate Start of the date range (date string)
     * @param enddate End of the date range (date string)
     * @param limit Limit of the result count (integer)
     * @return An array with a "download" key, which is a list of tuples with the following data:
     *            -latitude
     *            -longitude
     *            -date
     * @throws Zend_Exception
     */
    public function filterAction()
    {
        $this->disableLayout();
        $this->disableView();

        $itemIds = $this->getParam('itemId');
        $ids = explode(',', $itemIds);
        $idArray = array();
        foreach ($ids as $id) {
            if ($id != '') {
                $item = $this->Item->load($id);
                if (!$item) {
                    throw new Zend_Exception("Item ".$id." doesn't exist");
                }
                if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('You do not have read permission on item '.$id);
                }
                $idArray[] = $item->getKey();
            }
        }
        if ($this->getParam('startdate') == '') {
            $startDate = date('Y-m-d');
        } else {
            $startDate = date('Y-m-d', strtotime($this->getParam('startdate')));
        }
        if ($this->getParam('enddate') == '') {
            $endDate = date('Y-m-d  23:59:59');
        } else {
            $endDate = date('Y-m-d 23:59:59', strtotime($this->getParam('enddate')));
        }
        $limit = $this->getParam('limit');

        if (!isset($limit) || $limit < 0) {
            $limit = 1000;
        }

        $downloads = $this->Statistics_Download->getLocatedDownloads($idArray, $startDate, $endDate, $limit);
        $totalCount = $this->Statistics_Download->getCountInRange($idArray, $startDate, $endDate, $limit);

        $markers = array();
        foreach ($downloads as $download) {
            $latitude = $download->getIpLocation()->getLatitude();
            $longitude = $download->getIpLocation()->getLongitude();

            if ($latitude || $longitude) {
                $markers[] = array('latitude' => $latitude, 'longitude' => $longitude);
            }
        }
        echo JsonComponent::encode(array('downloads' => $markers, 'count' => $totalCount));
    }
}
