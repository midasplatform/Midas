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
 * This controller is used to manage global configuration of the tango
 * dashboard module.
 */
class Googleauth_ConfigController extends Googleauth_AppController
{
    public $_models = array('Setting');

    /** Renders the config view */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->clientId = $this->Setting->getValueByName('client_id', $this->moduleName);
        $this->view->clientSecret = $this->Setting->getValueByName('client_secret', $this->moduleName);
    }

    /** XHR endpoint to save config values */
    public function submitAction()
    {
        $this->requireAdminPrivileges();

        $this->disableLayout();
        $this->disableView();

        $params = array('client_id', 'client_secret');
        foreach ($params as $param) {
            $value = $this->getParam($param);
            $this->Setting->setConfig($param, $value, $this->moduleName);
        }

        echo JsonComponent::encode(array(true, 'Changes saved'));
    }
}
