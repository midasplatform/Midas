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
 *  License controller
 */
class LicenseController extends AppController
  {

  public $_models = array('License');
  public $_daos = array();
  public $_components = array();
  public $_forms = array();

  /**
   * Init Controller
   */
  function init()
    {
    }

  /**
   * STUB: index action. Lists all licenses on the admin page
   */
  function indexAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->view->licenses = $this->License->getAll();
    }

  /** View the license text in a dialog */
  function viewAction()
    {
    $this->disableLayout();
    $licenseId = $this->_getParam('licenseId');

    if(!isset($licenseId))
      {
      throw new Zend_Exception('Must pass a license id');
      }
    $license = $this->License->load($licenseId);
    if($license == false)
      {
      throw new Zend_Exception('Invalid licenseId');
      }
    $this->view->license = $license;
    }

} // end class

