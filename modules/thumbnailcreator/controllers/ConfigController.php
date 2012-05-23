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

    if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();

    $formArray = $this->getFormAsArray($configForm);
    $formArray['imagemagick']->setValue($applicationConfig['global']['imagemagick']);
    $this->view->configForm = $formArray;

    $thumbnailerForm = $this->ModuleForm->Config->createThumbnailerForm();
    $thumbnailerFormArray = $this->getFormAsArray($thumbnailerForm);
    $thumbnailerFormArray['useThumbnailer']->setValue($applicationConfig['global']['useThumbnailer']);
    $thumbnailerFormArray['thumbnailer']->setValue($applicationConfig['global']['thumbnailer']);
    $this->view->thumbnailerForm = $thumbnailerFormArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      $submitThumbnailer = $this->_getParam('submitThumbnailer');
      if(isset($submitConfig) || isset($submitThumbnailer))
        {
        if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini"))
          {
          rename(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old");
          }
        if(isset($submitConfig))
          {
          $applicationConfig['global']['imagemagick'] = $this->_getParam('imagemagick');
          }
        if(isset($submitThumbnailer))
          {
          $applicationConfig['global']['useThumbnailer'] = $this->_getParam('useThumbnailer');
          $applicationConfig['global']['thumbnailer'] = $this->_getParam('thumbnailer');
          }
        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }

}//end class
