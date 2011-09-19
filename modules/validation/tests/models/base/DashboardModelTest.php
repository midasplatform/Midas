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

}
