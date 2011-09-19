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
 * Base Model for validation dashboards. These consist of testing, training,
 * and truth data along with a simple name a description. Additionally, a
 * metric for assessing the quality of submitted results as well as a relation
 * for linking submitted result folders to the dashboard are defined. Finally,
 * an owner is specified as the user in charge of running the dashboard. Only
 * he or a site administrator may modify the dashboard in any way.
 */
class Validation_DashboardModelBase extends Validation_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'validation_dashboard';
    $this->_key = 'dashboard_id';

    $this->_mainData = array(
        'dashboard_id' =>  array('type' => MIDAS_DATA),
        'owner_id' => array('type' => MIDAS_DATA),
        'name' => array('type' => MIDAS_DATA),
        'description' => array('type' => MIDAS_DATA),
        'truthfolder_id' => array('type' => MIDAS_DATA),
        'testingfolder_id' => array('type' => MIDAS_DATA),
        'trainingfolder_id' => array('type' => MIDAS_DATA),
        'metric_id' => array('type' => MIDAS_DATA),
        'owner' =>  array('type' => MIDAS_MANY_TO_ONE,
                          'model' => 'User',
                          'parent_column' => 'owner_id',
                          'child_column' => 'user_id'),
        'truth' =>  array('type' => MIDAS_MANY_TO_ONE,
                          'model' => 'Folder',
                          'parent_column' => 'truthfolder_id',
                          'child_column' => 'folder_id'),
        'training' =>  array('type' => MIDAS_MANY_TO_ONE,
                             'model' => 'Folder',
                             'parent_column' => 'testingfolder_id',
                             'child_column' => 'folder_id'),
        'testing' =>  array('type' => MIDAS_MANY_TO_ONE,
                            'model' => 'Folder',
                            'parent_column' => 'trainingfolder_id',
                            'child_column' => 'folder_id'),
        'result' =>  array('type' => MIDAS_MANY_TO_MANY,
                           'model' => 'Folder',
                           'table' => 'validation_dashboard2folder',
                           'parent_column' => 'dashboard_id',
                           'child_column' => 'folder_id'),
      );
    $this->initialize(); // required
    } // end __construct()

} // end class Validation_DashboardModelBase
