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

/** test cleanup module behavior */
class CleanupPerformTest extends ControllerTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'cleanup');
    $this->enabledModules = array('scheduler', 'cleanup');
    $this->_models = array('User');

    parent::setUp();
    }

  /** test cleanup behavior */
  public function testCleanup()
    {
    require_once(BASE_PATH.'/modules/scheduler/constant/module.php');
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

    // admin user
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadModel('Job', 'scheduler');
    $jobLogModel = $modelLoader->loadModel('JobLog', 'scheduler');

    $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
    $this->assertTrue(empty($jobs));

    $this->request->setMethod('POST');
    $this->params['olderThan'] = '2'; //2 day limit on keeping files
    $this->params['submitConfig'] = 'true';
    $this->dispatchUrI('/cleanup/config/index', $userDao);

    $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
    $this->assertEquals(count($jobs), 1);
    }
}
