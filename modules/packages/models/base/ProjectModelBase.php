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
 * Project Model Base
 */
abstract class Packages_ProjectModelBase extends Packages_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'packages_project';
        $this->_key = 'project_id';
        $this->_mainData = array(
            'project_id' => array('type' => MIDAS_DATA),
            'community_id' => array('type' => MIDAS_DATA),
            'enabled' => array('type' => MIDAS_DATA),
            'community' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'Community',
                'parent_column' => 'community_id',
                'child_column' => 'community_id',
            ),
            'applications' => array(
                'type' => MIDAS_ONE_TO_MANY,
                'model' => 'Application',
                'module' => 'packages',
                'parent_column' => 'project_id',
                'child_column' => 'project_id',
            ),
        );
        $this->initialize();
    }

    /** Get all enabled */
    abstract public function getAllEnabled();

    /** Get by community id */
    abstract public function getByCommunityId($communityId);

    /**
     * Enable or disable the community as a package-hosting project
     *
     * @param community The community dao
     * @param $value Boolean value for whether or not the community is a package-hosting project
     */
    public function setEnabled($community, $value)
    {
        $project = $this->getByCommunityId($community->getKey());
        if (!$project) {
            $project = MidasLoader::newDao('ProjectDao', $this->moduleName);
            $project->setCommunityId($community->getKey());
        }
        $project->setEnabled($value ? 1 : 0);
        $this->save($project);
    }

    /**
     * Delete the project (deletes all applications within the project as well)
     */
    public function delete($project)
    {
        $applicationModel = MidasLoader::loadModel('Application', 'packages');
        $applications = $project->getApplications();
        foreach ($applications as $application) {
            $applicationModel->delete($application);
        }
        parent::delete($project);
    }
}
