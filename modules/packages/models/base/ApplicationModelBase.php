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
 * Application Model Base
 */
abstract class Packages_ApplicationModelBase extends Packages_AppModel
  {
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'packages_application';
    $this->_key = 'application_id';
    $this->_mainData = array(
        'application_id' => array('type' => MIDAS_DATA),
        'project_id' => array('type' => MIDAS_DATA),
        'name' => array('type' => MIDAS_DATA),
        'description' => array('type' => MIDAS_DATA),
        'project' => array('type' => MIDAS_MANY_TO_ONE,
                           'model' => 'Project',
                           'module' => 'packages',
                           'parent_column' => 'project_id',
                           'child_column' => 'project_id')
      );
    $this->initialize();
    }

  abstract public function getAllByProjectId($projectId);
  abstract public function getAllReleases($application);
  abstract public function getDistinctPlatforms($application);

  /**
   * Override the save function
   */
  public function save($application)
    {
    // Strip out unsafe html tags from description
    $application->setDescription(UtilityComponent::filterHtmlTags($application->getDescription()));
    parent::save($application);
    }
  }
