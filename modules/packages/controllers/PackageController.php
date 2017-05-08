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

/** package controller */
class Packages_PackageController extends Packages_AppController
{
    public $_models = array('Community', 'Item');
    public $_moduleModels = array('Application', 'Package', 'Project');

    /**
     * Render the page for editing package metadata on a specific package.
     * Write permission on the item is required.
     */
    public function manageAction()
    {
        $packageId = $this->getParam('id');
        if (!isset($packageId)) {
            throw new Zend_Exception('Must specify an id parameter');
        }

        $validator = new Zend_Validate_Digits();
        if (!$validator->isValid($packageId)) {
            throw new Zend_Exception('Must specify an id parameter');
        }

        $package = $this->Packages_Package->load($packageId);
        if (!$package) {
            throw new Zend_Exception('Invalid package id');
        }
        if (!$this->Item->policyCheck($package->getItem(), $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Write permission required');
        }

        $this->view->package = $package;
    }

    /**
     * Called when the edit form is submitted.
     */
    public function saveAction()
    {
        $packageId = $this->getParam('packageId');
        if (!isset($packageId)) {
            throw new Zend_Exception('Must set packageId parameter');
        }
        $package = $this->Packages_Package->load($packageId);
        if (!isset($package)) {
            throw new Zend_Exception('Invalid packageId parameter');
        }
        if (!$this->Item->policyCheck($package->getItem(), $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Write permission required');
        }
        $this->disableLayout();
        $this->disableView();

        $package->setOs($this->getParam('os'));
        $package->setArch($this->getParam('arch'));
        $package->setRevision($this->getParam('revision'));
        $package->setSubmissiontype($this->getParam('submissiontype'));
        $package->setPackagetype($this->getParam('packagetype'));
        $package->setProductname($this->getParam('productname'));
        $package->setCodebase($this->getParam('codebase'));
        $package->setCheckoutdate($this->getParam('checkoutdate'));
        $package->setRelease($this->getParam('release'));
        $this->Packages_Package->save($package);

        echo JsonComponent::encode(array('message' => 'Changes saved', 'status' => 'ok'));
    }

    /**
     * Ajax action for getting the latest package of each package type for the given os and arch.
     *
     * @param os The os to match on
     * @param arch The arch to match on
     * @param applicationId The application id
     * @return (json) - The latest uploaded package of each installer type for the given os, arch, and application
     */
    public function latestAction()
    {
        $this->disableLayout();
        $this->disableView();

        $os = $this->getParam('os');
        $arch = $this->getParam('arch');
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

        $latest = $this->Packages_Package->getLatestOfEachPackageType($application, $os, $arch);
        $filtered = array();
        foreach ($latest as $package) {
            if ($this->Item->policyCheck($package->getItem(), $this->userSession->Dao, MIDAS_POLICY_READ)
            ) {
                $sizestr = UtilityComponent::formatSize($package->getItem()->getSizebytes());
                $filtered[] = array_merge($package->toArray(), array('size_formatted' => $sizestr));
            }
        }
        echo JsonComponent::encode($filtered);
    }
}
