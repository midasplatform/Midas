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
/** ItemmetricModelTest*/
class ItemmetricModelTest extends DatabaseTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->enabledModules = array('batchmake');
    parent::setUp();
    $this->setupDatabase(array('default'), 'batchmake'); // module dataset
    }

  /** Test that ItemmetricModel::createTask($userDao) works */
  public function testCreateItemmetric()
    {
    $usersFile = $this->loadData('User', 'default', '', 'batchmake');

    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $user1Dao = $usersFile[0];


    // create an itemmetric
    $metricName = 'metrictest1';
    $bmsName = 'metrictest1.bms';
    $itemmetric1Dao = $itemmetricModel->createItemmetric($metricName, $bmsName);
    // check the dao is correct
    $this->assertNotEmpty($itemmetric1Dao);
    $this->assertTrue($itemmetric1Dao instanceof Batchmake_ItemmetricDao);
    // check that what we passed in is what we got out
    $this->assertEquals($metricName, $itemmetric1Dao->getMetricName());
    $this->assertEquals($bmsName, $itemmetric1Dao->getBmsName());
    // now try to retrieve it by key
    $key = $itemmetric1Dao->getKey();
    $dupDao = $itemmetricModel->load($key);
    $this->assertTrue($itemmetricModel->compareDao($itemmetric1Dao, $dupDao));

    // now try creating another one with the same name, see that it fails
    try
      {
      $itemmetricDaoDup = $itemmetricModel->createItemmetric($metricName, $bmsName);
      $this->fail('Expected an exception for '.$metricName.', but did not get one.');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }

    // be sure we can create one with a different name
    $metricName2 = 'metrictest2';
    $bmsName2 = 'metrictest2.bms';
    $itemmetricDao2 = $itemmetricModel->createItemmetric($metricName2, $bmsName2);
    // check the dao is correct
    $this->assertNotEmpty($itemmetricDao2);
    $this->assertTrue($itemmetricDao2 instanceof Batchmake_ItemmetricDao);
    // check that what we passed in is what we got out
    $this->assertEquals($metricName2, $itemmetricDao2->getMetricName());
    $this->assertEquals($bmsName2, $itemmetricDao2->getBmsName());
    // now try to retrieve it by key
    $key = $itemmetricDao2->getKey();
    $dupDao = $itemmetricModel->load($key);
    $this->assertTrue($itemmetricModel->compareDao($itemmetricDao2, $dupDao));
    }

  /**
    * tests getAll abstract function
    */
  public function testGetAll()
    {
    $itemmetricFileDaos = $this->loadData('Itemmetric', 'default', 'batchmake', 'batchmake');

    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $modelDaos = $itemmetricModel->getAll();

    // now check that each of the itemmetrics in the file are loaded as daos
    foreach($itemmetricFileDaos as $fileDao)
      {
      $found = false;
      foreach($modelDaos as $modelDao)
        {
        if($itemmetricModel->compareDao($modelDao, $fileDao))
          {
          $found = true;
          break;
          }
        }
      if(!$found)
        {
        $this->fail("Did not find a testdata itemmetric , with getAll");
        }
      }
    }


  }
