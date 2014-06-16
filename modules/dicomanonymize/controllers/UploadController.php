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

/** DICOM Anonymized upload controller */
class Dicomanonymize_UploadController extends Dicomanonymize_AppController
  {
  public $_models = array('License');

  /**
   * Configuration view
   */
  function indexAction()
    {
    if(!$this->logged)
      {
      throw new Zend_Exception('You must be logged in', 403);
      }
    $this->disableLayout();
    $this->view->allLicenses = $this->License->getAll();

    if(array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] === 'on')
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

    session_start();
    $parent = $this->_getParam('parent');
    if(!empty($parent))
      {
      $this->disableView();
      $this->userSession->JavaUpload->parent = $parent;
      }
    else
      {
      $this->userSession->JavaUpload->parent = null;
      }

    if(isset($parent))
      {
      $folder = $this->Folder->load($parent);
      if($this->logged && $folder != false)
        {
        $this->view->defaultUploadLocation = $folder->getKey();
        $this->view->defaultUploadLocationText = $folder->getName();
        }
      }
    else
      {
      $folder = null;
      }
    session_write_close();
    $this->view->extraHtml = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_JAVAUPLOAD_EXTRA_HTML',
                                                                      array('folder' => $folder));
    }
  } // end class
