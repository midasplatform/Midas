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

  /**
   * Test the download controller in the case of a one-bitstream item
   */
  function testDownloadAction()
    {
    $this->setupDatabase(array('default'));
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $filename = 'search.png';
    $path = BASE_PATH.'/tests/testfiles/'.$filename;

    $this->resetAll();
    $this->params = array();
    $this->params['enabledModules'] = 'api';
    $this->params['folderid'] = 1001;
    $this->params['filename'] = $filename;
    $this->params['useSession'] = 1;
    $this->dispatchUrI('/rest/system/uploadtoken', $userDao);
    $json = json_decode($this->getBody(), true);
    $uploadToken = $json['data']['token'];

    $this->resetAll();
    $this->request->setMethod('POST');
    $this->params = array();
    $this->params['enabledModules'] = 'api';
    $this->params['testingmode'] = 1;
    $this->params['uploadtoken'] = $uploadToken;
    $this->params['filename'] = $filename;
    $this->params['localinput'] = $path;
    $this->params['length'] = filesize($path);
    $this->params['useSession'] = 1;
    $this->dispatchUrI('/rest/system/upload');

    $actualMd5 = md5_file($path);
    $search = $this->Item->getItemsFromSearch($filename, $userDao);
    $this->assertTrue(count($search) > 0);
    $itemId = $search[0]->item_id;

    $this->resetAll();
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

    // Should not throw an exception; we should reach download empty zip
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
    $this->assertEquals($json['action'], 'download');

    $this->resetAll();
    $this->dispatchUri('/download/checksize?folderIds=1002', $adminUser);
    $json = json_decode($this->getBody(), true);
    $this->assertTrue(isset($json['action']));
    $this->assertEquals($json['action'], 'download');

    $this->resetAll();
    $this->dispatchUri('/download/checksize?itemIds=1000', null);
    $json = json_decode($this->getBody(), true);
    $this->assertTrue(isset($json['action']));
    $this->assertEquals($json['action'], 'download');
    }
  }
