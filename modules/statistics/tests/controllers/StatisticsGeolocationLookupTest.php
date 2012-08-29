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

/** test statistics geolocation behavior */
class StatisticsGeolocationLookupTest extends ControllerTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'statistics');
    $this->enabledModules = array('scheduler', 'statistics');
    $this->_models = array('User');

    parent::setUp();
    }

  /**
   * Test geolocation
   */
  public function testGeolocationTask()
    {
    // We need the module constants to be imported, and the notifier to be set
    require_once(BASE_PATH.'/modules/scheduler/constant/module.php');
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

    // Use the admin user so we can configure the module
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    $jobModel = MidasLoader::loadModel('Job', 'scheduler');
    $jobLogModel = MidasLoader::loadModel('JobLog', 'scheduler');
    $ipLocationModel = MidasLoader::loadModel('IpLocation', 'statistics');
    $ipLocations = $ipLocationModel->getAllUnlocated();

    $this->assertEquals(count($ipLocations), 1);
    $this->assertEquals($ipLocations[0]->getLatitude(), '');
    $this->assertEquals($ipLocations[0]->getLongitude(), '');
    $ip = $ipLocations[0]->getIp();

    $this->request->setMethod('POST');
    $this->params['ipinfodbapikey'] = '1234';
    $this->params['submitConfig'] = 'true';
    $this->dispatchUrI('/statistics/config/index', $userDao);

    // Assert that the task is now scheduled
    $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
    $this->assertEquals(count($jobs), 1);
    $job = $jobs[0];
    $params = json_decode($job->getParams());
    $this->assertEquals($params->apikey, '1234');

    // Make it so the job will fire on the next scheduler run
    $job->setFireTime(date('Y-m-j', strtotime('-1 day')).' 1:00:00');
    $jobModel->save($job);

    // Run the scheduler
    $this->resetAll();
    $this->dispatchUrI('/scheduler/run/index', $userDao);

    // Assert that geolocation task was performed
    $ipLocations = $ipLocationModel->getAllUnlocated();
    $this->assertEquals(count($ipLocations), 0);
    $ipLocation = $ipLocationModel->getByIp($ip);
    $this->assertTrue($ipLocation != false);
    $this->assertEquals($ipLocation->getLatitude(), '0');
    $this->assertEquals($ipLocation->getLongitude(), '0');

    // We should have a message in the log now
    $logs = $job->getLogs();
    $this->assertEquals(count($logs), 1);
    $this->assertTrue(strpos($logs[0]->getLog(), 'IpInfoDb lookup failed') !== false);

    print "Log = ".$logs[0]->getLog();
    }
}
