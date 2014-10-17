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

/** Config controller for the instance-wide module settings */
class Pvw_ConfigController extends Pvw_AppController
{
    public $_moduleForms = array('Config');
    public $_models = array('Setting');
    public $_moduleModels = array('Instance');
    public $_moduleComponents = array('Paraview');

    /**
     * Renders the module configuration page
     */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $configForm = $this->ModuleForm->Config->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);

        $pvpython = $this->Setting->getValueByName('pvpython', $this->moduleName);
        $ports = $this->Setting->getValueByName('ports', $this->moduleName);
        $displayEnv = $this->Setting->getValueByName('displayEnv', $this->moduleName);
        if (!$ports) {
            $ports = '9000,9001';
        }
        $formArray['pvpython']->setValue($pvpython);
        $formArray['ports']->setValue($ports);
        $formArray['displayEnv']->setValue($displayEnv);

        $this->view->configForm = $formArray;
    }

    /**
     * Handles submission of the module configuration form
     */
    public function submitAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();
        $this->disableView();

        $pvpython = $this->getParam('pvpython');
        $ports = $this->getParam('ports');
        $displayEnv = $this->getParam('displayEnv');
        $this->Setting->setConfig('pvpython', $pvpython, $this->moduleName);
        $this->Setting->setConfig('ports', $ports, $this->moduleName);
        $this->Setting->setConfig('displayEnv', $displayEnv, $this->moduleName);
        echo JsonComponent::encode(array(true, 'Changes saved'));
    }

    /**
     * Render the admin status tab
     */
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
