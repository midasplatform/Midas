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

/** test ScalarResultModel */
class ScalarResultModelTest extends DatabaseTestCase
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

  /** testGetAll */
  public function testGetAll()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $scalarResultModel = $modelLoad->loadModel('ScalarResult', 'validation');
    $daos = $scalarResultModel->getAll();
    $this->assertEquals(1, count($daos));
    }

  /** testGetSetValue */
  public function testGetSetValue()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $scalarResultModel = $modelLoad->loadModel('ScalarResult', 'validation');
    $daos = $scalarResultModel->getAll();
    $sr = $daos[0];

    $folder = new FolderDao();
    $folder->setName('result');
    $folder->setDescription('result');
    $folder->setParentId(-1);
    $this->Folder->save($folder);

    $trainingItem = new ItemDao();
    $trainingItem->setName('img00.mha');
    $trainingItem->setDescription('training img 00');
    $trainingItem->setType(0);
    $this->Item->save($trainingItem);

    $scalarResultModel->setFolder($sr, $folder);
    $scalarResultModel->setItem($sr, $trainingItem);
    $sr->setValue(90.009);
    $scalarResultModel->save($sr);
    $daos = $scalarResultModel->getAll();
    $sr = $daos[0];
    $this->assertEquals(90.009, $sr->getValue());
    }
}
