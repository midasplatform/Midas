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

/** Config controller */
class Remoteprocessing_ConfigController extends Remoteprocessing_AppController
{
  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Date');

  /** download remote script */
  function downloadAction()
    {
    while(ob_get_level() > 0)
      {
      ob_end_clean();
      }
    $this->requireAdminPrivileges();

    $this->disableLayout();
    $this->disableView();
    Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
    ob_start();
    $zip = new ZipStream('RemoteScript.zip');

    $file = BASE_PATH.'/modules/remoteprocessing/remotescript/main.py';
    $zip->add_file_from_path(basename($file), $file);
    $file = BASE_PATH.'/modules/remoteprocessing/remotescript/config.cfg';
    $zip->add_file_from_path(basename($file), $file);

    $dirname = BASE_PATH.'/modules/remoteprocessing/remotescript/pydas/';
    $dir = opendir($dirname);
    while($file = readdir($dir))
      {
      if($file != '.' && $file != '..' && !is_dir($dirname.$file))
        {
        $zip->add_file_from_path('pydas/'.basename($dirname.$file), $dirname.$file);
        }
      }

    $zip->finish();
    exit();
    }

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
    if(empty($applicationConfig['global']['securitykey']))
      {
      $applicationConfig['global']['securitykey'] = uniqid();
      $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", $applicationConfig);
      }
    $formArray['securitykey']->setValue($applicationConfig['global']['securitykey']);

    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->disableView();

      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/".$this->moduleName.".local.ini"))
          {
          rename(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", BASE_PATH."/core/configs/".$this->moduleName.".local.ini.old");
          }
        $applicationConfig['global']['securitykey'] = $this->_getParam('securitykey');

        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/".$this->moduleName.".local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }
}//end class