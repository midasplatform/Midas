<?php
/** Upload Controller */
class UploadController extends AppController
  {
  public $_models = array('User', 'Item', 'ItemRevision', 'Folder', 'Itempolicyuser', "ItemKeyword", 'Itempolicygroup', 'Group', 'Feed', "Feedpolicygroup", "Feedpolicyuser", 'Bitstream', 'Assetstore');
  public $_daos = array('User', 'Item', 'ItemRevision', 'Bitstream', 'Folder', "ItemKeyword");
  public $_components = array('Httpupload', 'Upload');
  public $_forms = array('Upload');

  /**
   * @method init()
   *  Init Controller
   */
  function init()
    {
    $maxFile = str_replace("M", "", ini_get('upload_max_filesize'));
    $maxPost = str_replace("M", "", ini_get('post_max_size'));
    if($maxFile < $maxPost)
      {
      $this->view->maxSizeFile = $maxFile;
      }
    else
      {
      $this->view->maxSizeFile = $maxPost;
      }
    }

  /** simple upload*/
  public function simpleuploadAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Error, should be an ajax action.");
      }
    $this->_helper->layout->disableLayout();
    $this->view->form = $this->getFormAsArray($this->Form->Upload->createUploadLinkForm());
    $this->userSession->uploaded = array();
    $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;

    $this->view->defaultUploadLocation = $this->userSession->Dao->getPrivatefolderId();
    $this->view->defaultUploadLocationText = $this->t('My Private Folder');

    $parent = $this->_getParam('parent');
    if(isset($parent))
      {
      $parent = $this->Folder->load($parent);
      if($this->logged && $parent != false)
        {
        $this->view->defaultUploadLocation = $parent->getKey();
        $this->view->defaultUploadLocationText = $parent->getName();
        }
      }
    }//end simple upload

  /**  upload new revision*/
  public function revisionAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Error, should be an ajax action.");
      }
    $this->_helper->layout->disableLayout();
    $itemId = $this->_getParam('itemId');
    $item = $this->Item->load($itemId);

    if($item == false)
      {
      throw new Zend_Exception("Unable to load item.");
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Error policies.");
      }
    $this->view->item = $item;
    $itemRevision = $this->Item->getLastRevision($item);
    $this->view->lastrevision = $itemRevision;
    }//end revisionAction


  /** save a link*/
  public function savelinkAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Error, should be an ajax action.");
      }
    $form = $this->Form->Upload->createUploadLinkForm();

    if(true)
      {
      $path_parts = pathinfo($form->getValue('url'));
      $name = $path_parts['basename'];
      $item = new ItemDao;
      $item->setName($name);
      $this->Item->save($item);
      $this->userSession->uploaded[] = $item->getKey();
      $feed = $this->Feed->createFeed($this->userSession->Dao, MIDAS_FEED_CREATE_LINK_ITEM, $item);
      $this->Folder->addItem($this->userSession->Dao->getPrivateFolder(), $item);
      $this->Feedpolicyuser->createPolicy($this->userSession->Dao, $feed, MIDAS_POLICY_ADMIN);
      $this->Itempolicyuser->createPolicy($this->userSession->Dao, $item, MIDAS_POLICY_ADMIN);
      $itemRevisionDao = new ItemRevisionDao;
      $itemRevisionDao->setChanges('Initial revision');
      $itemRevisionDao->setUser_id($this->userSession->Dao->getKey());
      $this->Item->addRevision($item, $itemRevisionDao);
      $this->getLogger()->info(__METHOD__." Upload link ok ".$name.":".$form->getValue('url'));
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    }//end simple upload

  /** java upload*/
  public function javauploadAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Error, should be an ajax action.");
      }
    $this->_helper->layout->disableLayout();
    $this->view->protocol = "http";
    $this->view->host = empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['HTTP_X_FORWARDED_HOST'];
    }//end java upload


  /** used to see how much of a file made it to the server during an
   * interrupted upload attempt **/
  function gethttpuploadoffsetAction()
    {
    $params = $this->_getAllParams();
    $url = $this->view->url();
    $url = substr($url,  0, strrpos($url, '/'));
    $params['internParameter'] = substr($url, strrpos($url, '/') + 1);
    $this->Component->Httpupload->get_http_upload_offset($params);
    } //end get_http_upload_offset

  /** java upload function, didn 't check what it does :-) */
  function gethttpuploaduniqueidentifierAction()
    {
    $params = $this->_getAllParams();
    $this->Component->Httpupload->get_http_upload_unique_identifier($params);
    } //end get_http_upload_unique_identifier


  /** process java upload*/
  function processjavauploadAction()
    {
    $params = $this->_getAllParams();
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $TMP_DIR = BASE_PATH.'/tmp/misc/';
    list ($filename, $path, $length) = $this->Component->Httpupload->process_http_upload($params);

    if(!empty($path) && file_exists($path) && $length > 0)
      {
      $item = $this->Component->Upload->createUploadedItem($this->userSession->Dao, $filename, $path);
      $this->userSession->uploaded[] = $item->getKey();
      }
    } //end processjavaupload


  /** save an uploaded file */
  public function saveuploadedAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $upload = new Zend_File_Transfer();
    $upload->receive();
    $path = $upload->getFileName();
    $file_size = filesize($path);
    $parent = $this->_getParam("parent");
    $license = $this->_getParam("license");
    if(!empty($path) && file_exists($path) && $upload->getFileSize() > 0)
      {
      $tmp = explode('-', $parent);
      if(count($tmp) == 2) //means we upload a new revision
        {
        $changes = $this->_getParam("changes");
        $this->Component->Upload->createNewRevision($this->userSession->Dao, $upload->getFilename(null, false), $upload->getFilename(), $tmp, $changes, $license);
        }
      else
        {
        $item = $this->Component->Upload->createUploadedItem($this->userSession->Dao, $upload->getFilename(null, false), $upload->getFilename(), $parent, $license);
        $this->userSession->uploaded[] = $item->getKey();
        }

      $info = array();
      $info['name'] = basename($upload->getFileName());        
      $info['size'] = $file_size;
      echo json_encode($info);
      }
    }//end saveuploaded
}//end class