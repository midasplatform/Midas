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

/** Notification manager for the ratings module */
class Ratings_Notification extends MIDAS_Notification
{
    public $moduleName = 'ratings';
    public $moduleWebroot = '';

    /** init notification process */
    public function init()
    {
        $fc = Zend_Controller_Front::getInstance();
        $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
        $this->coreWebroot = $fc->getBaseUrl().'/core';

        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getJs');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_CSS', 'getCss');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JSON', 'getJson');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_INFO', 'getItemInfo');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_APPEND_ELEMENTS', 'getElement');
        $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
        $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'handleItemDeleted');
    }

    /** Some html to be appended to the item view sidebar */
    public function getItemInfo($params)
    {
        return '<div class="sideElement" id="sideElementRatings">
              <h1>Rating Distribution</h1>
            </div>';
    }

    /** Get javascript for the ratings */
    public function getJs($params)
    {
        return array(
            $this->moduleWebroot.'/public/js/star_rating/jquery.ui.stars.min.js',
            $this->moduleWebroot.'/public/js/item/item.ratings.js',
            $this->moduleWebroot.'/public/js/common/common.ratings.js',
            $this->coreWebroot.'/public/js/jquery/jquery.jqplot.min.js',
            $this->coreWebroot.'/public/js/jquery/jqplot/jqplot.barRenderer.min.js',
            $this->coreWebroot.'/public/js/jquery/jqplot/jqplot.categoryAxisRenderer.min.js',
            $this->coreWebroot.'/public/js/jquery/jqplot/jqplot.pointLabels.min.js',
        );
    }

    /** Get stylesheets for the ratings */
    public function getCss($params)
    {
        return array(
            $this->moduleWebroot.'/public/css/star_rating/jquery.ui.stars.css',
            $this->moduleWebroot.'/public/css/item/item.ratings.css',
            $this->coreWebroot.'/public/css/jquery/jquery.jqplot.min.css',
        );
    }

    /** Get the element to render at the bottom of the item view */
    public function getElement($params)
    {
        return array('rating');
    }

    /** Get json to pass to the view */
    public function getJson($params)
    {
        /** @var Ratings_ItemratingModel $itemRatingModel */
        $itemRatingModel = MidasLoader::loadModel('Itemrating', $this->moduleName);
        $data = $itemRatingModel->getAggregateInfo($params['item']);
        if ($this->userSession->Dao) {
            $data['userRating'] = $itemRatingModel->getByUser($this->userSession->Dao, $params['item']);
        }

        return $data;
    }

    /**
     * When a user is getting deleted, we should delete their comments
     */
    public function handleUserDeleted($params)
    {
        /** @var Ratings_ItemratingModel $itemRatingModel */
        $itemRatingModel = MidasLoader::loadModel('Itemrating', $this->moduleName);
        $itemRatingModel->deleteByUser($params['userDao']);
    }

    /**
     * When an item is getting deleted, we should delete associated comments
     */
    public function handleItemDeleted($params)
    {
        /** @var Ratings_ItemratingModel $itemRatingModel */
        $itemRatingModel = MidasLoader::loadModel('Itemrating', $this->moduleName);
        $itemRatingModel->deleteByItem($params['item']);
    }
}
