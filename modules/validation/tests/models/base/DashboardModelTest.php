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
class DashboardModelTest extends DatabaseTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'validation'); // module dataset
    $this->enabledModules = array('validation');
    $this->_models = array('Folder', 'Item');
    $this->_daos = array('Folder', 'Item');
    parent::setUp();
    }

  /** testGetAll*/
  public function testGetAll()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $this->assertEquals(1, count($daos));
    }

  /**
   * test the fetching of results
   */
  public function testGetResults()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $this->assertEquals(2, count($dao->getResults()));
    }

  /**
   * test the fetching of Testing, Training, and Truth
   */
  public function testGetTestingTrainingAndTruth()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $testing = $dao->getTesting();
    $training = $dao->getTraining();
    $truth = $dao->getTruth();
    $this->assertNotEquals(false, $testing);
    $this->assertNotEquals(false, $training);
    $this->assertNotEquals(false, $truth);
    }

  public function testVerifyConsistency()
    {
    // Create training, testing, and truth folders
    $testingFolder = new FolderDao();
    $testingFolder->setName('testing');
    $testingFolder->setDescription('testing');
    $testingFolder->setParentId(-1);
    $this->Folder->save($testingFolder);
    $trainingFolder = new FolderDao();
    $trainingFolder->setName('training');
    $trainingFolder->setDescription('training');
    $trainingFolder->setParentId(-1);
    $this->Folder->save($trainingFolder);
    $truthFolder = new FolderDao();
    $truthFolder->setName('truth');
    $truthFolder->setDescription('truth');
    $truthFolder->setParentId(-1);
    $this->Folder->save($truthFolder);

    // Add items to the folders
    for ($i = 0; $i < 3; ++$i)
      {
      $trainingItem = new ItemDao();
      $testingItem = new ItemDao();
      $truthItem = new ItemDao();
      $trainingItem->setName('img0'.$i.'.mha');
      $testingItem->setName('img0'.$i.'.mha');
      $truthItem->setName('img0'.$i.'.mha');
      $trainingItem->setDescription('training img '.$i);
      $testingItem->setDescription('testing img '.$i);
      $truthItem->setDescription('truth img '.$i);
      $trainingItem->setType(0);
      $testingItem->setType(0);
      $truthItem->setType(0);
      $this->Item->save($trainingItem);
      $this->Item->save($testingItem);
      $this->Item->save($truthItem);
      $this->Folder->addItem($trainingFolder, $trainingItem);
      $this->Folder->addItem($testingFolder, $testingItem);
      $this->Folder->addItem($truthFolder, $truthItem);
      }

    // Acquire the dashboard from the database
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];

    // Add testing, training, and truth to the dashboard
    $dashboardModel->setTraining($dao, $trainingFolder);
    $dashboardModel->setTesting($dao, $testingFolder);
    $dashboardModel->setTruth($dao, $truthFolder);
    
    // Reload the dashboard and check it for consistency
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $this->assertEquals(true, $dashboardModel->checkConsistency($dao));
    }
}
