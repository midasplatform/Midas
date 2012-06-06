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
 *  Controller for downloading elements.
 */
class DownloadController extends AppController
{
  public $_models = array('Folder', 'Item', 'Community', 'User', 'Bitstream');
  public $_daos = array();
  public $_components = array('DownloadBitstream');

  /** index
   * @param folders = 12-13 (will download a zip of the folder 12 and 13 ,recusively)
   * @param folders = 12, 1-13, 1 (will download a zip of the folder 12 and 13 ,recusively) //Need testing
   * @param items = 12-13 (will download a zip containing the last revisions of the items 12 and 13)
   * @param items = 12, 1-13 (will download a zip containing the revision 1 of item 12 and last revision of item 13)
   * @param items = 12, 1 (will download the revision 1 of the item 12, a zip ifthere are multiple bitstream or simply the file)
   * @param bitstream = 1 (will download related bitstream)
   * @param offset The offset in bytes if downloading a bitstream (defaults to 0)
   * @param name Alternate filename when downloading a bitstream (defaults to bitstream name)
   */
  public function indexAction()
    {
    $this->disableLayout();
    $itemIds = $this->_getParam('items');
    $folderIds = $this->_getParam('folders');
    $bitsreamid = $this->_getParam('bitstream');
    $sessionUser = $this->userSession->Dao;
    if($sessionUser != null)
      {
      // Make sure this is a copy and not a reference
      $sessionUser = $this->User->load($sessionUser->getKey());
      }
    if(isset($bitsreamid) && is_numeric($bitsreamid))
      {
      $name = $this->_getParam('name');
      $offset = $this->_getParam('offset');
      if(!isset($offset))
        {
        $offset = 0;
        }
      $bitstream = $this->Bitstream->load($bitsreamid);
      if(!$bitstream)
        {
        throw new Zend_Controller_Action_Exception('Invalid bitstream id', 404);
        }

      if(isset($name))
        {
        $bitstream->setName($name); //don't save name, just set it on this dao
        }

      $revision = $bitstream->getItemrevision();
      $item = $revision->getItem();
      if($item == false || !$this->Item->policyCheck($item, $sessionUser))
        {
        throw new Zend_Exception('Permission denied');
        }
      $this->Component->DownloadBitstream->download($bitstream, $offset, true);
      return;
      }
    if(!isset($itemIds) && !isset($folderIds))
      {
      throw new Zend_Exception("No parameters");
      }
    $folderIds = explode('-', $folderIds);
    $folders = $this->Folder->load($folderIds);

    $itemIds = explode('-', $itemIds);
    $revisions = array();
    if(!empty($itemIds))
      {
      foreach($itemIds as $itemId)
        {
        // check revision
        $tmp = explode(',', $itemId);
        if(empty($tmp[0]))
          {
          continue;
          }
        $item = $this->Item->load($tmp[0]);
        if($item == false || !$this->Item->policyCheck($item, $sessionUser))
          {
          continue;
          }
        if(isset($tmp[1]))
          {
          $tmp = $this->Item->getRevision($item, $tmp[1]);
          if($tmp !== false)
            {
            $revisions[] = $tmp;
            }
          }
        else
          {
          $tmp = $this->Item->getLastRevision($item);
          if($tmp !== false)
            {
            $revisions[] = $tmp;
            }
          }
        }
      }

    if(empty($folders) && empty($revisions))
      {
      // download an empty zip with the name of item (if it exists), then exit
      $this->_downloadEmptyItem($item);
      }
    if(empty($folders) && count($revisions) == 1)
      {
      $revision = $revisions[0];
      $bitstreams = $revision->getBitstreams();

      if($this->_getParam('testingmode') == '1')
        {
        $bitstreams = array($bitstreams[0]);
        }

      if(count($bitstreams) == 0)
        {
        // download an empty zip with the name of item (if it exists), then exit
        $this->Item->incrementDownloadCount($revision->getItem());
        $this->_downloadEmptyItem($revision->getItem());
        }
      elseif(count($bitstreams) == 1)
        {
        if(strpos($bitstreams[0]->getPath(), 'http://') !== false)
          {
          $this->_redirect($bitstreams[0]->getPath());
          return;
          }
        $this->disableView();
        if($this->_getParam('testingmode') == '1')
          {
          $this->Component->DownloadBitstream->testingmode = true;
          }
        $this->Component->DownloadBitstream->download($bitstreams[0], 0, true);
        }
      else
        {
        while(ob_get_level() > 0)
          {
          ob_end_clean();
          }
        ob_start();
        Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
        $this->_helper->viewRenderer->setNoRender();
        $name = $revision->getItem()->getName();
        $name = substr($name, 0, 50);
        session_write_close(); //unlock session writing for concurrent access
        $zip = new ZipStream($name.'.zip');
        foreach($bitstreams as $bitstream)
          {
          $filename = $bitstream->getName();
          $path = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
          Zend_Registry::get('dbAdapter')->closeConnection();
          $zip->add_file_from_path($filename, $path);
          }
        $zip->finish();
        $this->Item->incrementDownloadCount($revision->getItem());
        exit();
        }
      }
    else
      {
      Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
      $this->_helper->viewRenderer->setNoRender();
      if(count($folders) == 1 && empty($revisions))
        {
        $name = $folders[0]->getName();
        $name = substr($name, 0, 50);

        $rootFolder = $this->Folder->getRoot($folders[0]);
        if($rootFolder)
          {
          // Find the Community or the User which have the folder
          $rootCom = $this->Community->getByFolder($rootFolder);
          if(!$rootCom)
            {
            $user = $this->User->getByFolder($rootFolder);
            $name = $user->getFirstname().$user->getLastname().'-'.$name;
            }
          else
            {
            $name = $rootCom->getName().'-'.$name;
            }
          }
        }
      else
        {
        $name = "Custom";
        }

      session_write_close(); //unlock session writing for concurrent access
      while(ob_get_level() > 0)
        {
        ob_end_clean();
        }
      ob_start();
      $zip = new ZipStream($name.'.zip');
      $zip = $this->_createZipRecursive($zip, '', $folders, $revisions, $sessionUser);
      $zip->finish();
      exit();
      }
    }//end index

  /**
   * will download a zip file with the same name as the item name,
   * if the item exists, then will exit.
   * @param type $item
   */
  private function _downloadEmptyItem($item)
    {
    while(ob_get_level() > 0)
      {
      ob_end_clean();
      }
    ob_start();
    Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
    $this->_helper->viewRenderer->setNoRender();
    if(isset($item))
      {
      $name = $item->getName();
      }
    else
      {
      $name = "No_item_selected";
      }
    $name = substr($name, 0, 50);
    $zip = new ZipStream($name.'.zip');
    $zip->finish();
    exit();
    }

  /** create zip recursive*/
  private function _createZipRecursive($zip, $path, $folders, $revisions, $sessionUser)
    {
    foreach($revisions as $revision)
      {
      $itemName = $revision->getItem()->getName();
      $bitstreams = $revision->getBitstreams();
      $count = count($bitstreams);

      foreach($bitstreams as $bitstream)
        {
        if($count > 1 || $bitstream->getName() != $itemName)
          {
          $currPath = $path.'/'.$itemName;
          }
        else
          {
          $currPath = $path;
          }
        $filename = $currPath.'/'.$bitstream->getName();
        $fullpath = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
        Zend_Registry::get('dbAdapter')->closeConnection();
        $zip->add_file_from_path($filename, $fullpath);
        }
      $this->Item->incrementDownloadCount($revision->getItem());
      }
    foreach($folders as $folder)
      {
      if(!$this->Folder->policyCheck($folder, $sessionUser))
        {
        continue;
        }
      $items = $folder->getItems();
      $subRevisions = array();
      foreach($items as $item)
        {
        $itemName = $item->getName();
        if(!$this->Item->policyCheck($item, $sessionUser))
          {
          continue;
          }
        $tmp = $this->Item->getLastRevision($item);
        if($tmp !== false)
          {
          $subRevisions[] = $tmp;
          }
        }
      $zip = $this->_createZipRecursive($zip, $path.'/'.$folder->getName(), $folder->getFolders(), $subRevisions, $sessionUser);
      }
    return $zip;
    }
} // end class

