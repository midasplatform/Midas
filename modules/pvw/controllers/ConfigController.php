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

/** Config controller for the instance-wide module settings */
class Pvw_ConfigController extends Pvw_AppController
{
  public $_moduleForms = array('Config');
  public $_models = array('Setting');

  /**
   * Renders the module configuration page
   */
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $configForm = $this->ModuleForm->Config->createConfigForm();
    $formArray = $this->getFormAsArray($configForm);

    $pvpython = $this->Setting->getValueByName('pvpython', $this->moduleName);
    $staticcontent = $this->Setting->getValueByName('staticcontent', $this->moduleName);
    $ports = $this->Setting->getValueByName('ports', $this->moduleName);
    if(!$ports)
      {
      $ports = '9000,9001';
      }
    $formArray['pvpython']->setValue($pvpython);
    $formArray['staticcontent']->setValue($staticcontent);
    $formArray['ports']->setValue($ports);

    $this->view->configForm = $formArray;
    }

  /**
   * Handles submission of the module configuration form
   */
  function submitAction()
    {
    $this->disableLayout();
    $this->disableView();

    $pvpython = $this->_getParam('pvpython');
    $staticcontent = $this->_getParam('staticcontent');
    $ports = $this->_getParam('ports');
    $this->Setting->setConfig('pvpython', $pvpython, $this->moduleName);
    $this->Setting->setConfig('staticcontent', $staticcontent, $this->moduleName);
    $this->Setting->setConfig('ports', $ports, $this->moduleName);
    echo JsonComponent::encode(array(true, 'Changes saved'));
    }

}//end class
