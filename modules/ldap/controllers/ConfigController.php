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

/** Config controller for the ldap module */
class Ldap_ConfigController extends Ldap_AppController
  {
  public $_moduleForms = array('Config');
  public $_components = array('Utility');

  /** Index action */
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
    $formArray['autoAddUnknownUser']->setValue($config->ldap->autoAddUnknownUser);
    $formArray['backup']->setValue($config->ldap->backup);
    $formArray['basedn']->setValue($config->ldap->basedn);
    $formArray['bindn']->setValue($config->ldap->bindn);
    $formArray['bindpw']->setValue($config->ldap->bindpw);
    $formArray['hostname']->setValue($config->ldap->hostname);
    $formArray['port']->setValue($config->ldap->port);
    $formArray['protocolVersion']->setValue($config->ldap->protocolVersion);
    $formArray['proxyBasedn']->setValue($config->ldap->proxyBasedn);
    $formArray['proxyPassword']->setValue($config->ldap->proxyPassword);
    $formArray['search']->setValue($config->ldap->search);
    $formArray['useActiveDirectory']->setValue($config->ldap->useActiveDirectory);
    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->getParam('submitConfig');
      if(isset($submitConfig))
        {
        $config->ldap->autoAddUnknownUser = $this->getParam('autoAddUnknownUser');
        $config->ldap->backup = $this->getParam('backup');
        $config->ldap->basedn = $this->getParam('basedn');
        $config->ldap->bindn = $this->getParam('bindn');
        $config->ldap->hostname = $this->getParam('hostname');
        $config->ldap->port = $this->getParam('port');
        $config->ldap->protocolVersion = $this->getParam('protocolVersion');
        $config->ldap->proxyBasedn = $this->getParam('proxyBasedn');
        $config->ldap->search = $this->getParam('search');
        $config->ldap->useActiveDirectory = $this->getParam('useActiveDirectory');
        $bindpw = $this->getParam('bindpw');
        if(!empty($bindpw))
          {
          $config->ldap->bindpw = $bindpw;
          }
        $proxyPassword = $this->getParam('proxyPassword');
        if(!empty($proxyPassword))
          {
          $config->ldap->proxyPassword = $proxyPassword;
          }

        $writer = new Zend_Config_Writer_Ini();
        $writer->setConfig($config);
        $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
        $writer->write();
        echo JsonComponent::encode(array(true, 'Changes saved'));
        }
      }
    }
  }
