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

  /**
   * Tests the behavior of the cleanup module
   * 1. Configures a cleanup cutoff as the admin user
   * 2. Creates some content in the temp dir
   * 3. Runs the cleanup task via the scheduler
   *
   * CAUTION: If this test is run on an instance where the scheduler is being
   * run concurrently, it could cause a race condition that might break
   * this test, so don't do that :)
   */
  public function testCleanup()
    {
    // We need the module constants to be imported, and the notifier to be set
    require_once(BASE_PATH.'/modules/scheduler/constant/module.php');
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

    $tempDir = $this->getTempDirectory();

    // Use the admin user so we can configure the module
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadModel('Job', 'scheduler');
    $jobLogModel = $modelLoader->loadModel('JobLog', 'scheduler');

    $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
    if(!empty($jobs))
      {
      foreach($jobs as $job)
        {
        $jobModel->delete($job);
        }
      }
    $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
    $this->assertTrue(empty($jobs));

    $this->request->setMethod('POST');
    $this->params['olderThan'] = '5'; //5 day limit on keeping files
    $this->params['submitConfig'] = 'true';
    $this->dispatchUrI('/cleanup/config/index', $userDao);

    // Assert that the task is now scheduled
    $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
    $this->assertEquals(count($jobs), 1);
    $job = $jobs[0];
    $params = json_decode($job->getParams());
    $this->assertEquals($params->days, '5');
    $this->assertEquals($tempDir, $params->tempDirectory);

    // Create some stuff to be cleaned up in the temp dir
    if(!file_exists($tempDir.'/145/272'))
      {
      $this->assertTrue(mkdir($tempDir.'/145/272', 0700, true));
      }
    if(!file_exists($tempDir.'/72/398'))
      {
      $this->assertTrue(mkdir($tempDir.'/72/398', 0700, true));
      }
    if(!file_exists($tempDir.'/1/79'))
      {
      $this->assertTrue(mkdir($tempDir.'/1/79', 0700, true));
      }
    if(!file_exists($tempDir.'/shouldNotBeRemoved'))
      {
      $this->assertTrue(mkdir($tempDir.'/shouldNotBeRemoved', 0700, true));
      }
    $this->assertTrue(touch($tempDir.'/145/272/shouldBeKept.txt', strtotime('-2 days')));
    $this->assertTrue(touch($tempDir.'/72/398/shouldBeDeleted.txt', strtotime('-8 days')));

    // Make it so the job will fire on the next scheduler run
    $job->setFireTime(date('Y-m-j', strtotime('-1 day')).' 1:00:00');
    $jobModel->save($job);

    // Run the scheduler
    $this->resetAll();
    $this->dispatchUrI('/scheduler/run/index', $userDao);

    // Make sure only files older than the cutoff were removed
    $this->assertTrue(file_exists($tempDir.'/145/272/shouldBeKept.txt'));
    $this->assertFalse(file_exists($tempDir.'/72/398/shouldBeDeleted.txt'));

    // Make sure numeric empty dirs are removed, but other ones are kept
    $this->assertFalse(file_exists($tempDir.'/1/79'));
    $this->assertTrue(file_exists($tempDir.'/shouldNotBeRemoved'));
    rmdir($tempDir.'/shouldNotBeRemoved');

    // Make sure the log entry was created for this run
    $logs = $job->getLogs();
    $this->assertTrue(count($logs) > 0);
    $this->assertNotEmpty($logs[0]->getLog());

    echo 'Log from task: '.$logs[0]->getLog();
    }
}
