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
/** AssetstoreModelTest*/
class AssetstoreModelTest extends DatabaseTestCase
  {
  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Assetstore');
    $this->_daos = array();
    parent::setUp();
    }



  /**
   * helper function, will save an Assetstore, using either the passed in
   * assetstoreDao or creating a new one, will set the three parameter
   * values before saving.
   * @param type $name
   * @param type $path
   * @param type $type
   * @param AssetstoreDao $assetstoreDao
   * @return AssetstoreDao
   */
  protected function validSaveTestcase($name, $path, $type, $assetstoreDao = null)
    {
    if(empty($assetstoreDao))
      {
      $assetstoreDao = new AssetstoreDao();
      }
    $assetstoreDao->setName($name);
    $assetstoreDao->setPath($path);
    $assetstoreDao->setType($type);
    $this->Assetstore->save($assetstoreDao);
    return $assetstoreDao;
    }

  /**
   * helper function, attempts to save an Assetstore, using either the passed in
   * assetstoreDao or creating a new one, will set the three parameter
   * values before saving, expects that the save will fail, and asserts
   * that an exception has been thrown by the Assetstore Model.
   * @param type $name
   * @param type $path
   * @param type $type
   * @param AssetstoreDao $assetstoreDao
   */
  protected function invalidSaveTestcase($name, $path, $type, $assetstoreDao = null)
    {
    if(empty($assetstoreDao))
      {
      $assetstoreDao = new AssetstoreDao();
      }
    $assetstoreDao->setName($name);
    $assetstoreDao->setPath($path);
    $assetstoreDao->setType($type);
    try
      {
      $this->Assetstore->save($assetstoreDao);
      $this->fail('Expected an exception saving assetstoreDao, but did not get one.');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }
    }

  /** test the save method*/
  public function testSave()
    {

    // expect that there will be one assetstore to start out, Default
    // this will probably need to be expanded once we start adding implementations
    // for the other two types of assetstore
    // for now, a local Default assetstore is expected
    $all = $this->Assetstore->getAll();
    $this->assertEquals(sizeof($all), 1);
    $default = $all[0];
    $this->assertEquals('Default', $default->getName());
    // Correct Create Test


    // create new ones with a different path from existing ones

    $assetstoreDao1 = $this->validSaveTestcase('test_assetstore_1', '/testassetstore1/path', 0);
    $assetstoreDao2 = $this->validSaveTestcase('test_assetstore_2', '/testassetstore2/path', 1);
    $assetstoreDao3 = $this->validSaveTestcase('test_assetstore_3', '/testassetstore3/path', 2);

    // make sure one got saved
    $found = $this->Assetstore->findBy('name', 'test_assetstore_3');
    $this->assertNotEmpty($found);
    $savedDao = $found[0];

    $this->assertTrue($this->Assetstore->compareDao($assetstoreDao3, $savedDao, true));

    // Incorrect Create Tests

    // create a new one with empty path, should fail
    $this->invalidSaveTestcase('test_assetstore_4', '', '0');

    // create a new one with same path as existing ones, should fail
    $this->invalidSaveTestcase('test_assetstore_4', '/testassetstore1/path', '0');

    // create a new one with empty name, should fail
    $this->invalidSaveTestcase('', '/testassetstore4/path', '0');

    // create a new one with same path as existing ones, should fail
    $this->invalidSaveTestcase('test_assetstore_3', '/testassetstore4/path', '0');

    // Incorrect Edit&Save Tests

    // take existing, try to save with empty name
    $savedName = $assetstoreDao1->getName();
    $this->invalidSaveTestcase('', $assetstoreDao1->getPath(), $assetstoreDao1->getType(), $assetstoreDao1);
    $assetstoreDao1->setName($savedName);

    // take existing, try to save with empty path
    $savedPath = $assetstoreDao1->getPath();
    $this->invalidSaveTestcase($assetstoreDao1->getName(), '', $assetstoreDao1->getType(), $assetstoreDao1);
    $assetstoreDao1->setPath($savedPath);

    // take existing, try to save with a colliding name
    $savedName = $assetstoreDao1->getName();
    $this->invalidSaveTestcase($assetstoreDao2->getName(), $assetstoreDao1->getPath(), $assetstoreDao1->getType(), $assetstoreDao1);
    $assetstoreDao1->setName($savedName);

    // take existing, try to save with a colliding path
    $savedPath = $assetstoreDao1->getPath();
    $this->invalidSaveTestcase($assetstoreDao1->getName(), $assetstoreDao2->getPath(), $assetstoreDao1->getType(), $assetstoreDao1);
    $assetstoreDao1->setPath($savedPath);

    // Correct Edit&Save Tests

    // take existing, try to save with a non-colliding name
    $savedName = $assetstoreDao1->getName();
    $newName = 'noncollidingname1';
    $this->validSaveTestcase($newName, $assetstoreDao1->getPath(), $assetstoreDao1->getType(), $assetstoreDao1);

    // check that the new value is saved
    $found = $this->Assetstore->findBy('name', $newName);
    $this->assertNotEmpty($found);
    $foundDao = $found[0];

    $this->assertTrue($this->Assetstore->compareDao($foundDao, $assetstoreDao1, true));
    $this->assertEquals($foundDao->getName(), $newName);
    $assetstoreDao1->setName($savedName);


    // take existing, try to save with a non-colliding path
    $savedPath = $assetstoreDao1->getPath();
    $newPath = 'noncolliding/path';
    $this->validSaveTestcase($assetstoreDao1->getName(), $newPath, $assetstoreDao1->getType(), $assetstoreDao1);

    // check that the new value is saved
    $found = $this->Assetstore->findBy('path', $newPath);
    $this->assertNotEmpty($found);
    $foundDao = $found[0];

    $this->assertTrue($this->Assetstore->compareDao($foundDao, $assetstoreDao1, true));
    $this->assertEquals($foundDao->getPath(), $newPath);
    $assetstoreDao1->setPath($savedPath);

    // delete the newly added ones to clean up
    $this->Assetstore->delete($assetstoreDao1);
    $this->Assetstore->delete($assetstoreDao2);
    $this->Assetstore->delete($assetstoreDao3);
    }

  }
