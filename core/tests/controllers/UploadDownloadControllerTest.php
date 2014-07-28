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
    $this->_models = array('User', 'Folder', 'Feed', 'Assetstore', 'Item', 'Activedownload');
    $this->_daos = array('User', 'Assetstore', 'Activedownload');
    parent::setUp();
    }

  /** test UploadController::GethttpuploadoffsetAction*/
  function testGethttpuploadoffsetAction()
    {
    $this->setupDatabase(array('default'));

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $dir = $this->getTempDirectory().'/'.$userDao->getUserId().'/1002'; //private folder
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
    $page = 'upload/gethttpuploadoffset/?uploadUniqueIdentifier='.$userDao->getUserId().'/1002/httpupload.png&testingmode=1';
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
    $identifier = $this->getTempDirectory().'/httpupload.png';
    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = 'upload/gethttpuploaduniqueidentifier/?filename=httpupload.png&testingmode=1';
    $this->dispatchUrI($page, $userDao);
    $this->assertEquals(trim($this->getBody()), '[ERROR]You must specify a parent folder or item.');

    $this->resetAll();
    $folders = $userDao->getFolder()->getFolders();
    $page .= '&parentFolderId='.$folders[0]->getKey();
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
    $subdir = $userDao->getUserId().'/1002'; // private folder
    $dir = $this->getTempDirectory().'/'.$subdir;
    $fileBase = BASE_PATH.'/tests/testfiles/search.png';
    $file = $this->getTempDirectory().'/testing_file.png';
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
    $this->dispatchUrI($page, $userDao, true);

    $this->resetAll();
    $page .= '&parentId=1002';
    $this->dispatchUrI($page, $userDao);
    $this->assertTrue(strpos($this->getBody(), '[ERROR]') === 0);

    $this->resetAll();
    $params = 'testingmode=1&filename=search.png&localinput='.$file.'&length='.(filesize($file) + 1).'&uploadUniqueIdentifier='.$subdir.'/httpupload.png';
    $page = 'upload/processjavaupload/?'.$params.'&parentId=1002';
    $this->dispatchUrI($page, $userDao);
    $this->assertTrue(strpos($this->getBody(), '[OK]') === 0);

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

    $folder = $this->Folder->load(1001); //public folder
    $this->dispatchUrI('/upload/simpleupload?parent='.$folder->getKey(), $userDao, false);
    $this->assertContains('id="destinationId" value="'.$folder->getKey(), $this->getBody());

    $this->resetAll();
    $this->dispatchUrI('/upload/simpleupload', $userDao, false);
    $this->assertContains('id="destinationId" value=""', $this->getBody());
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

    $this->params = array();
    $this->params['parent'] = '1001'; //public folder
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
    $this->params['parent'] = '1001'; //public folder
    $this->params['license'] = 0;
    $this->params['testpath'] = BASE_PATH.'/tests/testfiles/search.png'; //testing mode param
    $this->dispatchUrI('/upload/saveuploaded', $userDao);

    $search = $this->Item->getItemsFromSearch('search.png', $userDao);
    $this->assertNotEmpty($search, 'Unable to find uploaded item');

    // Test to make sure uploading an empty file works
    $this->resetAll();
    $this->params['parent'] = '1001';
    $this->params['license'] = 0;
    $this->params['testpath'] = BASE_PATH.'/tests/testfiles/empty.txt'; //testing mode param
    $this->dispatchUrI('/upload/saveuploaded', $userDao);

    $search = $this->Item->getItemsFromSearch('empty.txt', $userDao);
    $this->assertNotEmpty($search, 'Unable to find empty uploaded item');
    }

  /**
   * Test the download controller in the case of a one-bitstream item
   */
  function testDownloadAction()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $actualMd5 = md5_file($this->getTempDirectory().'/testing_file.png');

    $search = $this->Item->getItemsFromSearch('search.png', $userDao);
    $this->assertTrue(count($search) > 0);
    $itemId = $search[0]->item_id;

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; //must be set in order to lock active download
    $this->setupDatabase(array('activedownload')); //wipe any old locks
    $this->dispatchUrI('/download?items='.$itemId, $userDao);
    $downloadedMd5 = md5($this->getBody());

    $this->assertEquals($actualMd5, $downloadedMd5);

    // Test our special path-style URL endpoints for downloading single items or folders
    $this->resetAll();
    $this->setupDatabase(array('activedownload')); //wipe any old locks
    $this->dispatchUrI('/download/item/'.$itemId.'/', $userDao);
    $downloadedMd5 = md5($this->getBody());

    $this->assertEquals($actualMd5, $downloadedMd5);

    // Downloading an invalid bitstream id should respond with 404 and exception
    $this->resetAll();
    $this->dispatchUrI('/download?bitstream=934192', $userDao, true, false);

    // Should not throw an exception; we should reach download empty zip code
    $this->resetAll();
    $this->dispatchUrI('/download?items=', $userDao);
    $this->assertEquals(trim($this->getBody()), 'No_item_selected');

    // We should get an exception if we try to download item 1002
    $this->resetAll();
    $this->dispatchUrI('/download?items=1004-1002', $userDao, true);

    // We should get an exception if trying to download an item that doesn't exist
    $this->resetAll();
    $this->dispatchUrI('/download?items=214529', $userDao, true, false);
    }

  /**
   * Test active download locking mechanism
   */
  function testActiveDownloadLocking()
    {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; //simulate server environment (required)
    $this->setupDatabase(array('activedownload')); //wipe any old locks

    // Test that only one active download is allowed at a time
    $lock = $this->Activedownload->acquireLock();
    $this->assertTrue($lock instanceof ActivedownloadDao);

    try
      {
      $otherLock = $this->Activedownload->acquireLock();
      $this->fail('We should not be able to acquire a second lock');
      }
    catch(Exception $e)
      {
      $this->assertEquals($otherLock, null);
      $this->Activedownload->delete($lock);
      }

    // Test that an old/orphaned lock is replaced without a problem
    $oldLock = new ActivedownloadDao();
    $oldLock->setIp($_SERVER['REMOTE_ADDR']);
    $oldLock->setDateCreation(date("Y-m-d H:i:s", strtotime('-1 day')));
    $oldLock->setLastUpdate(date("Y-m-d H:i:s", strtotime('-10 minutes')));
    $this->Activedownload->save($oldLock);

    $lock = $this->Activedownload->acquireLock();
    $this->assertTrue($lock instanceof ActivedownloadDao);
    $this->assertNotEquals($lock->getKey(), $oldLock->getKey());
    }

  /** Test the checksize method */
  function testChecksizeAction()
    {
    $adminUser = $this->User->load(3);
    // anon user should throw an exception if no permission on the folder
    $this->dispatchUri('/download/checksize?folderIds=1002', null, true);

    $this->resetAll();
    $this->dispatchUri('/download/checksize?folderIds=1001', $adminUser);
    $json = json_decode($this->getBody(), true);
    $this->assertTrue(isset($json['action']));
    $this->assertEquals($json['action'], 'download'); //below the threshold

    $this->resetAll();
    $this->dispatchUri('/download/checksize?folderIds=1002', $adminUser);
    $json = json_decode($this->getBody(), true);
    $this->assertTrue(isset($json['action']));
    $this->assertEquals($json['action'], 'download'); //below the threshold

    $this->resetAll();
    $this->dispatchUri('/download/checksize?itemIds=1000', null);
    $json = json_decode($this->getBody(), true);
    $this->assertTrue(isset($json['action']));
    $this->assertEquals($json['action'], 'download'); //below the threshold

    $this->resetAll();
    $item = $this->Item->load(1000);
    $item->setSizebytes(1342177280); //1.25 GB
    $this->Item->save($item);
    $this->dispatchUri('/download/checksize?itemIds=1000', null);
    $json = json_decode($this->getBody(), true);
    $this->assertTrue(isset($json['action']));
    $this->assertEquals($json['action'], 'promptApplet'); //now above the threshold
    $this->assertEquals($json['sizeStr'], '1.3 GB'); //should round to 1 decimal place
    }

  /** Test rendering of the large downloader view */
  function testAppletAction()
    {
    $adminUser = $this->User->load(3);
    $this->dispatchUri('/download/applet?folderIds=1002', null, true);

    $this->resetAll();
    $this->dispatchUri('/download/applet?folderIds=1002', $adminUser);
    $this->assertQuery('param[name="itemIds"]');
    $this->assertQuery('param[name="folderIds"][value="1002"]');
    $this->assertQuery('param[name="totalSize"][value="0"]');

    $this->resetAll();
    $item = $this->Item->load(1000);
    $item->setSizebytes(1342177280); //1.25 GB
    $this->Item->save($item);
    $this->dispatchUri('/download/applet?itemIds=1000', $adminUser);
    $this->assertQuery('param[name="itemIds"][value="1000"]');
    $this->assertQuery('param[name="folderIds"]');
    $this->assertQuery('param[name="totalSize"][value="1342177280"]');
    }
  }
