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

require_once BASE_PATH.'/modules/comments/models/base/ItemcommentModelBase.php';

/** Item comment model implementation */
class Comments_ItemcommentModel extends Comments_ItemcommentModelBase
{
    /**
     * Get (paginated) comments on an item.
     */
    public function getComments($item, $limit = 10, $offset = 0)
    {
        /**
         * Change the order of the retrieval based upon the user setting.
         * This changes the display order of the comments.
         * 'ASC' shows the oldest comment first, 'DESC' shows the newest first.
         */
        if (MidasLoader::loadModel('Setting')->getValueByName('sortSelect', 'comments') == COMMENTS_OLDEST_FIRST) {
            $commentSort = 'ASC';
        } else {
            $commentSort = 'DESC';
        }
        $sql = $this->database->select()->where('item_id = ?', $item->getKey())->limit($limit, $offset)->order(
            'date '.$commentSort
        );

        $rowset = $this->database->fetchAll($sql);
        $comments = array();
        foreach ($rowset as $row) {
            $comments[] = $this->initDao('Itemcomment', $row, 'comments');
        }

        return $comments;
    }

    /**
     * Delete all comments made by the user. Called when user is about to be deleted.
     */
    public function deleteByUser($user)
    {
        Zend_Registry::get('dbAdapter')->delete($this->_name, 'user_id = '.$user->getKey());
    }

    /**
     * Delete all comments on a given item. Called when item is about to be deleted.
     */
    public function deleteByItem($item)
    {
        Zend_Registry::get('dbAdapter')->delete($this->_name, 'item_id = '.$item->getKey());
    }

    /**
     * Get the total number of comments on the item.
     */
    public function getTotal($item)
    {
        $sql = $this->database->select()->from(array($this->_name), array('count' => 'count(*)'))->where(
            'item_id = ?',
            $item->getKey()
        );

        $row = $this->database->fetchRow($sql);

        return $row['count'];
    }
}
