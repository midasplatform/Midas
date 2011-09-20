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
abstract class Validation_DashboardModelBase extends Validation_AppModel
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
                             'parent_column' => 'trainingfolder_id',
                             'child_column' => 'folder_id'),
        'testing' =>  array('type' => MIDAS_MANY_TO_ONE,
                            'model' => 'Folder',
                            'parent_column' => 'testingfolder_id',
                            'child_column' => 'folder_id'),
        'results' =>  array('type' => MIDAS_MANY_TO_MANY,
                           'model' => 'Folder',
                           'table' => 'validation_dashboard2folder',
                           'parent_column' => 'dashboard_id',
                           'child_column' => 'folder_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  /**
   * Set the truth folder of the dashboard
   * @return void
   */
  function setTruth($dashboard, $folder)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dasboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $dashboard->setTruthfolderId($folder->getKey());
    parent::save($dashboard);
    }

  /**
   * Set the training folder of the dashboard
   * @return void
   */
  function setTraining($dashboard, $folder)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dasboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $dashboard->setTrainingfolderId($folder->getKey());
    parent::save($dashboard);
    }

  /**
   * Set the testing folder of the dashboard
   * @return void
   */
  function setTesting($dashboard, $folder)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dasboard.");
      }
    if(!$folder instanceof FolderDao)
      {
      throw new Zend_Exception("Should be a folder.");
      }
    $dashboard->setTestingfolderId($folder->getKey());
    parent::save($dashboard);
    }

  /**
   * Verify that the testing, truth, and training folders contain the same
   * number of items and that the item names correspond.
   * @return boolean true if valid, false if invalid
   */
  function checkConsistency($dashboard)
    {
    if(!$dashboard instanceof Validation_DashboardDao)
      {
      throw new Zend_Exception("Should be a dashboard.");
      }
    $testing = $dashboard->getTesting();
    $training = $dashboard->getTraining();
    $truth = $dashboard->getTruth();

    $testingItems = $testing->getItems();
    $trainingItems = $training->getItems();
    $truthItems = $truth->getItems();
    
    if( count($testingItems) == count($trainingItems) &&
        count($trainingItems) == count($truthItems) )
      {
      $fn = create_function('$first,$second',
                            'return strcmp( $first->getName(),
                                            $second->getName());');
      usort($testingItems, $fn);
      usort($trainingItems, $fn);
      usort($truthItems, $fn);
      for( $i = 0; $i < count($truthItems); ++$i )
        {
        $nameDifference = 0;
        if( strcmp( $testingItems[$i]->getName(),
                    $trainingItems[$i]->getName() ) != 0 )
          {
          return false;
          }
        if( strcmp( $trainingItems[$i]->getName(),
                    $truthItems[$i]->getName() ) != 0 )
          {
          return false;
          }
        }
      return true;
      }
    else
      {
      return false;
      }
    }

  abstract function addResult($dashboard, $folder);

} // end class Validation_DashboardModelBase
