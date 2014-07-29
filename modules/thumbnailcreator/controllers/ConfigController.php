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
 * Thumbnailcreator module configuration
 */
class Thumbnailcreator_ConfigController extends Thumbnailcreator_AppController
  {
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
    $formArray['imagemagick']->setValue($config->imagemagick);
    $this->view->configForm = $formArray;

    $thumbnailerForm = $this->ModuleForm->Config->createThumbnailerForm();
    $thumbnailerFormArray = $this->getFormAsArray($thumbnailerForm);
    $thumbnailerFormArray['thumbnailer']->setValue($config->thumbnailer);
    $thumbnailerFormArray['useThumbnailer']->setValue($config->useThumbnailer);
    $this->view->thumbnailerForm = $thumbnailerFormArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->getParam('submitConfig');
      $submitThumbnailer = $this->getParam('submitThumbnailer');
      if(isset($submitConfig) || isset($submitThumbnailer))
        {
        if(isset($submitConfig))
          {
          $config->imagemagick = $this->getParam('imagemagick');
          }
        if(isset($submitThumbnailer))
          {
          $config->thumbnailer = $this->getParam('thumbnailer');
          $config->useThumbnailer = $this->getParam('useThumbnailer');
          }
        $writer = new Zend_Config_Writer_Ini();
        $writer->setConfig($config);
        $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
        $writer->write();
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }
  }
