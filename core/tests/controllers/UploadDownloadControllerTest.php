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
/**
 * Tests uploading and downloading of files
 */
class UploadDownloadControllerTest extends ControllerTestCase
  {
  /** init tests*/
  public function setUp()
    {
    $this->_models = array('User', 'Feed', 'Assetstore', 'Item');
    $this->_daos = array('User', 'Assetstore');
    parent::setUp();
    }

  /** test UploadController::GethttpuploadoffsetAction*/
  function testGethttpuploadoffsetAction()
    {
    $this->setupDatabase(array('default'));

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $dir = $this->getTempDirectory().$userDao->getUserId().'/'.$userDao->getPrivatefolderId();
    $identifier = $dir.'/httpupload.png';
    if(!file_exists($dir))
      {
      mkdir($dir, 0700, true);
      }
    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = 'upload/gethttpuploadoffset/?uploadUniqueIdentifier='.$userDao->getUserId().'/'.$userDao->getPrivatefolderId().'/httpupload.png&testingmode=1';
    $this->dispatchUrI($page, $userDao);

    $content = $this->getBody();
    if(strpos($content, '[OK]') === false)
      {
      $this->fail();
      }
    if(strpos($content, '[ERROR]') !== false)
      {
      $this->fail();
      }
    }

  /** test UploadController::gethttpuploaduniqueidentifierAction*/
  function testGethttpuploaduniqueidentifierAction()
    {
    $this->setupDatabase(array('default'));

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $identifier = $this->getTempDirectory().'httpupload.png';
    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = 'upload/gethttpuploaduniqueidentifier/?filename=httpupload.png&testingmode=1';
    $this->dispatchUrI($page, $userDao);
    $content = $this->getBody();
    if(strpos($content, '[OK]') === false)
      {
      $this->fail();
      }
    if(strpos($content, '[ERROR]') !== false)
      {
      $this->fail();
      }
    }

  /** test UploadController::processjavaupload*/
  function testProcessjavauploadAction()
    {
    $this->setupDatabase(array('default'));

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $subdir = $userDao->getUserId().'/'.$userDao->getPrivatefolderId();
    $dir = $this->getTempDirectory().$subdir;
    $fileBase = BASE_PATH.'/tests/testfiles/search.png';
    $file = $this->getTempDirectory().'testing_file.png';
    $identifier = $dir.'/httpupload.png';

    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy($fileBase, $file);
    $ident = fopen($identifier, 'x+');
    fwrite($ident, ' ');
    fclose($ident);
    chmod($identifier, 0777);

    $params = 'testingmode=1&filename=search.png&localinput='.$file.'&length='.filesize($file).'&uploadUniqueIdentifier='.$subdir.'/httpupload.png';
    $page = 'upload/processjavaupload/?'.$params;
    $this->dispatchUrI($page, $userDao);

    $search = $this->Item->getItemsFromSearch('search.png', $userDao);
    if(empty($search))
      {
      $this->fail('Unable to find item');
      }
    $this->setupDatabase(array('default'));
    }

  /** test UploadController::simpleuploadAction*/
  function testSimpleuploadAction()
    {
    $this->setupDatabase(array('default'));
    $this->dispatchUrI("/upload/simpleupload", null, true);

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $folder = $userDao->getPublicFolder();
    $this->dispatchUrI('/upload/simpleupload?parent='.$folder->getKey(), $userDao, false);
    $this->assertContains('id="destinationId" value="'.$folder->getKey(), $this->getBody());

    $this->resetAll();
    $folder = $userDao->getPrivateFolder();
    $this->dispatchUrI('/upload/simpleupload', $userDao, false);
    $this->assertContains('id="destinationId" value="'.$folder->getKey(), $this->getBody());
    }

  /** test UploadController::revision */
  function testRevision()
    {
    $this->setupDatabase(array('default'));
    $this->dispatchUrI("/upload/revision", null, true);

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $itemsFile = $this->loadData('Item', 'default');
    $itemDao = $this->Item->load($itemsFile[1]->getKey());

    $this->dispatchUrI("/upload/revision?itemId=".$itemDao->getKey(), $userDao);
    }

  /** test UploadController::savelink */
  function testSavelinkAction()
    {
    $this->setupDatabase(array('default'));
    $this->dispatchUrI("/upload/savelink", null, true);

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $itemsFile = $this->loadData('Item', 'default');
    $itemDao = $this->Item->load($itemsFile[1]->getKey());

    $this->params = array();
    $this->params['parent'] = $userDao->getPublicFolder()->getKey();
    $this->params['name'] = 'test name link';
    $this->params['url'] = 'http://www.kitware.com';
    $this->params['license'] = 0;
    $this->dispatchUrI('/upload/savelink', $userDao);

    $search = $this->Item->getItemsFromSearch($this->params['name'], $userDao);
    if(empty($search))
      {
      $this->fail('Unable to find item');
      }
    $this->setupDatabase(array('default'));
    }

  /** test UploadController::saveuploadedAction*/
  function testSaveuploadedAction()
    {
    $this->setupDatabase(array('default'));

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->params = array();
    $this->params['parent'] = $userDao->getPublicFolder()->getKey();
    $this->params['license'] = 0;
    $this->params['testpath'] = BASE_PATH.'/tests/testfiles/search.png'; //testing mode param
    $this->dispatchUrI('/upload/saveuploaded', $userDao);

    $search = $this->Item->getItemsFromSearch('search.png', $userDao);
    $this->assertNotEmpty($search, 'Unable to find uploaded item');

    // Test to make sure uploading an empty file works
    $this->resetAll();
    $this->params['parent'] = $userDao->getPublicFolder()->getKey();
    $this->params['license'] = 0;
    $this->params['testpath'] = BASE_PATH.'/tests/testfiles/empty.txt'; //testing mode param
    $this->dispatchUrI('/upload/saveuploaded', $userDao);

    $search = $this->Item->getItemsFromSearch('empty.txt', $userDao);
    $this->assertNotEmpty($search, 'Unable to find empty uploaded item');
    }

  /**
   * Test the download controller in the case of a one-bitstream item
   */
  function testDownloadBitstream()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $actualMd5 = md5_file($this->getTempDirectory().'testing_file.png');

    $search = $this->Item->getItemsFromSearch('search.png', $userDao);
    $this->assertTrue(count($search) > 0);
    $itemId = $search[0]->item_id;

    $this->dispatchUrI('/download?testingmode=1&items='.$itemId, $userDao);
    $downloadedMd5 = md5($this->getBody());

    $this->assertEquals($actualMd5, $downloadedMd5);
    }
  }
