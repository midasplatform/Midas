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
/** test hello model*/
class JobModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'remoteprocessing'); // module dataset
    $this->enabledModules = array('remoteprocessing');
    parent::setUp();
    }

  /** test getRelatedJob($item)*/
  public function testGetRelatedJob()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $itemModel = $modelLoad->loadModel('Item');

    $item = $itemModel->load(1);

    $jobs = $jobModel->getRelatedJob($item);
    $this->assertEquals(1, count($jobs));
    }

  /** test getBy*/
  public function testGetBy()
    {
    include BASE_PATH.'/modules/remoteprocessing/constant/module.php';
    $modelLoad = new MIDAS_ModelLoader();
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');

    $jobs = $jobModel->getBy(MIDAS_REMOTEPROCESSING_OS_WINDOWS, '', '2000-10-26 22:32:58', MIDAS_REMOTEPROCESSING_STATUS_WAIT);
    $this->assertEquals(1, count($jobs));

    $jobs = $jobModel->getBy(MIDAS_REMOTEPROCESSING_OS_LINUX, '', '2000-10-26 22:32:58', MIDAS_REMOTEPROCESSING_STATUS_WAIT);
    $this->assertEquals(0, count($jobs));

    $jobs = $jobModel->getBy(MIDAS_REMOTEPROCESSING_OS_WINDOWS, '', '2100-10-26 22:32:58', MIDAS_REMOTEPROCESSING_STATUS_WAIT);
    $this->assertEquals(0, count($jobs));
    }

  /** test addItemRelation*/
  public function testAddItemRelation()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $itemsFile = $this->loadData('Item', 'default');
    $jobFile = $this->loadData('Job', 'default', 'remoteprocessing');

    $jobModel->addItemRelation($jobFile[0], $itemsFile[1]);
    $jobs = $jobModel->getRelatedJob($itemsFile[1]);
    $this->assertEquals(1, count($jobs));
    }
  }
