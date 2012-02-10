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
require_once BASE_PATH.'/modules/ratings/models/base/ItemratingModelBase.php';

/** Item rating model implementation */
class Ratings_ItemratingModel extends Ratings_ItemratingModelBase
{
  /**
   * Set the rating on an item for a user (overwrites if already exists)
   * If a 0 rating is passed, any existing rating will be removed
   */
  function setRating($user, $item, $rating)
    {
    $row = $this->database->fetchRow($this->database->select()->where('item_id = ?', $item->getKey())
                                                              ->where('user_id = ?', $user->getKey()));
    $dao = $this->initDao('Itemrating', $row, 'ratings');
    if($dao == null)
      {
      if($rating == 0)
        {
        return; //no need to save this rating at all
        }
      $this->loadDaoClass('ItemratingDao', 'ratings');
      $dao = new Ratings_ItemratingDao();
      $dao->setUserId($user->getKey());
      $dao->setItemId($item->getKey());
      }
    else if($rating == 0)
      {
      $this->delete($dao);
      return;
      }
    $dao->setRating($rating);
    $this->save($dao);
    }

  /**
   * Get the average rating and total rating count on an item.
   * @return array('total' => total number of ratings, 'average' => average rating)
   */
  function getAggregateInfo($item)
    {
    $sql = $this->database->select()
                ->from(array('i' => 'ratings_item'), array('count(*)', 'avg(rating)'))
                ->where('item_id = ?', $item->getKey());
    $row = $this->database->fetchRow($sql);
    $info = array();
    if(isset($row['count(*)'])) //mysql
      {
      $info['total'] = $row['count(*)'];
      }
    else if(isset($row['count'])) //pgsql
      {
      $info['total'] = $row['count'];
      }
    if(isset($row['avg(rating)'])) //mysql
      {
      $info['average'] = $row['avg(rating)'];
      }
    else if(isset($row['avg'])) //pgsql
      {
      $info['average'] = $row['avg'];
      }
    return $info;
    }

  /**
   * Return the user's rating for the item (1-5), or 0 if none exists
   */
  function getByUser($user, $item)
    {
    $sql = $this->database->select()
                ->from(array('i' => 'ratings_item'), array('rating'))
                ->where('item_id = ?', $item->getKey())
                ->where('user_id = ?', $user->getKey());
    $row = $this->database->fetchRow($sql);
    if(!$row || !isset($row['rating']))
      {
      return 0;
      }
    else
      {
      return $row['rating'];
      }
    }
}
?>
