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

/** Configure controller for archive module */
class Archive_ConfigController extends Archive_AppController
  {
  public $_models = array('Setting');

  /** Admin config page */
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $this->view->header = 'Archive extraction module configuration';
    $this->view->json[$this->moduleName]['unzipCommand'] = $this->Setting->getValueByName('unzipCommand', $this->moduleName);
    }

  /** Submit module configuration options */
  function submitAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();

    $unzipCmd = $this->getParam('unzipCommand');

    if(!isset($unzipCmd))
      {
      throw new Zend_Exception('Must pass unzipCommand parameter');
      }
    $this->Setting->setConfig('unzipCommand', $unzipCmd, $this->moduleName);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }
  } // end class
