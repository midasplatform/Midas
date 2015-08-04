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

/** packages application controller */
class Packages_ApplicationController extends Packages_AppController
{
    public $_components = array('Breadcrumb');
    public $_models = array('Community');
    public $_moduleDaos = array('Application');
    public $_moduleModels = array('Application', 'Package', 'Project');

    /**
     * Create a new application under a project.
     *
     * @param projectId Project id
     * @param name Name of the application
     * @param description Description of the application
     * @throws Zend_Exception
     */
    public function createAction()
    {
        $projectId = $this->getParam('projectId');
        if (!isset($projectId)) {
            throw new Zend_Exception('Must specify a projectId parameter');
        }
        $name = $this->getParam('name');
        $description = $this->getParam('description');

        if (!isset($name)) {
            throw new Zend_Exception('Parameter "name" must be set');
        }
        if (!isset($description)) {
            throw new Zend_Exception('Parameter "description" must be set');
        }
        $project = $this->Packages_Project->load($projectId);
        if (!$project) {
            throw new Zend_Exception('Invalid projectId');
        }

        if (!$this->Community->policyCheck($project->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Must be community administrator to add a new application');
        }
        $this->disableLayout();
        $this->disableView();

        $application = new Packages_ApplicationDao();
        $application->setName($name);
        $application->setDescription($description);
        $application->setProjectId($projectId);
        $this->Packages_Application->save($application);
        $this->redirect('/packages/application/view?applicationId='.$application->getKey());
    }

    /**
     * Edit an application's name and description.
     * @throws Zend_Exception
     */
    public function editAction()
    {
        $applicationId = $this->getParam('applicationId');
        if (!isset($applicationId)) {
            throw new Zend_Exception('Must specify an applicationId parameter');
        }
        $name = $this->getParam('name');
        $description = $this->getParam('description');

        if (!isset($name)) {
            throw new Zend_Exception('Parameter "name" must be set');
        }
        if (!isset($description)) {
            throw new Zend_Exception('Parameter "description" must be set');
        }
        $application = $this->Packages_Application->load($applicationId);
        if (!$application) {
            throw new Zend_Exception('Invalid applicationId');
        }

        if (!$this->Community->policyCheck(
            $application->getProject()->getCommunity(),
            $this->userSession->Dao,
            MIDAS_POLICY_ADMIN
        )
        ) {
            throw new Zend_Exception('Must be community administrator to edit an application');
        }
        $this->disableLayout();
        $this->disableView();

        $application->setName($name);
        $application->setDescription($description);
        $this->Packages_Application->save($application);

        echo JsonComponent::encode(
            array(
                'status' => 'ok',
                'message' => 'Changes saved',
                'name' => $application->getName(),
                'description' => $application->getDescription(),
            )
        );
    }

    /**
     * View the release packages for an application.
     * @throws Zend_Exception
     */
    public function viewAction()
    {
        $applicationId = $this->getParam('applicationId');
        if (!isset($applicationId)) {
            throw new Zend_Exception('Must specify an applicationId parameter');
        }
        $application = $this->Packages_Application->load($applicationId);
        if (!$application) {
            throw new Zend_Exception('Invalid applicationId', 404);
        }
        $comm = $application->getProject()->getCommunity();
        if (!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('You do not have read permissions on the project');
        }
        $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

        $breadcrumbs = array();
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $comm->getName().' Packages',
            'icon' => $this->view->moduleWebroot.'/public/images/package.png',
            'href' => $this->view->webroot.'/community/'.$comm->getKey().'#Packages',
        );
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $application->getName(),
            'icon' => $this->view->moduleWebroot.'/public/images/application_terminal.png',
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);

        $this->view->application = $application;
        $this->view->json['applicationId'] = $application->getKey();

        $this->view->releases = $this->Packages_Application->getAllReleases($application);
        usort($this->view->releases, array($this, '_releaseSort'));

        if (count($this->view->releases) > 0) {
            $this->view->json['openRelease'] = $this->view->releases[0];
            $this->view->json['latestReleasePackages'] = $this->Packages_Package->get(
                array(
                    'application_id' => $application->getKey(),
                    'release' => $this->view->releases[0],
                )
            );
        }
    }

    /**
     * Delete an application (ajax). All package and extension records for this application will be deleted,
     * but their underlying items will remain in place. Requires admin privileges on the application's
     * project community.
     *
     * @param applicationId The id of the application to delete
     * @throws Zend_Exception
     */
    public function deleteAction()
    {
        $applicationId = $this->getParam('applicationId');
        if (!$applicationId) {
            throw new Zend_Exception('Must pass applicationId parameter');
        }
        $application = $this->Packages_Application->load($applicationId);
        $community = $application->getProject()->getCommunity();
        if (!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Must be project community administrator to delete applications');
        }
        $this->disableLayout();
        $this->disableView();
        $this->Packages_Application->delete($application);
        $this->redirect('/community/'.$community->getKey().'#Packages');
    }

    /**
     * View for latest builds.
     * @throws Zend_Exception
     */
    public function latestAction()
    {
        $applicationId = $this->getParam('applicationId');
        if (!isset($applicationId)) {
            throw new Zend_Exception('Must specify an applicationId parameter');
        }
        $application = $this->Packages_Application->load($applicationId);
        if (!$application) {
            throw new Zend_Exception('Invalid applicationId', 404);
        }
        $comm = $application->getProject()->getCommunity();
        if (!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('You do not have read permissions on the project');
        }
        $breadcrumbs = array();
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $comm->getName().' Packages',
            'icon' => $this->view->moduleWebroot.'/public/images/package.png',
            'href' => $this->view->webroot.'/community/'.$comm->getKey().'#Packages',
        );
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => $application->getName(),
            'icon' => $this->view->moduleWebroot.'/public/images/application_terminal.png',
            'href' => $this->view->webroot.'/packages/application/view?applicationId='.$application->getKey(),
        );
        $breadcrumbs[] = array(
            'type' => 'custom',
            'text' => 'Latest Nightly Packages',
            'icon' => $this->view->coreWebroot.'/public/images/icons/time.png',
        );
        $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);

        $this->view->platforms = $this->Packages_Application->getDistinctPlatforms($application);
        $this->view->json['applicationId'] = $application->getKey();
    }

    /**
     * Helper function for sorting releases (desc).
     *
     * @param string $a
     * @param string $b
     * @return int
     */
    private function _releaseSort($a, $b)
    {
        $a_tok = explode('.', $a);
        $b_tok = explode('.', $b);

        $a_count = count($a_tok);
        $b_count = count($b_tok);
        for ($i = 0; $i < $a_count && $i < $b_count; ++$i) {
            $a_v = (int) $a_tok[$i];
            $b_v = (int) $b_tok[$i];
            if ($a_v > $b_v) {
                return -1;
            } elseif ($a_v < $b_v) {
                return 1;
            }
        }

        if ($a_count == $b_count) {
            return 0;
        }
        if ($a_count < $b_count) {
            return 1;
        }

        return -1;
    }

    /** Ajax action to return a list of packages for a given application and release. */
    public function getpackagesAction()
    {
        $this->disableLayout();
        $this->disableView();
        $releasePackages = $this->Packages_Package->get(
            array('application_id' => $this->getParam('applicationId'), 'release' => $this->getParam('release'))
        );
        echo JsonComponent::encode($releasePackages);
    }
}
