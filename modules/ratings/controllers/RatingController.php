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

/** Controller for setting and getting ratings */
class Ratings_RatingController extends Ratings_AppController
{
    public $_models = array('Item');

    /**
     * Set a rating on an item
     *
     * @param itemId The item id to set the rating on
     * @param rating The rating (0-5) to set for the currently logged user. 0 means remove user's rating.
     * @throws Zend_Exception
     */
    public function rateitemAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('Must be logged in to rate an item');
        }

        $itemId = $this->getParam('itemId');
        if (!isset($itemId) || !$itemId) {
            throw new Zend_Exception('Must set itemId parameter');
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Not a valid itemId');
        }
        $rating = (int) $this->getParam('rating');
        if ($rating < 0 || $rating > 5) {
            throw new Zend_Exception('Rating must be 0-5');
        }

        $this->disableView();
        $this->disableLayout();

        /** @var Ratings_ItemratingModel $itemRatingModel */
        $itemRatingModel = MidasLoader::loadModel('Itemrating', $this->moduleName);
        $itemRatingModel->setRating($this->userSession->Dao, $item, $rating);

        $info = $itemRatingModel->getAggregateInfo($item);
        $message = $rating == 0 ? 'Rating removed' : 'Rating saved';
        echo JsonComponent::encode(array_merge(array('status' => 'ok', 'message' => $message), $info));
    }
}
