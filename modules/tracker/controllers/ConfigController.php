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

/** Module configuration controller (for admin use) */
class Tracker_ConfigController extends Tracker_AppController
{
    public $_models = array('Setting');
    public $_components = array('Breadcrumb');

    /**
     * Show configuration view
     */
    public function indexAction()
    {
        $this->requireAdminPrivileges();
        $this->view->tempScalarTtl = $this->Setting->getValueByName('tempScalarTtl', $this->moduleName);
        if (!$this->view->tempScalarTtl) {
            $this->view->tempScalarTtl = 24; // default to 24 hours
        }

        $breadcrumbs = array();
        $breadcrumbs[] = array('type' => 'moduleList');
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => 'Tracker Dashboard Configuration',
            'icon' => $this->view->moduleWebroot.'/public/images/chart_line.png',
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);
    }

    /**
     * Submit configuration settings
     */
    public function submitAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();
        $this->disableView();

        $tempScalarTtl = $this->getParam('tempScalarTtl');

        $this->Setting->setConfig('tempScalarTtl', $tempScalarTtl, $this->moduleName);

        echo JsonComponent::encode(array('message' => 'Changes saved', 'status' => 'ok'));
    }
}
