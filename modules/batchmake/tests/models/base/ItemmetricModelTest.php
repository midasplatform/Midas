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
/** ItemmetricModelTest*/
class ItemmetricModelTest extends DatabaseTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->enabledModules = array('batchmake');
    parent::setUp();
    $this->setupDatabase(array('default'), 'batchmake'); // module dataset
    $db = Zend_Registry::get('dbAdapter');
    $configDatabase = Zend_Registry::get('configDatabase' );
    if($configDatabase->database->adapter == 'PDO_PGSQL')
      {
      $db->query("SELECT setval('batchmake_itemmetric_itemmetric_id_seq', (SELECT MAX(itemmetric_id) FROM batchmake_itemmetric)+1);");
      }
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
