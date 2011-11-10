<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** Config controller */
class Remoteprocessing_ConfigController extends Remoteprocessing_AppController
{
  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Date');

  /** download remote script */
  function downloadAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }

    $this->disableLayout();
    $this->disableView();
    Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
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
    }

  /** index action*/
  function indexAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }

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