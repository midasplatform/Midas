<?php

/** Controller for downloading key files */
class Keyfiles_DownloadController extends Keyfiles_AppController
{
  var $_models = array('Bitstream', 'Folder', 'Item');

  /**
   * Download all key files for the head revision of an item
   */
  public function itemAction()
    {
    $itemId = $this->_getParam('itemId');
    if(!isset($itemId))
      {
      throw new Exception('Must pass an itemId parameter');
      }
    $item = $this->Item->load($itemId);
    if(!$item)
      {
      throw new Zend_Exception('Invalid itemId', 404);
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao))
      {
      throw new Zend_Exception('Read permission required', 403);
      }
    $revision = $this->Item->getLastRevision($item);
    if(!$revision)
      {
      throw new Zend_Exception('Item must have at least one revision', 404);
      }
    $this->disableView();
    $this->disableLayout();

    if(headers_sent())
      {
      return;
      }

    $this->_emptyOutputBuffer();
    ob_start(); //must start a new buffer for ZipStream to work

    Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
    $zip = new ZipStream($item->getName().'.zip');
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $bitstream)
      {
      $zip->add_file($bitstream->getName().'.md5', $bitstream->getChecksum());
      }
    $zip->finish();
    exit();
    }

  /**
   * Download the key file for a specific bitstream
   */
  public function bitstreamAction()
    {
    $bitstreamId = $this->_getParam('bitstreamId');
    if(!isset($bitstreamId))
      {
      throw new Exception('Must pass a bitstreamId parameter');
      }
    $bitstream = $this->Bitstream->load($bitstreamId);
    if(!$bitstream)
      {
      throw new Zend_Exception('Invalid bitstreamId', 404);
      }
    $item = $bitstream->getItemrevision()->getItem();
    if(!$this->Item->policyCheck($item, $this->userSession->Dao))
      {
      throw new Zend_Exception('Read permission required', 403);
      }
    $this->disableView();
    $this->disableLayout();

    $checksum = $bitstream->getChecksum();

    $download = !headers_sent();
    if($download)
      {
      $this->_emptyOutputBuffer();
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.$bitstream->getName().'.md5"');
      header('Content-Length: '.strlen($checksum));
      header('Expires: 0');
      header('Accept-Ranges: bytes');
      header('Cache-Control: private', false);
      header('Pragma: private');
      }

    echo $checksum;

    if($download)
      {
      exit();
      }
    }

  /**
   * Kill the whole ob stack (Zend uses double nested output buffers)
   */
  private function _emptyOutputBuffer()
    {
    while(ob_get_level() > 0)
      {
      ob_end_clean();
      }
    }

  /**
   * Download key files for a selected group of folders and/or items
   * @param items List of item id's separated by -
   * @param folders List of folder id's separated by -
   */
  public function batchAction()
    {
    $itemIds = $this->_getParam('items');
    $folderIds = $this->_getParam('folders');
    if(!isset($itemIds) && !isset($folderIds))
      {
      throw new Zend_Exception('No parameters');
      }
    $this->disableLayout();
    $this->disableView();
    $folderIds = explode('-', $folderIds);
    $folders = $this->Folder->load($folderIds);

    $itemIds = explode('-', $itemIds);
    $items = $this->Item->load($itemIds);
    $revisions = array();
    foreach($items as $item)
      {
      $tmp = $this->Item->getLastRevision($item);
      if($tmp !== false)
        {
        $revisions[] = $tmp;
        }
      }
    if(headers_sent())
      {
      return;
      }
    $this->_emptyOutputBuffer();
    ob_start(); //must start a new buffer for ZipStream to work

    Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
    ob_start();
    $zip = new ZipStream('Keyfiles.zip');
    $zip = $this->_createZipRecursive($zip, '', $folders, $revisions, $this->userSession->Dao);
    $zip->finish();
    exit();
    }

  /** Helper function to create and stream the hierarchy */
  private function _createZipRecursive($zip, $path, $folders, $revisions, $sessionUser)
    {
    foreach($revisions as $revision)
      {
      if(!$this->Item->policyCheck($revision->getItem(), $sessionUser))
        {
        continue;
        }
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
        $filename = $currPath.'/'.$bitstream->getName().'.md5';
        $zip->add_file($filename, $bitstream->getChecksum());
        }
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
}//end class
