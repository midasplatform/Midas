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
abstract class Comments_ItemcommentModelBase extends Comments_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'comments_item';
    $this->_daoName = 'ItemcommentDao';
    $this->_key = 'comment_id';

    $this->_mainData = array(
      'comment_id' => array('type' => MIDAS_DATA),
      'user_id' => array('type' => MIDAS_DATA),
      'item_id' => array('type' => MIDAS_DATA),
      'comment' => array('type' => MIDAS_DATA),
      'date' => array('type' => MIDAS_DATA),
      'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id')
      );
    $this->initialize();
    }

  /** Get all the comments for an item */
  abstract public function getComments($item, $limit = 10, $offset = 0);
  /** Delete all comments made by the user */
  abstract public function deleteByUser($user);
  /** Delete all comments on the given item */
  abstract public function deleteByItem($item);
  

  /** Add a comment to an item */
  public function addComment($user, $item, $comment)
    {
    $this->loadDaoClass('ItemcommentDao', 'comments');
    $commentDao = new Comments_ItemcommentDao();
    $commentDao->setUserId($user->getKey());
    $commentDao->setItemId($item->getKey());
    $commentDao->setComment($comment);
    $this->save($commentDao);

    Zend_Registry::get('notifier')->callback('CALLBACK_COMMENTS_ADDED_COMMENT', array('comment' => $commentDao));
    }
}
?>
