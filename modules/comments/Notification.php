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

/** Notification manager for the comments module */
class Comments_Notification extends MIDAS_Notification
{
    public $moduleName = 'comments';
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
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_APPEND_ELEMENTS', 'getElement');
        $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
        $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'handleItemDeleted');
    }

    /** Get javascript for the comments */
    public function getJs($params)
    {
        return array(
            $this->moduleWebroot.'/public/js/item/item.comments.js',
            $this->coreWebroot.'/public/js/jquery/jquery.autogrow-textarea.js',
        );
    }

    /** Get stylesheets for the comments */
    public function getCss($params)
    {
        return array($this->moduleWebroot.'/public/css/item/item.comments.css');
    }

    /** Get the element to render at the bottom of the item view */
    public function getElement($params)
    {
        return array('comment');
    }

    /** Get json to pass to the view initially */
    public function getJson($params)
    {
        $json = array('limit' => 10, 'offset' => 0);
        if ($this->userSession->Dao != null) {
            $json['user'] = $this->userSession->Dao;
        }
        $commentComponent = MidasLoader::loadComponent('Comment', $this->moduleName);
        list($comments, $total) = $commentComponent->getComments($params['item'], $json['limit'], $json['offset']);
        $json['comments'] = $comments;
        $json['total'] = $total;

        return $json;
    }

    /**
     * When a user is getting deleted, we should delete their comments
     */
    public function handleUserDeleted($params)
    {
        $itemCommentModel = MidasLoader::loadModel('Itemcomment', $this->moduleName);
        $itemCommentModel->deleteByUser($params['userDao']);
    }

    /**
     * When an item is getting deleted, we should delete associated comments
     */
    public function handleItemDeleted($params)
    {
        $itemCommentModel = MidasLoader::loadModel('Itemcomment', $this->moduleName);
        $itemCommentModel->deleteByItem($params['item']);
    }
}
