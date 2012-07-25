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
/** test slicerpackages_package model*/
class PackageModelTest extends DatabaseTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'packages'); // module dataset
    $this->enabledModules = array('packages');
    $this->_models = array('Folder', 'Item');
    $this->_daos = array('Folder', 'Item');
    parent::setUp();
    }

  /** testGetAll*/
  public function testGetAll()
    {
    $packageModel = MidasLoader::loadModel('Package', 'packages');
    $daos = $packageModel->getAll();
    $this->assertEquals(1, count($daos));
    }
}
