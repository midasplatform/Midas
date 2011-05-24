<?php

/**
 *  AJAX request for the admin Controller
 */
class DownloadController extends AppController
{
  public $_models = array("Folder", 'Item');
  public $_daos = array();
  public $_components = array();
   
  /** index
   * @param ?folders = 12-13 (will download a zip of the folder 12 and 13 ,recusively)
   * @param ?folders = 12, 1-13, 1 (will download a zip of the folder 12 and 13 ,recusively) //Need testing
   * @param ?items = 12-13 (will download a zip containing the last revisions of the items 12 and 13)
   * @param ?items = 12, 1-13 (will download a zip containing the revision 1 of item 12 and last revision of item 13)
   * @param ?items = 12, 1 (will download the revision 1 of the item 12, a zip ifthere are multiple bitstream or simply the file)
   */
  public function indexAction()
    {
    set_time_limit(0);
    $this->_helper->layout->disableLayout();
    $itemIds = $this->_getParam('items');
    $folderIds = $this->_getParam('folders');
    if(!isset($itemIds) && !isset($folderIds))
      {
      throw new Zend_Exception("No parameters");
      }
    $folderIds = explode('-', $folderIds);
    $folders = array();
    foreach($folderIds as $folderId)
      {
      $tmp = explode(', ', $folderId);
      if(empty($tmp[0]))
        {
        continue;
        }
      $folder = $this->Folder->load($tmp[0]);
      if($folder == false)
        {
        continue;
        }
      if(!isset($tmp[0]) || $tmp[0] == 1)
        {
        $folder->recursive = true;
        }
      else
        {
        $folder->recursive = false;
        }
      }
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
        if($item == false || !$this->Item->policyCheck($item, $this->userSession->Dao))
          {
          continue;
          }
        $this->Item->incrementDownloadCount($item);
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
      throw new Zend_Exception("No element");
      }
    if(empty($folders) && count($revisions) == 1)
      {
      $revision = $revisions[0];
      $bitstreams = $revision->getBitstreams();
      if(count($bitstreams) == 0)
        {
        throw new Zend_Exception("Empty item");
        }
      elseif(count($bitstreams) == 1)
        {
        $bitstream = $bitstreams[0];
        if(strpos($bitstream->getPath(), 'http://') !== false)
          {
          $this->_redirect($bitstream->getPath());
          return;
          }
        $this->view->mimetype = $bitstream->getMimetype();
        $this->view->path = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
        $this->view->name = $bitstream->getName();
        if(!file_exists($this->view->path))
          {
          throw new Zend_Exception("Unable to find file on the disk");
          }
        $this->render('onebitstream');
        }
      else
        {
        Zend_Loader::loadClass("ZipStream", BASE_PATH.'/library/ZipStream/');
        $this->_helper->viewRenderer->setNoRender();
        $name = $revision->getItem()->getName(); 
        $name = substr($name, 0, 50);
        $zip = new ZipStream($name.'.zip');
        foreach($bitstreams as $bitstream)
          {
          $zip->add_file_from_path($bitstream->getName(), $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath());
          }
        $zip->finish();
        }
      }
    else
      {
      Zend_Loader::loadClass("ZipStream", BASE_PATH.'/library/ZipStream/');
      $this->_helper->viewRenderer->setNoRender();
      if(count($folders) == 1 && empty($revisions))
        {
        $name = $folders[0]->getName(); 
        $name = substr($name, 0, 50);
        }
      else
        {
        $name = "Custom";
        }
      $zip = new ZipStream($name.'.zip');
      $zip = $this->_createZipRecursive($zip, '', $folders, $revisions);
      $zip->finish();
      }
    }//end index
   
  /** create zip recursive*/
  private function _createZipRecursive($zip, $path, $folders, $revisions)
    {
    foreach($revisions as $revision)
      {
      $bitstreams = $revision->getBitstreams();
      foreach($bitstreams as $bitstream)
        {
        $zip->add_file_from_path($path.'/'.$bitstream->getName(), $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath());
        }
      }
    foreach($folders as $folder)
      {
      if(!$this->Folder->policyCheck($folder, $this->userSession->Dao))
        {
        continue;
        } 
      $items = $folder->getItems();
      $subRevisions = array();
      foreach($items as $item)
        {
        if(!$this->Item->policyCheck($item, $this->userSession->Dao))
          {
          continue;
          }       
        $tmp = $this->Item->getLastRevision($item);
        if($tmp !== false)
          {
          $subRevisions[] = $tmp;
          if(isset($folder->recursive) && $folder->recursive == false)
            {
            $bitstreams = $subRevisions->getBitstreams();
            foreach($bitstreams as $bitstream)
              {
              $zip->add_file_from_path($path.'/'.$bitstream->getName(), $bitstream->getAssetstore()->getPath().'/'. $bitstream->getPath());
              }
            }
          }        
        }
      if(!isset($folder->recursive) || $folder->recursive)
        {
        $zip = $this->_createZipRecursive($zip, $path.'/'.$folder->getName(), $folder->getFolders(), $subRevisions);
        }
      }
    return $zip;
    }
} // end class

  