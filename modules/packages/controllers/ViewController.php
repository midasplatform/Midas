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

/** packages view controller */
class Packages_ViewController extends Packages_AppController
{
    public $_models = array('Community');
    public $_moduleModels = array('Application', 'Project');

    /**
     * View for the Packages tab within the community view.
     * Shows a list of all applications and their links
     */
    public function projectAction()
    {
        $this->disableLayout();
        $projectId = $this->getParam('projectId');
        if (!isset($projectId)) {
            throw new Zend_Exception('Must specify a projectId parameter');
        }
        $this->view->project = $this->Packages_Project->load($projectId);
        $this->view->community = $this->view->project->getCommunity();
        $this->view->applications = $this->Packages_Application->getAllByProjectId($projectId);

        $this->view->isAdmin = $this->Community->policyCheck(
            $this->view->community,
            $this->userSession->Dao,
            MIDAS_POLICY_ADMIN
        );
        $this->view->json['projectId'] = $projectId;
    }
}
