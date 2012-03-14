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

/**
 * This class is used to create thumbnails
 */
class Thumbnailcreator_ThumbnailController extends Thumbnailcreator_AppController
{
  public $_models = array('Bitstream', 'Item');
  public $_moduleModels = array('Itemthumbnail');
  public $_moduleDaos = array('Itemthumbnail');

  /**
   * Creates a thumbnail
   * @param bitstreamId The bitstream to create the thumbnail from
   * @param itemId The item to set the thumbnail on
   * @param width (Optional) The width in pixels to resize to (aspect ratio will be preserved). Defaults to 575
   * @return array('thumbnail' => path to the thumbnail)
   */
  function createAction()
    {
    $itemId = $this->_getParam('itemId');
    if(!isset($itemId))
      {
      throw new Zend_Exception('itemId parameter required');
      }
    $bitstreamId = $this->_getParam('bitstreamId');
    if(!isset($bitstreamId))
      {
      throw new Zend_Exception('bitstreamId parameter required');
      }
    $width = $this->_getParam('width');
    if(!isset($width))
      {
      $width = 575;
      }
    $this->disableView();
    $this->disableLayout();

    $bitstream = $this->Bitstream->load($bitstreamId);
    $item = $this->Item->load($itemId);
    if(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      echo JsonComponent::encode(array('status' => 'error', 'message' => 'Write permission required'));
      return;
      }
    $componentLoader = new MIDAS_ComponentLoader();
    $imComponent = $componentLoader->loadComponent('Imagemagick', 'thumbnailcreator');

    $itemThumbnail = $this->Thumbnailcreator_Itemthumbnail->getByItemId($item->getKey());
    if(!$itemThumbnail)
      {
      $itemThumbnail = new Thumbnailcreator_ItemthumbnailDao();
      $itemThumbnail->setItemId($item->getKey());
      $oldThumbnail = '';
      }
    else
      {
      $oldThumbnail = $itemThumbnail->getThumbnail();
      }

    try
      {
      $thumbnail = $imComponent->createThumbnailFromPath($bitstream->getFullPath(), (int)$width, 0, false);
      if(!file_exists($thumbnail))
        {
        echo JsonComponent::encode(array('status' => 'error', 'message' => 'Could not create thumbnail from the bitstream'));
        return;
        }
      $thumbnail = substr($thumbnail, strlen(BASE_PATH) + 1); //convert to relative path from base directory

      if(!empty($oldThumbnail) && file_exists(BASE_PATH.'/'.$oldThumbnail))
        {
        unlink(BASE_PATH.'/'.$oldThumbnail);
        }

      $itemThumbnail->setThumbnail($thumbnail);
      $this->Thumbnailcreator_Itemthumbnail->save($itemThumbnail);
      echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Thumbnail saved', 'thumbnail' => $thumbnail));
      }
    catch(Exception $e)
      {
      echo JsonComponent::encode(array('status' => 'error', 'message' => 'Error: '.$e->getMessage()));
      }
    }

}//end class
