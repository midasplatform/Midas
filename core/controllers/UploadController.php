<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** Upload Controller */
class UploadController extends AppController
  {
  public $_models = array('Assetstore', 'User', 'Item', 'ItemRevision', 'Folder', 'Itempolicyuser', 'Itempolicygroup', 'Group', 'Feed', "Feedpolicygroup", "Feedpolicyuser", 'Bitstream', 'Assetstore');
  public $_daos = array('Assetstore', 'User', 'Item', 'ItemRevision', 'Bitstream', 'Folder');
  public $_components = array('Httpupload', 'Upload');
  public $_forms = array('Upload');

  /**
   * @method init()
   *  Init Controller
   */
  function init()
    {
    $maxFile = str_replace('M', '', ini_get('upload_max_filesize'));
    $maxPost = str_replace('M', '', ini_get('post_max_size'));
    if($maxFile < $maxPost)
      {
      $this->view->maxSizeFile = $maxFile * 1024 * 1024;
      }
    else
      {
      $this->view->maxSizeFile = $maxPost * 1024 * 1024;
      }

    if($this->isTestingEnv())
      {
      $assetstores = $this->Assetstore->getAll();
      if(empty($assetstores))
        {
        $assetstoreDao = new AssetstoreDao();
        $assetstoreDao->setName('Default');
        $assetstoreDao->setPath(BASE_PATH.'/data/assetstore');
        $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
        $this->Assetstore = new AssetstoreModel(); //reset Database adapter
        $this->Assetstore->save($assetstoreDao);
        }
      else
        {
        $assetstoreDao = $assetstores[0];
        }
      $config = Zend_Registry::get('configGlobal');
      $config->defaultassetstore->id = $assetstoreDao->getKey();
      Zend_Registry::set('configGlobal', $config);
      }
    }

  /** simple upload*/
  public function simpleuploadAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->getRequest()->isXmlHttpRequest() && !$this->isTestingEnv())
      {
      throw new Zend_Exception('Error, should be an ajax action.');
      }
    $this->disableLayout();
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

  /**  upload new revision */
  public function revisionAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->getRequest()->isXmlHttpRequest() && !$this->isTestingEnv())
      {
      throw new Zend_Exception('Error, should be an ajax action.');
      }
    $this->disableLayout();
    $itemId = $this->_getParam('itemId');
    $item = $this->Item->load($itemId);

    if($item == false)
      {
      throw new Zend_Exception('Unable to load item.');
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception('Error policies.');
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
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->getRequest()->isXmlHttpRequest() && !$this->isTestingEnv())
      {
      throw new Zend_Exception('Error, should be an ajax action.');
      }

    $this->disableLayout();
    $this->disableView();
    $parent = $this->_getParam('parent');
    $name = $this->_getParam('name');
    $url = $this->_getParam('url');
    $parent = $this->_getParam('parent');
    $license = $this->_getParam('license');
    if(!empty($url) && !empty($name))
      {
      $item = $this->Component->Upload->createLinkItem($this->userSession->Dao, $name, $url, $parent);
      $this->userSession->uploaded[] = $item->getKey();
      }
    }//end simple upload

  /** java upload*/
  public function javauploadAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception('Error, should be an ajax action.');
      }
    $this->_helper->layout->disableLayout();
    $this->view->protocol = 'http';
    $this->view->host = empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['HTTP_X_FORWARDED_HOST'];
    $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;

    $parent = $this->_getParam('parent');
    $license = $this->_getParam('license');
    if(!empty ($parent) && !empty($license))
      {
      $this->disableView();
      $this->userSession->JavaUpload->parent = $parent;
      $this->userSession->JavaUpload->license = $license;
      }
    }//end java upload


  /** used to see how much of a file made it to the server during an
   * interrupted upload attempt **/
  function gethttpuploadoffsetAction()
    {
    $this->disableLayout();
    $this->disableView();
    $params = $this->_getAllParams();
    $url = $this->view->url();
    $url = substr($url,  0, strrpos($url, '/'));
    $params['internParameter'] = substr($url, strrpos($url, '/') + 1);
    $this->Component->Httpupload->get_http_upload_offset($params);
    } //end get_http_upload_offset

  /** java upload function, didn't check what it does :-) */
  function gethttpuploaduniqueidentifierAction()
    {
    $this->disableLayout();
    $this->disableView();
    $params = $this->_getAllParams();
    $this->Component->Httpupload->get_http_upload_unique_identifier($params);
    } //end get_http_upload_unique_identifier


  /** process java upload*/
  function processjavauploadAction()
    {
    $params = $this->_getAllParams();
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    $this->disableLayout();
    $this->disableView();

    $TMP_DIR = BASE_PATH.'/tmp/misc/';
    list ($filename, $path, $length) = $this->Component->Httpupload->process_http_upload($params);

    if(!empty($path) && file_exists($path) && $length > 0)
      {
      if(isset($this->userSession->JavaUpload->parent))
        {
        $parent = $this->userSession->JavaUpload->parent;
        }
      else
        {
        $parent = null;
        }
      if(isset($this->userSession->JavaUpload->license))
        {
        $license = $this->userSession->JavaUpload->license;
        }
      else
        {
        $license = null;
        }
      $item = $this->Component->Upload->createUploadedItem($this->userSession->Dao, $filename, $path, $parent, $license);
      $this->userSession->uploaded[] = $item->getKey();
      }
    } //end processjavaupload


  /** save an uploaded file */
  public function saveuploadedAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }

    $this->disableLayout();
    $this->disableView();

    if($this->isTestingEnv())
      {
      //simulate file upload
      $path = $this->_getParam('path');
      $filename = basename($path);
      $file_size = filesize($path);
      }
    else
      {
      // bugfix: We added an adapter class (see issue 324) under Zend/File/Transfer/Adapter
      ob_start();
      $upload = new Zend_File_Transfer('HttpFixed');
      $upload->receive();
      $path = $upload->getFileName();
      $file_size = filesize($path);
      $filename = $upload->getFilename(null, false);
      ob_end_clean();
      }

    $parent = $this->_getParam('parent');
    $license = $this->_getParam('license');
    if(!empty($path) && file_exists($path))
      {
      $tmp = explode('-', $parent);
      if(count($tmp) == 2) //means we upload a new revision
        {
        $changes = $this->_getParam('changes');
        $this->Component->Upload->createNewRevision($this->userSession->Dao, $filename, $path, $tmp, $changes, $license);
        }
      else
        {
        $item = $this->Component->Upload->createUploadedItem($this->userSession->Dao, $filename, $path, $parent, $license);
        $this->userSession->uploaded[] = $item->getKey();
        }

      $info = array();
      $info['name'] = basename($path);
      $info['size'] = $file_size;
      echo json_encode($info);
      }
    }//end saveuploaded
}//end class
