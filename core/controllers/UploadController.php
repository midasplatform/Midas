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
  public $_models = array('Folderpolicygroup', 'Folderpolicyuser', 'Assetstore', 'User', 'Item', 'ItemRevision', 'Folder', 'Itempolicyuser', 'Itempolicygroup', 'Group', 'Feed', "Feedpolicygroup", "Feedpolicyuser", 'Bitstream', 'Assetstore');
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
    if(!empty($parent) && !empty($license))
      {
      $this->disableView();
      $this->userSession->JavaUpload->parent = $parent;
      $this->userSession->JavaUpload->license = $license;
      }
    }//end java upload


  /**
   * Used to see how much of a file made it to the server during an interrupted upload attempt
   * @param uploadUniqueIdentifier The upload token to check
   */
  function gethttpuploadoffsetAction()
    {
    $this->disableLayout();
    $this->disableView();
    $params = $this->_getAllParams();

    list($userId, , ) = explode('/', $params['uploadUniqueIdentifier']);
    if($userId != $this->userSession->Dao->getUserId())
      {
      echo '[ERROR]User id does not match upload token user id';
      throw new Zend_Exception('User id does not match upload token user id');
      }

    $this->Component->Httpupload->setTmpDirectory($this->getTempDirectory());
    $this->Component->Httpupload->setTokenParamName('uploadUniqueIdentifier');
    $offset = $this->Component->Httpupload->getOffset($params);
    echo '[OK]'.$offset['offset'];
    } //end gethttpuploadoffset

  /**
   * Get a unique upload token for the java uploader. Must be logged in to do this
   * @param filename The name of the file to be uploaded
   */
  function gethttpuploaduniqueidentifierAction()
    {
    $this->disableLayout();
    $this->disableView();
    $params = $this->_getAllParams();

    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }

    if(!isset($params['testingmode']) && $this->userSession->JavaUpload->parent)
      {
      $folderId = $this->userSession->JavaUpload->parent;
      }
    else
      {
      $folderId = $this->userSession->Dao->getPrivatefolderId();
      }

    $this->Component->Httpupload->setTmpDirectory($this->getTempDirectory());

    $dir = $this->userSession->Dao->getUserId().'/'.$folderId;
    try
      {
      $token = $this->Component->Httpupload->generateToken($params, $dir);
      echo '[OK]'.$token['token'];
      }
    catch(Exception $e)
      {
      echo '[ERROR]'.$e->getMessage();
      throw $e;
      }
    } //end get_http_upload_unique_identifier


  /**
   * Process a java upload
   * @param uploadUniqueIdentifier The upload token (see gethttpuploaduniqueidentifierAction)
   * @param filename The name of the file being uploaded
   * @param length The length of the file being uploaded
   */
  function processjavauploadAction()
    {
    $this->disableLayout();
    $this->disableView();
    $params = $this->_getAllParams();

    if(!$this->logged)
      {
      echo '[ERROR]You must be logged in to upload';
      throw new Zend_Exception('You have to be logged in to do that');
      }
    list($userId, $parentId, ) = explode('/', $params['uploadUniqueIdentifier']);
    if($userId != $this->userSession->Dao->getUserId())
      {
      echo '[ERROR]User id does not match upload token user id';
      throw new Zend_Exception('User id does not match upload token user id');
      }

    if(!isset($params['testingmode']) && $this->userSession->JavaUpload->parent)
      {
      $expectedParentId = $this->userSession->JavaUpload->parent;
      }
    else
      {
      $expectedParentId = $this->userSession->Dao->getPrivatefolderId();
      }

    if($parentId != $expectedParentId)
      {
      echo '[ERROR]You are attempting to upload into the incorrect parent folder';
      throw new Zend_Exception('You are attempting to upload into the incorrect parent folder');
      }

    $this->Component->Httpupload->setTmpDirectory($this->getTempDirectory());
    $this->Component->Httpupload->setTestingMode(Zend_Registry::get('configGlobal')->environment == 'testing');
    $this->Component->Httpupload->setTokenParamName('uploadUniqueIdentifier');
    $data = $this->Component->Httpupload->process($params);

    if(!empty($data['path']) && file_exists($data['path']) && $data['size'] > 0)
      {
      if(!isset($params['testingmode']) && isset($this->userSession->JavaUpload->parent))
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

      try
        {
        $item = $this->Component->Upload->createUploadedItem($this->userSession->Dao, $data['filename'], $data['path'], $parent, $license, $data['md5']);
        }
      catch(Exception $e)
        {
        echo "[ERROR] ".$e->getMessage();
        throw $e;
        }
      $this->userSession->uploaded[] = $item->getKey();
      echo "[OK]";
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
    $pathClient = $this->_getParam("path");

    if($this->isTestingEnv())
      {
      //simulate file upload
      $path = BASE_PATH.'/tests/testfiles/search.png';
      $file_size = filesize($path);
      $filename = 'search.png';
      }
    else
      {
      // bugfix: We added an adapter class (see issue 324) under Zend/File/Transfer/Adapter
      ob_start();
      $upload = new Zend_File_Transfer('HttpFixed');
      $upload->receive();
      $path = $upload->getFileName();
      $file_size =  filesize($path);
      $filename = $upload->getFilename(null, false);
      ob_end_clean();
      }

    if(!empty($pathClient))
      {
      $tmpArray = explode(';;', $pathClient);
      foreach($tmpArray as $value)
        {
        $tmpPathValue = explode('/', $value);
        if(end($tmpPathValue) == $filename)
          {
          $pathClient = $value;
          break;
          }
        }
      }

    $parent = $this->_getParam('parent');
    $license = $this->_getParam('license');

    if(!empty($path) && file_exists($path) && $file_size > 0)
      {
      $tmp = explode('-', $parent);
      if(count($tmp) == 2) //means we upload a new revision
        {
        $changes = $this->_getParam('changes');
        $this->Component->Upload->createNewRevision($this->userSession->Dao, $filename, $path, $tmp, $changes, $license);
        }
      else
        {
        if(!empty($pathClient) && $pathClient != ";;")
          {
          $parentDao = $this->Folder->load($parent);
          $folders = explode('/', $pathClient);
          unset($folders[count($folders) - 1]);
          foreach($folders as $folderName)
            {
            if($this->Folder->getFolderExists($folderName, $parentDao))
              {
              $parentDao = $this->Folder->getFolderByName($parentDao, $folderName);
              }
            else
              {
              $new_folder = $this->Folder->createFolder($folderName, '', $parentDao);
              $policyGroup = $parentDao->getFolderpolicygroup();
              $policyUser = $parentDao->getFolderpolicyuser();
              foreach($policyGroup as $policy)
                {
                $group = $policy->getGroup();
                $policyValue = $policy->getPolicy();
                $this->Folderpolicygroup->createPolicy($group, $new_folder, $policyValue);
                }
              foreach($policyUser as $policy)
                {
                $user = $policy->getUser();
                $policyValue = $policy->getPolicy();
                $this->Folderpolicyuser->createPolicy($user, $new_folder, $policyValue);
                }
              $parentDao = $new_folder;
              }
            }
          $parent = $parentDao->getKey();
          }
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
