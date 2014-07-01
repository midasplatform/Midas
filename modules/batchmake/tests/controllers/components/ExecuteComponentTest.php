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

require_once BASE_PATH.'/modules/batchmake/tests/controllers/BatchmakeControllerTest.php';

/**
 * ExecuteComponent tests
 */
class ExecuteComponentTest extends BatchmakeControllerTest
  {

  protected $executeComponent;
  protected $kwBatchmakeComponent;
  protected $cwd;
  protected $testTmpDir = 'batchmake/tests';
  protected $tmpItem;

  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User', 'Item', 'Bitstream');
    $this->_components = array('Export', 'Upload');
    $this->enabledModules = array('batchmake', 'api');
    $this->cwd = getcwd();
    parent::setUp();
    if(!isset($this->executeComponent))
      {
      require_once BASE_PATH.'/modules/batchmake/controllers/components/ExecuteComponent.php';
      $this->executeComponent = new Batchmake_ExecuteComponent();
      }
    if(!isset($this->kwBatchmakeComponent))
      {
      require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
      require_once BASE_PATH.'/modules/batchmake/tests/library/ExecutorMock.php';
      $executor = new Batchmake_ExecutorMock();
      $this->kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent($this->setupAndGetConfig(), $executor);
      }

    // upload a file for testing of the exports
    $tmpDir = $this->getTempDirectory() . '/' .$this->testTmpDir;
    // use UploadComponent
    require_once BASE_PATH.'/core/controllers/components/UploadComponent.php';
    $uploadComponent = new UploadComponent();
    // notifier is required in ItemRevisionModelBase::addBitstream, create a fake one
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    // create a directory for testing the export component
    if(file_exists($tmpDir))
      {
      if(!KWUtils::recursiveRemoveDirectory($tmpDir))
        {
        throw new Zend_Exception($tmpDir." already exists and cannot be deleted.");
        }
      }
    if(!mkdir($tmpDir))
      {
      throw new Zend_Exception("Cannot create directory: ".$tmpDir);
      }
    chmod($tmpDir, 0777);

    // upload an item to user1's public folder
    $user1_public_path = $tmpDir.'/public.file';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_public_path);
    $user1_public_fh = fopen($user1_public_path, "a+");
    fwrite($user1_public_fh, "content:user1_public");
    fclose($user1_public_fh);
    $user1_pulic_file_size = filesize($user1_public_path);
    $user1_public_filename = 'public.file';
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $user1_public_parent = '1001';
    $license = 0;
    $this->tmpItem = $uploadComponent->createUploadedItem($userDao, $user1_public_filename,
                                          $user1_public_path, $user1_public_parent, $license, '', true);

    parent::setUp();
    }

  /** clean up after tests */
  public function tearDown()
    {
    // remove the temporary tests dir
    $tmpDir = $this->getTempDirectory() . '/' . $this->testTmpDir;

    KWUtils::recursiveRemoveDirectory($tmpDir);
    $this->Item->delete($this->tmpItem);
    // change the current dir back to the saved cwd after each test
    chdir($this->cwd);
    }

  /**
   * tests that a list of items gets correctly exported to a batchmake work dir.
   */
  public function testExportItemsToWorkDataDir()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $this->kwBatchmakeComponent->createTask($userDao);
    $workDir = $taskDao->getWorkDir();
    $itemIds = array($this->tmpItem->getItemId());
    $this->executeComponent->exportItemsToWorkDataDir($userDao, $taskDao, $itemIds);
    // check that the exported file now exists on the file system
    $this->assertTrue(file_exists($workDir.'/data/'.$itemIds[0].'/'.$this->tmpItem->getName()), "exported item should exist");
    }

  /**
   * tests that a list of items gets correctly exported to a batchmake work dir,
   * and returns a path to the exported items.
   */
  public function testExportSingleBitstreamItemsToWorkDataDir()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $this->kwBatchmakeComponent->createTask($userDao);
    $workDir = $taskDao->getWorkDir();
    $items = array($this->tmpItem->getName() => $this->tmpItem->getItemId());
    $exportedItems = $this->executeComponent->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $items);
    // check that the exported file now exists on the file system
    $expectedExportPath = $workDir.'/data/'.$this->tmpItem->getItemId().'/'.$this->tmpItem->getName();
    $this->assertTrue(file_exists($expectedExportPath), "exported item should exist");
    // ensure that the name of the returned item is the same as the passed in
    foreach($exportedItems as $itemName => $exportedPath)
      {
      $this->assertEquals($itemName, $this->tmpItem->getName(), "Item names should be equal.");
      $this->assertEquals($exportedPath, $expectedExportPath, "Expected export path does not match actual.");
      }
    }

  /**
   * tests that a python config file will be generated in the batchmake work dir
   */
  public function testGeneratePythonConfigParams()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $this->kwBatchmakeComponent->createTask($userDao);
    $workDir = $taskDao->getWorkDir();

    // need to falsify a webroot for this test
    $fakeWebroot = "fake_web_root";
    Zend_Registry::set('webroot', $fakeWebroot);
    // need to falisfy a HTTP_HOST
    $_SERVER['HTTP_HOST'] = 'localhost';
    // need to create a web api-key for the user
    $userapiModel = MidasLoader::loadModel('Userapi');
    $userapiModel->createDefaultApiKey($userDao);

    $this->executeComponent->generatePythonConfigParams($taskDao, $userDao);
    $expectedConfigPath = $workDir.'/config.cfg';
    $this->assertTrue(file_exists($expectedConfigPath), "exported config file should exist");

    $this->executeComponent->generatePythonConfigParams($taskDao, $userDao, 'admin');
    $expectedConfigPath = $workDir.'/adminconfig.cfg';
    $this->assertTrue(file_exists($expectedConfigPath), "exported admin config file should exist");

    // unfalsify.  retruth?
    Zend_Registry::set('webroot', null);
    unset($_SERVER['HTTP_HOST']);
    }

  /**
   * tests that a python config file will be generated in the batchmake work dir
   */
  public function testGenerateBatchmakeConfig()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $this->kwBatchmakeComponent->createTask($userDao);
    $workDir = $taskDao->getWorkDir();

    $configScriptStem = 'test';
    $this->executeComponent->generateBatchmakeConfig($taskDao, array('prop' => 'val'), 'script.py', 'dagscript.py', $configScriptStem);
    $expectedConfigPath = $workDir.'/'.$configScriptStem . ".config.bms";
    $this->assertTrue(file_exists($expectedConfigPath), "batchmake config file should exist");
    }
  } // end class
