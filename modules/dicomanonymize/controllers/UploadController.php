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
