<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
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
    $jobFile = $this->loadData('Job', 'default', 'remoteprocessing', 'remoteprocessing');

    $jobModel->addItemRelation($jobFile[0], $itemsFile[1]);
    $jobs = $jobModel->getRelatedJob($itemsFile[1]);
    $this->assertEquals(1, count($jobs));
    }
  }
