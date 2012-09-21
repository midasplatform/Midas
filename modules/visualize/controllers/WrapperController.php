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

/** Wrapper controller*/
class Visualize_WrapperController extends Visualize_AppController
{
  public $_moduleComponents = array('Main');
  public $_models = array('Item', 'Folder');
  public $_components = array('Date', 'Utility', 'Sortdao');

  /** indexAction*/
  function indexAction()
    {
    $this->view->header = $this->t("Preview");
    $this->view->Date = $this->Component->Date;
    $this->view->Utility = $this->Component->Utility;
    $itemId = $this->_getParam("itemId");
    $viewMode = $this->_getParam('viewMode');
    if(!isset($viewMode))
      {
      $viewMode = 'volume';
      }
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }
    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao))
      {
      throw new Zend_Exception("Problem policies.");
      }

    $itemRevision = $this->Item->getLastRevision($itemDao);
    $itemDao->lastrevision = $itemRevision;

    $this->view->itemDao = $itemDao;

    $this->view->itemSize = $this->Component->Utility->formatSize($itemDao->getSizebytes());

    $this->view->title .= ' - '.$itemDao->getName();
    $this->view->metaDescription = substr($itemDao->getDescription(), 0, 160);
    $this->view->viewMode = $viewMode;

    $tmp = Zend_Registry::get('notifier')->callback("CALLBACK_VISUALIZE_CAN_VISUALIZE", array('item' => $itemDao));
    if(isset($tmp['visualize']) && $tmp['visualize'] == true)
      {
      $this->view->preview = true;
      }
    else
      {
      throw new Zend_Exception("Unable to preview this item.");
      }

    $items = array();
    $this->view->currentFolder = false;

    $parents = $itemDao->getFolders();
    if(count($parents) == 1)
      {
      $currentFolder = $parents[0];
      }
    elseif(isset($this->userSession->recentFolders))
      {
      foreach($parents as $p)
        {
        if(in_array($p->getKey(), $this->userSession->recentFolders))
          {
          $currentFolder = $p;
          break;
          }
        }

      }
    if(isset($currentFolder))
      {
      $items = $this->Folder->getItemsFiltered($currentFolder, $this->userSession->Dao, MIDAS_POLICY_READ);
      $this->view->currentFolder = $currentFolder;
      }

    foreach($items as $key => $item)
      {
      $tmp = Zend_Registry::get('notifier')->callback("CALLBACK_VISUALIZE_CAN_VISUALIZE", array('item' => $item));
      if(isset($tmp['visualize']) && $tmp['visualize'] == true)
        {
        $items[$key]->preview = 'true';
        }
      else
        {
        $items[$key]->preview = 'false';
        }
      }

    $this->view->sameLocation = $items;

    $this->view->json['item'] = $itemDao->toArray();
    $this->view->json['viewMode'] = $viewMode;
    }//end index
  } // end class
?>
