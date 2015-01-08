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
 * Admin controller for the pvw module.
 *
 * @property Pvw_InstanceModel $Pvw_Instance
 */
class Pvw_AdminController extends Pvw_AppController
{
    /** @var array */
    public $_models = array('Setting');

    /** @var array */
    public $_moduleComponents = array('Paraview');

    /** @var array */
    public $_moduleModels = array('Instance');

    /** Index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->pageTitle = 'ParaViewWeb Module Configuration';
        $form = new Pvw_Form_Admin();

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

    /** Render the admin status tab. */
    public function statusAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();

        $instances = $this->Pvw_Instance->getAll();
        $this->view->instances = array();

        foreach ($instances as $instance) {
            $this->view->instances[] = array(
                'dao' => $instance,
                'status' => $this->ModuleComponent->Paraview->isRunning($instance),
            );
        }
    }
}
