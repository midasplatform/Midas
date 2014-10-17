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
    public function downloadAction()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $this->requireAdminPrivileges();

        $this->disableLayout();
        $this->disableView();
        ob_start();
        $zip = new ZipStream('RemoteScript.zip');

        $file = BASE_PATH.'/modules/remoteprocessing/remotescript/main.py';
        $zip->add_file_from_path(basename($file), $file);
        $file = BASE_PATH.'/modules/remoteprocessing/remotescript/config.cfg';
        $zip->add_file_from_path(basename($file), $file);

        $dirname = BASE_PATH.'/modules/remoteprocessing/remotescript/pydas/';
        $dir = opendir($dirname);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && !is_dir($dirname.$file)) {
                $zip->add_file_from_path('pydas/'.basename($dirname.$file), $dirname.$file);
            }
        }

        $zip->finish();
        exit();
    }

    /** index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $options = array('allowModifications' => true);
        if (file_exists(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini')) {
            $config = new Zend_Config_Ini(
                LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini',
                'global',
                $options
            );
        } else {
            $config = new Zend_Config_Ini(
                BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini',
                'global',
                $options
            );
        }

        $configForm = $this->ModuleForm->Config->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);
        if (empty($config->securitykey)) {
            $config->securitykey = uniqid();

            $writer = new Zend_Config_Writer_Ini();
            $writer->setConfig($config);
            $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
            $writer->write();
        }
        $formArray['securitykey']->setValue($config->securitykey);
        if (empty($config->showbutton)) {
            $formArray['showbutton']->setValue(true);
        } else {
            $formArray['showbutton']->setValue($config->showbutton);
        }
        $this->view->configForm = $formArray;

        if ($this->_request->isPost()) {
            $this->disableLayout();
            $this->disableView();

            $submitConfig = $this->getParam('submitConfig');
            if (isset($submitConfig)) {
                $config->securitykey = $this->getParam('securitykey');
                $config->showbutton = $this->getParam('showbutton');
                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
                $writer->write();
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
        }
    }
}
