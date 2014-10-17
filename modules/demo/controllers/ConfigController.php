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

/** Config controller for the demo module */
class Demo_ConfigController extends Demo_AppController
{
    public $_models = array('Setting');
    public $_moduleComponents = array('Demo');
    public $_moduleForms = array('Config');

    /** Require admin privileges excluding demo admin */
    public function requireNonDemoAdminPrivileges()
    {
        $this->requireAdminPrivileges();
        if ($this->userSession->Dao->getEmail() === MIDAS_DEMO_ADMIN_EMAIL) {
            throw new Zend_Exception(MIDAS_ADMIN_PRIVILEGES_REQUIRED, 403);
        }
    }

    /** Index action */
    public function indexAction()
    {
        $this->requireNonDemoAdminPrivileges();
        $configForm = $this->ModuleForm->Config->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);
        $param = 'enabled';
        $value = $this->Setting->getValueByName($param, $this->moduleName);
        $formArray[$param]->setValue($value);
        $this->view->configForm = $formArray;
    }

    /** Reset action */
    public function resetAction()
    {
        $this->requireNonDemoAdminPrivileges();
        $this->ModuleComponent->Demo->reset();
        $this->_helper->Redirector->gotoSimple('index');
    }

    /** Submit action */
    public function submitAction()
    {
        $this->requireNonDemoAdminPrivileges();
        $this->disableLayout();
        $this->disableView();
        $param = 'enabled';
        $value = $this->getParam($param);
        $this->Setting->setConfig($param, $value, $this->moduleName);
        echo JsonComponent::encode(array(true, 'Changes saved'));
    }
}
