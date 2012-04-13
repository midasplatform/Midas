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
/** notification manager*/
class Thumbnailcreator_Notification extends MIDAS_Notification
  {
  public $moduleName = 'thumbnailcreator';
  
  /** init notification process*/
  public function init()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    $this->coreWebroot = $fc->getBaseUrl().'/core';

    $this->addTask('TASK_THUMBNAILCREATOR_CREATE', 'createThumbnail', "Create Thumbnail. Parameters: Item, Revision");
    $this->addEvent('EVENT_CORE_CREATE_THUMBNAIL', 'TASK_THUMBNAILCREATOR_CREATE');

    $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'handleItemDeleted');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_PREPEND_ELEMENTS', 'getItemElement');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getJs');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_CSS', 'getCss');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JSON', 'getJson');
    }//end init
    
  /** createThumbnail */
  public function createThumbnail($params)
    {
    $componentLoader = new MIDAS_ComponentLoader;
    $thumbnailComponent = $componentLoader->loadComponent('Imagemagick', $this->moduleName);
    $thumbnailComponent->createThumbnail($params[0]);
    }

  /** Get javascript for the item view */
  public function getJs($params)
    {
    return array($this->moduleWebroot.'/public/js/item/thumbnailcreator.item.view.js');
    }

  /** Get stylesheets for the item view */
  public function getCss($params)
    {
    return array($this->moduleWebroot.'/public/css/item/thumbnailcreator.item.view.css');
    }

  /** Get Json for the item view */
  public function getJson($params)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $itemthumbnailModel = $modelLoader->loadModel('Itemthumbnail', $this->moduleName);
    $itemthumbnail = $itemthumbnailModel->getByItemId($params['item']->getKey());
    if($itemthumbnail != null)
      {
      return array('itemthumbnail' => $itemthumbnail);
      }
    else
      {
      return array();
      }
    }

  /**
   * When an item is being deleted, we should remove corresponding thumbnails
   */
  public function handleItemDeleted($params)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $itemthumbnailModel = $modelLoader->loadModel('Itemthumbnail', $this->moduleName);
    $itemthumbnail = $itemthumbnailModel->getByItemId($params['item']->getKey());
    if($itemthumbnail && $itemthumbnail->getThumbnailId() !== null)
      {
      $bitstreamModel = $this->ModelLoader->loadModel('Bitstream');
      $thumbnail = $bitstreamModel->load($itemthumbnail->getThumbnailId());
      $bitstreamModel->delete($thumbnail);
      }
    else if($itemthumbnail)
      {
      $itemthumbnailModel->delete($itemthumbnail);
      }
    }

  public function getItemElement($params)
    {
    return array('itemview');
    }
  } //end class
?>
