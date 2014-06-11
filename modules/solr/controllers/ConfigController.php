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
 * Module configuration controller
 */
class Solr_ConfigController extends Solr_AppController
  {
  public $_models = array('Setting');
  public $_moduleForms = array('Config');

  /** render config form */
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $solrHost = $this->Setting->getValueByName('solrHost', $this->moduleName);
    $solrPort = $this->Setting->getValueByName('solrPort', $this->moduleName);
    $solrWebroot = $this->Setting->getValueByName('solrWebroot', $this->moduleName);

    $configForm = $this->ModuleForm->Config->createConfigForm();
    $formArray = $this->getFormAsArray($configForm);
    if($solrHost === null)
      {
      $formArray['host']->setValue('localhost');
      $this->view->saved = false;
      }
    else
      {
      $formArray['host']->setValue($solrHost);
      $this->view->saved = true;
      }

    if($solrPort === null)
      {
      $formArray['port']->setValue('8983');
      }
    else
      {
      $formArray['port']->setValue($solrPort);
      }

    if($solrWebroot === null)
      {
      $formArray['webroot']->setValue('/solr/');
      }
    else
      {
      $formArray['webroot']->setValue($solrWebroot);
      }
    $this->view->configForm = $formArray;
    }

  /**
   * Submit module configuration parameters (ajax)
   * @param host Solr host (typically localhost)
   * @param port Port Solr is listening on (defaults is 8983)
   * @param webroot Solr webroot (typically /solr/)
   */
  function submitAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();

    $solrHost = $this->_getParam('host');
    $solrPort = $this->_getParam('port');
    $solrWebroot = $this->_getParam('webroot');

    $this->Setting->setConfig('solrHost', $solrHost, $this->moduleName);
    $this->Setting->setConfig('solrPort', $solrPort, $this->moduleName);
    $this->Setting->setConfig('solrWebroot', $solrWebroot, $this->moduleName);
    echo JsonComponent::encode(array(true, 'Changes saved'));
    }
  } // end class
