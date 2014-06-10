<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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

  public abstract function getAllByProjectId($projectId);
  public abstract function getAllReleases($application);
  public abstract function getDistinctPlatforms($application);

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
