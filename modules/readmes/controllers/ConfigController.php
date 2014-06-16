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
 * Readmes_ConfigController
 *
 * @category   Midas modules
 * @package    readmes
 */
class Readmes_ConfigController extends Readmes_AppController
  {
  public $_components = array('Breadcrumb');

  /**
   * @method indexAction()
   * @throws Zend_Exception on invalid userSession
   */
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $iconPath = $this->view->moduleWebroot.'/public/images/page_white_text.png';
    $breadcrumbs = array();
    $breadcrumbs[] = array('type' => 'moduleList');
    $breadcrumbs[] = array('type' => 'custom',
                           'text' => 'Readme Module Configuration',
                           'icon' => $iconPath);
    $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);
    }
  } // end class
