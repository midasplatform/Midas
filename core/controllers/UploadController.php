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

/** Upload Controller */
class UploadController extends AppController
  {
  public $_components = array('Upload');
  public $_forms = array('Upload');
  public $_models = array('Assetstore', 'Folder', 'Folderpolicygroup',
    'Folderpolicyuser', 'Item', 'License');

  /** Init controller */
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
        $assetstoreDao->setPath($this->getDataDirectory('assetstore'));
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

  /** Simple upload */
  public function simpleuploadAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->isTestingEnv())
      {
      $this->requireAjaxRequest();
      }
    $this->disableLayout();
    $this->view->form = $this->getFormAsArray($this->Form->Upload->createUploadLinkForm());
    $this->userSession->filePosition = null;
    $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;
    $this->view->allLicenses = $this->License->getAll();

    $this->view->defaultUploadLocation = '';
    $this->view->defaultUploadLocationText = 'You must select a folder';

    $parent = $this->getParam('parent');
    if(isset($parent))
      {
      $parent = $this->Folder->load($parent);
      if($this->logged && $parent != false)
        {
        $this->view->defaultUploadLocation = $parent->getKey();
        $this->view->defaultUploadLocationText = $parent->getName();
        }
      }
    else
      {
      $parent = null;
      }
    $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
      'CALLBACK_CORE_GET_SIMPLEUPLOAD_EXTRA_HTML', array('folder' => $parent));
    $this->view->customTabs = Zend_Registry::get('notifier')->callback(
      'CALLBACK_CORE_GET_UPLOAD_TABS', array());
    }

  /** Upload new revision */
  public function revisionAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->isTestingEnv())
      {
      $this->requireAjaxRequest();
      }
    $this->disableLayout();
    $itemId = $this->getParam('itemId');
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

    // Check if the revision exists and if it does, we send its license ID to
    // the view. If it does not exist we use our default license
    if($itemRevision)
      {
      $this->view->selectedLicense = $itemRevision->getLicenseId();
      }
    else
      {
      $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;
      }

    $this->view->allLicenses = $this->License->getAll();

    if(array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] === 'on')
      {
      $this->view->protocol = 'https';
      }
    else
      {
      $this->view->protocol = 'http';
      }

    if(!$this->isTestingEnv())
      {
      $this->view->host = empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['HTTP_X_FORWARDED_HOST'];
      }
    else
      {
      $this->view->host = 'localhost';
      }

    $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
      'CALLBACK_CORE_GET_REVISIONUPLOAD_EXTRA_HTML', array('item' => $item));
    $this->view->customTabs = Zend_Registry::get('notifier')->callback(
      'CALLBACK_CORE_GET_REVISIONUPLOAD_TABS', array());
    }

  /** Save a link */
  public function savelinkAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }
    if(!$this->isTestingEnv())
      {
      $this->requireAjaxRequest();
      }

    $this->disableLayout();
    $this->disableView();
    $name = $this->getParam('name');
    $url = $this->getParam('url');
    $parent = $this->getParam('parent');
    if(!empty($url) && !empty($name))
      {
      $this->Component->Upload->createLinkItem($this->userSession->Dao, $name, $url, $parent);
      }
    }

  /** Save an uploaded file */
  public function saveuploadedAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You have to be logged in to do that');
      }

    $this->disableLayout();
    $this->disableView();
    $pathClient = $this->getParam('path');

    if($this->isTestingEnv())
      {
      // simulate file upload
      $path = $this->getParam('testpath');
      $filename = basename($path);
      $file_size = filesize($path);
      }
    else
      {
      // bug fix: We added an adapter class (see issue 324) under Zend/File/Transfer/Adapter
      ob_start();
      $upload = new Zend_File_Transfer('HttpFixed');
      $upload->receive();
      $path = $upload->getFileName();
      $file_size = filesize($path);
      $filename = $upload->getFilename(null, false);
      ob_end_clean();
      }

    // If we are uploading a directory
    // $pathClient stores the list of full path for the files in the directory
    if(!empty($pathClient))
      {
      if(strlen(str_replace(';', '', $pathClient)) > 0) // Check that we are dealing with folders
        {
        if(!isset($this->userSession->filePosition) || ($this->userSession->filePosition === null) )
          {
          $this->userSession->filePosition = 0;
          }
        else
          {
          $this->userSession->filePosition++;
          }

        $filesArray = explode(';;', $pathClient);

        // The $filesArray also contains directories 'XXX/.' so we account for it
        $i = -1;
        foreach($filesArray as $extractedFilename)
          {
          if(substr($extractedFilename, strlen($extractedFilename)-2, 2) != '/.')
            {
            $i++;
            }
          if($i == $this->userSession->filePosition)
            {
            $pathClient = $extractedFilename;
            break;
            }
          }
        }
      }

    $parent = $this->getParam('parent');
    $license = $this->getParam('license');

    if(!empty($path) && file_exists($path))
      {
      $itemId_itemRevisionNumber = explode('-', $parent);
      if(count($itemId_itemRevisionNumber) == 2) //means we upload a new revision
        {
        $changes = $this->getParam('changes');
        $itemId = $itemId_itemRevisionNumber[0];
        $itemRevisionNumber = $itemId_itemRevisionNumber[1];

        $validations = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_VALIDATE_UPLOAD_REVISION',
                                                                array('filename' => $filename,
                                                                      'size' => $file_size,
                                                                      'path' => $path,
                                                                      'itemId' => $itemId,
                                                                      'changes' => $changes,
                                                                      'revisionNumber' => $itemRevisionNumber));
        foreach($validations as $validation)
          {
          if(!$validation['status'])
            {
            unlink($path);
            throw new Zend_Exception($validation['message']);
            }
          }
        $this->Component->Upload->createNewRevision($this->userSession->Dao, $filename, $path,
                                                    $changes, $itemId, $itemRevisionNumber, $license,
                                                    '', (bool)$this->isTestingEnv());
        }
      else
        {
        $newRevision = (bool)$this->getParam('newRevision'); //on name collision, should we create new revision?
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
        $validations = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_VALIDATE_UPLOAD',
                                                                array('filename' => $filename,
                                                                      'size' => $file_size,
                                                                      'path' => $path,
                                                                      'folderId' => $parent));
        foreach($validations as $validation)
          {
          if(!$validation['status'])
            {
            unlink($path);
            throw new Zend_Exception($validation['message']);
            }
          }
        $this->Component->Upload->createUploadedItem($this->userSession->Dao, $filename,
                                                     $path, $parent, $license, '',
                                                     (bool)$this->isTestingEnv(),
                                                     $newRevision);
        }

      $info = array();
      $info['name'] = basename($path);
      $info['size'] = $file_size;
      echo JsonComponent::encode($info);
      }
    }
  }
