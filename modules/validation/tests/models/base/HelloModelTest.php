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
class HelloModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'helloworld'); // module dataset
    $this->enabledModules = array('helloworld');
    parent::setUp();
    }

  /** testGetAll*/
  public function testGetAll()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $helloModel = $modelLoad->loadModel('Hello', 'helloworld');
    
    $daos = $helloModel->getAll();
    $this->assertEquals(1, count($daos));
    }

 
  }
