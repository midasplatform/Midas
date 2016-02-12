<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Index controller for the validation module. */
class Validation_IndexController extends Validation_AppController
{
    public $_models = array('User', 'Item', 'Folder');
    public $_moduleModels = array('Dashboard');
    public $_daos = array('Item', 'Folder');
    public $_moduleDaos = array('Dashboard');
    public $_components = array('Utility');
    public $_moduleComponents = array();
    public $_forms = array();
    public $_moduleForms = array();

    /**
     * Index Action (first action when we access the application).
     */
    public function init()
    {
    }

    /** index action */
    public function indexAction()
    {
        $dashboards = $this->Validation_Dashboard->getAll();
        $this->view->nSubmissions = 0;
        foreach ($dashboards as $dashboard) {
            $this->view->nSubmissions += count($dashboard->getResults());
        }
        $this->view->dashboards = $dashboards;
        $this->view->nDashboards = count($dashboards);
    }
}
