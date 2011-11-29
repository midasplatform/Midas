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

/** test scheduled geolocation task behavior */
class OfflineGeolocationTest extends ControllerTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->enabledModules = array('scheduler', 'statistics');
    $this->_models = array('User');

    parent::setUp();
    }

  /**
   * Tests that configuring the statistics module adds the geolocation
   * lookup task.
   */
  public function testOfflineGeolocation()
    {
    // We need the module constants to be imported, and the notifier to be set
    require_once(BASE_PATH.'/modules/scheduler/constant/module.php');
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

    // Use the admin user so we can configure the module
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadModel('Job', 'scheduler');

    $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
    if(!empty($jobs))
      {
      foreach($jobs as $job)
        {
        $jobModel->delete($job);
        }
      }
    $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
    $this->assertTrue(empty($jobs));

    // Configure the module
    $this->request->setMethod('POST');
    $this->params['submitConfig'] = 'true';
    $this->dispatchUrI('/statistics/config/index', $userDao);

    // Assert that the task is now scheduled
    $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
    $this->assertEquals(count($jobs), 1);
    $job = $jobs[0];
    $this->assertEmpty(json_decode($job->getParams()));
    $this->assertEquals($job->getTimeInterval(), 3600);
    }
}
