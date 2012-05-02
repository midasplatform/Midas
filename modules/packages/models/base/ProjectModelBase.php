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
 * Project Model Base
 */
abstract class Packages_ProjectModelBase extends Packages_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'packages_project';
    $this->_key = 'project_id';
    $this->_mainData = array(
        'project_id' => array('type' => MIDAS_DATA),
        'community_id' => array('type' => MIDAS_DATA),
        'enabled' => array('type' => MIDAS_DATA),
        'community' => array('type' => MIDAS_MANY_TO_ONE,
                             'model' => 'Community',
                             'parent_column' => 'community_id',
                             'child_column' => 'community_id'),
        'applications' => array('type' => MIDAS_ONE_TO_MANY,
                                'model' => 'Application',
                                'module' => 'packages',
                                'parent_column' => 'project_id',
                                'child_column' => 'project_id')
      );
    $this->initialize();
    }

  public abstract function getAllEnabled();
  public abstract function getByCommunityId($communityId);

  /**
   * Enable or disable the community as a package-hosting project
   * @param community The community dao
   * @param $value Boolean value for whether or not the community is a package-hosting project
   */
  public function setEnabled($community, $value)
    {
    $project = $this->getByCommunityId($community->getKey());
    if(!$project)
      {
      $this->loadDaoClass('ProjectDao', $this->moduleName);
      $project = new Packages_ProjectDao();
      $project->setCommunityId($community->getKey());
      }
    $project->setEnabled($value ? 1 : 0);
    $this->save($project);
    }
}
