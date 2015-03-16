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

/** Item rating base model for the ratings module */
abstract class Ratings_ItemratingModelBase extends Ratings_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'ratings_item';
        $this->_daoName = 'ItemratingDao';
        $this->_key = 'rating_id';

        $this->_mainData = array(
            'rating_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'item_id' => array('type' => MIDAS_DATA),
            'rating' => array('type' => MIDAS_DATA),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
            'item' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Item',
                'parent_column' => 'item_id',
                'child_column' => 'item_id',
            ),
        );
        $this->initialize();
    }

    /** Set the rating on an item for a user (overwrites if already exists) */
    abstract public function setRating($user, $item, $rating);

    /** Get the average rating and total rating count on an item */
    abstract public function getAggregateInfo($item);

    /** Get rating by user, or return 0 if none exists for that user */
    abstract public function getByUser($user, $item);

    /** Delete all comments made by the user. Called when user is about to be deleted. */
    abstract public function deleteByUser($user);

    /** Delete all comments on a given item. Called when item is about to be deleted. */
    abstract public function deleteByItem($item);
}
