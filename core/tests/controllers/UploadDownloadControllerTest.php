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
    $identifier = BASE_PATH.'/tmp/misc/httpupload.png';
    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = 'upload/gethttpuploadoffset/?uploadUniqueIdentifier=httpupload.png&testingmode=1';
    $this->dispatchUrI($page);

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
    $identifier = BASE_PATH.'/tmp/misc/httpupload.png';
    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = 'upload/gethttpuploaduniqueidentifier/?filename=httpupload.png&testingmode=1';
    $this->dispatchUrI($page);
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
    $fileBase = BASE_PATH.'/tests/testfiles/search.png';
    $file = BASE_PATH.'/tmp/misc/testing_file.png';
    $identifier = BASE_PATH.'/tmp/misc/httpupload.png';

    if(file_exists($identifier))
      {
      unlink($identifier);
      }
    copy($fileBase, $file);
    $ident = fopen($identifier, 'x+');
    fwrite($ident, ' ');
    fclose($ident);
    chmod($identifier, 0777);

    $params = 'testingmode=1&filename=search.png&path='.$file.'&length='.filesize($file).'&uploadUniqueIdentifier='.basename($identifier);
    $page = $this->webroot.'item/process_http_upload/'.$this->item.'?'.$params;

    $page = 'upload/processjavaupload/?'.$params;

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
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
    $actualMd5 = md5_file(BASE_PATH.'/tmp/misc/testing_file.png');

    $search = $this->Item->getItemsFromSearch('search.png', $userDao);
    $this->assertTrue(count($search) > 0);
    $itemId = $search[0]->item_id;

    $this->dispatchUrI('/download?testingmode=1&items='.$itemId, $userDao);
    $downloadedMd5 = md5($this->getBody());

    $this->assertEquals($actualMd5, $downloadedMd5);
    }
  }
