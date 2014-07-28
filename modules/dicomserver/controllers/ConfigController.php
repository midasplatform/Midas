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

/** Config controller for the dicomserver module */
class Dicomserver_ConfigController extends Dicomserver_AppController
  {
  public $_moduleComponents = array('Server');
  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Date');

  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $options = array('allowModifications' => true);
    if(file_exists(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini'))
      {
      $config = new Zend_Config_Ini(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini', 'global', $options);
      }
    else
      {
      $config = new Zend_Config_Ini(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini', 'global', $options);
      }

    $configForm = $this->ModuleForm->Config->createConfigForm();
    $formArray = $this->getFormAsArray($configForm);
    $formArray['dcm2xml']->setValue($config->dcm2xml);
    $formArray['dcmqridx']->setValue($config->dcmqridx);
    $formArray['dcmqrscp_port']->setValue($config->dcmqrscp_port);
    $formArray['dcmqrscp']->setValue($config->dcmqrscp);
    $formArray['peer_aes']->setValue($config->peer_aes);
    $formArray['pydas_dest_folder']->setValue($config->pydas_dest_folder);
    $formArray['server_ae_title']->setValue($config->server_ae_title);
    $formArray['storescp_port']->setValue($config->storescp_port);
    $formArray['storescp_study_timeout']->setValue($config->storescp_study_timeout);
    $formArray['storescp']->setValue($config->storescp);
    if(empty($config->receptiondir))
      {
      $formArray['receptiondir']->setValue($this->ModuleComponent->Server->getDefaultReceptionDir());
      }
    else
      {
      $formArray['receptiondir']->setValue($config->receptiondir);
      }
    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->getParam('submitConfig');
      if(isset($submitConfig))
        {
        $config->dcm2xml = $this->getParam('dcm2xml');
        $config->dcmqridx = $this->getParam('dcmqridx');
        $config->dcmqrscp_port = $this->getParam('dcmqrscp_port');
        $config->dcmqrscp = $this->getParam('dcmqrscp');
        $config->peer_aes = $this->getParam('peer_aes');
        $config->pydas_dest_folder = $this->getParam('pydas_dest_folder');
        $config->receptiondir = $this->getParam('receptiondir');
        $config->server_ae_title = $this->getParam('server_ae_title');
        $config->storescp_port = $this->getParam('storescp_port');
        $config->storescp_study_timeout = $this->getParam('storescp_study_timeout');
        $config->storescp = $this->getParam('storescp');

        $writer = new Zend_Config_Writer_Ini();
        $writer->setConfig($config);
        $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
        $writer->write();
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    $dashboard_array = $this->ModuleComponent->Server->isDICOMServerWorking();
    // has shown status separately; remove it from the dashboard to avoid redundancy
    unset($dashboard_array['Status']);
    $this->view->dashboard = $dashboard_array;
    }
  } // end class
