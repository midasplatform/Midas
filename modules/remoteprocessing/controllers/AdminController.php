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

/** Admin controller for the remoteprocessing module. */
class Remoteprocessing_AdminController extends Remoteprocessing_AppController
{
    /** @var array */
    public $_models = array('Setting');

    /** Index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->pageTitle = 'Remote Processing Module Configuration';
        $form = new Remoteprocessing_Form_Admin();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($form->isValid($data)) {
                $values = $form->getValues();

                foreach ($values as $key => $value) {
                    if ($key !== 'csrf' && !is_null($value)) {
                        $this->Setting->setConfig($key, $value, $this->moduleName);
                    }
                }
            }

            $form->populate($data);
        } else {
            $elements = $form->getElements();

            foreach ($elements as $element) {
                $name = $element->getName();

                if ($name !== 'csrf' && $name !== 'submit') {
                    $value = $this->Setting->getValueByName($name, $this->moduleName);

                    if (!is_null($value)) {
                        $form->setDefault($name, $value);
                    }
                }
            }
        }

        $this->view->form = $form;
        session_start();
    }

    /** Download remote script action. */
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
}
