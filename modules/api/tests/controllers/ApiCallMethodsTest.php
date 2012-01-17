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

/** Tests the functionality of the web API methods */
class ApiCallMethodsTest extends ControllerTestCase
  {
  /** set up tests */
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'api'); // module dataset
    $this->enabledModules = array('api');
    $this->_models = array('User', 'Folder', 'Item', 'ItemRevision', 'Assetstore', 'Bitstream');
    $this->_daos = array();

    parent::setUp();
    }

  /** Invoke the JSON web API */
  private function _callJsonApi($sessionUser = null, $method = 'POST')
    {
    $this->request->setMethod($method);
    $this->dispatchUrI($this->webroot.'api/json', $sessionUser);
    return json_decode($this->getBody());
    }

  /** Make sure we got a good response from a web API call */
  private function _assertStatusOk($resp)
    {
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);
    $this->assertTrue(isset($resp->data));
    }

  /** Authenticate using the default api key for user 1 */
  private function _loginAsNormalUser()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->params['method'] = 'midas.login';
    $this->params['email'] = $usersFile[0]->getEmail();
    $this->params['appname'] = 'Default';
    $this->params['apikey'] = $apiKey;

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(strlen($resp->data->token), 40);

    // **IMPORTANT** This will clear any params that were set before this function was called
    $this->resetAll();
    return $resp->data->token;
    }

  /** Authenticate using the default api key */
  private function _loginAsAdministrator()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->params['method'] = 'midas.login';
    $this->params['email'] = $usersFile[2]->getEmail();
    $this->params['appname'] = 'Default';
    $this->params['apikey'] = $apiKey;
    $this->request->setMethod('POST');

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(strlen($resp->data->token), 40);

    // **IMPORTANT** This will clear any params that were set before this function was called
    $this->resetAll();
    return $resp->data->token;
    }

  /** Get the folders corresponding to the user */
  public function testUserFolders()
    {
    // Try anonymously first
    $this->params['method'] = 'midas.user.folders';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    // No user folders should be visible anonymously
    $this->assertEquals(count($resp->data), 0);

    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.user.folders';
    $resp = $this->_callJsonApi(null, 'GET');
    $this->_assertStatusOk($resp);
    $this->assertEquals(count($resp->data), 2);

    // We do not expect folder 1000 to be returned, as this is an internal-only
    // value not intended to be exposed by the web api
    foreach($resp->data as $folder)
      {
      $this->assertEquals($folder->_model, 'Folder');
      $this->assertEquals($folder->parent_id, 1000);
      }
    $this->assertEquals($resp->data[0]->name, 'User 1 name Folder 2');
    $this->assertEquals($resp->data[1]->name, 'User 1 name Folder 3');
    }

  /** Test creation of a new community */
  public function testCommunityCreate()
    {
    $modelLoader = new MIDAS_ModelLoader();
    $communityModel = $modelLoader->loadModel('Community');
    $communities = $communityModel->getAll();
    $originalCount = count($communities);

    // Normal user should not be able to create a community
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.community.create';
    $this->params['name'] = 'testNewComm';
    $resp = $this->_callJsonApi();
    $this->assertEquals($resp->message, 'Only admins can create communities');
    $this->assertEquals($resp->stat, 'fail');
    $this->assertNotEquals($resp->code, 0);

    $communities = $communityModel->getAll();
    $this->assertEquals(count($communities), $originalCount);

    // Admin should be able to create the community
    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.community.create';
    $this->params['name'] = 'testNewComm';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $communities = $communityModel->getAll();
    $this->assertEquals(count($communities), $originalCount + 1);
    }

  /** Test listing of visible communities */
  public function testCommunityList()
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.community.list';

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertEquals(count($resp->data), 1);
    $this->assertEquals($resp->data[0]->_model, 'Community');
    $this->assertEquals($resp->data[0]->community_id, 2000);
    $this->assertEquals($resp->data[0]->folder_id, 1003);
    $this->assertEquals($resp->data[0]->publicfolder_id, 1004);
    $this->assertEquals($resp->data[0]->privatefolder_id, 1005);
    $this->assertEquals($resp->data[0]->name, 'Community test User 1');

    //TODO test that a private community is not returned (requires another community in the data set)
    }

  /** Test creating a folder */
  public function testFolderCreate()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.folder.create';
    $this->params['name'] = 'testFolderCreate';
    $this->params['parentid'] = $userDao->getPublicfolderId();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Make sure folder was created correctly
    $this->assertNotEmpty($resp->data->uuid);
    $this->assertEquals($userDao->getPublicfolderId(), $resp->data->parent_id);
    $this->assertEquals('testFolderCreate', $resp->data->name);
    $this->assertEquals('', $resp->data->description);
    }

  /** Test listing of child folders */
  public function testFolderChildren()
    {
    $this->resetAll();
    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.folder.children';
    $this->params['id'] = 1000;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Should contain 2 folders and 0 items
    $this->assertEquals(count($resp->data->folders), 2);
    $this->assertEquals(count($resp->data->items), 0);

    $this->assertEquals($resp->data->folders[0]->_model, 'Folder');
    $this->assertEquals($resp->data->folders[1]->_model, 'Folder');
    $this->assertEquals($resp->data->folders[0]->folder_id, 1001);
    $this->assertEquals($resp->data->folders[1]->folder_id, 1002);
    $this->assertEquals($resp->data->folders[0]->name, 'User 1 name Folder 2');
    $this->assertEquals($resp->data->folders[1]->name, 'User 1 name Folder 3');
    $this->assertEquals($resp->data->folders[0]->description, 'Description Folder 2');
    $this->assertEquals($resp->data->folders[1]->description, 'Description Folder 3');

    $this->resetAll();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.folder.children';
    $this->params['id'] = 1001;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Should contain 0 folders and 2 items
    $this->assertEquals(count($resp->data->folders), 0);
    $this->assertEquals(count($resp->data->items), 2);

    $this->assertEquals($resp->data->items[0]->_model, 'Item');
    $this->assertEquals($resp->data->items[1]->_model, 'Item');
    $this->assertEquals($resp->data->items[0]->item_id, 1);
    $this->assertEquals($resp->data->items[1]->item_id, 2);
    $this->assertEquals($resp->data->items[0]->name, 'name 1');
    $this->assertEquals($resp->data->items[1]->name, 'name 2');
    $this->assertEquals($resp->data->items[0]->description, 'Description 1');
    $this->assertEquals($resp->data->items[1]->description, 'Description 2');
    }

  /** Test the item.get method */
  public function testItemGet()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $itemDao = $this->Item->load($itemsFile[0]->getKey());

    $this->resetAll();
    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.get';
    $this->params['id'] = $itemsFile[0]->getKey();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertEquals($resp->data->item_id, $itemDao->getKey());
    $this->assertEquals($resp->data->uuid, $itemDao->getUuid());
    $this->assertEquals($resp->data->description, $itemDao->getDescription());
    $this->assertTrue(is_array($resp->data->revisions));
    $this->assertEquals(count($resp->data->revisions), 2); //make sure we get both revisions
    $this->assertTrue(is_array($resp->data->revisions[0]->bitstreams));
    $this->assertEquals($resp->data->revisions[0]->revision, '1');
    $this->assertEquals($resp->data->revisions[1]->revision, '2');

    // Test the 'head' parameter
    $this->resetAll();
    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.get';
    $this->params['id'] = $itemsFile[0]->getKey();
    $this->params['head'] = 'true';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertEquals(count($resp->data->revisions), 1); //make sure we get only one revision
    $this->assertTrue(is_array($resp->data->revisions[0]->bitstreams));
    $this->assertEquals($resp->data->revisions[0]->revision, '2');
    }

  /** Test get user's default API key using username and password */
  public function testUserApikeyDefault()
    {
    $this->resetAll();
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->params['method'] = 'midas.user.apikey.default';
    $this->params['email'] = $userDao->getEmail();
    $this->params['password'] = 'test';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // Expected API key
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->assertEquals($resp->data->apikey, $apiKey);
    }

  /** Test that we can authenticate to the web API using the user session */
  public function testSessionAuthentication()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $this->resetAll();
    $this->params = array();
    $this->params['method'] = 'midas.user.folders';
    $this->params['useSession'] = 'true';
    $resp = $this->_callJsonApi($userDao);
    $this->_assertStatusOk($resp);

    // We should see the user's folders
    $this->assertEquals(count($resp->data), 2);

    foreach($resp->data as $folder)
      {
      $this->assertEquals($folder->_model, 'Folder');
      $this->assertEquals($folder->parent_id, 1000);
      }
    $this->assertEquals($resp->data[0]->name, 'User 1 name Folder 2');
    $this->assertEquals($resp->data[1]->name, 'User 1 name Folder 3');
    }

  /** Test file upload */
  public function testBitstreamUpload()
    {
    $this->resetAll();
    $usersFile = $this->loadData('User', 'default');
    $itemsFile = $this->loadData('Item', 'default');

    // generate an upload token
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test.txt';
    $this->params['checksum'] = 'foo';
    // call should fail for the first item since we don't have write permission
    $this->params['itemid'] = $itemsFile[0]->getKey();
    $resp = $this->_callJsonApi();
    $this->assertEquals($resp->stat, 'fail');
    $this->assertEquals($resp->message, 'Invalid policy or itemid');
    $this->assertTrue($resp->code != 0);

    //now upload using our token
    $this->resetAll();
    $usersFile = $this->loadData('User', 'default');
    $itemsFile = $this->loadData('Item', 'default');

    // generate the test file
    $string = '';
    $length = 100;
    for($i = 0; $i < $length; $i++)
      {
      $string .= 'a';
      }
    $fh = fopen($this->getTempDirectory().'/test.txt', 'w');
    fwrite($fh, $string);
    fclose($fh);
    $md5 = md5($string);
    $assetstores = $this->Assetstore->getAll();
    $this->assertTrue(count($assetstores) > 0, 'There are no assetstores defined in the database');
    $assetstoreFile = $assetstores[0]->getPath().'/'.substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.$md5;
    if(file_exists($assetstoreFile))
      {
      unlink($assetstoreFile);
      }

    // generate another upload token
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test.txt';
    $this->params['checksum'] = $md5;
    // use the second item since it has write permission set for our user
    $this->params['itemid'] = $itemsFile[1]->getKey();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    // verify the upload token
    $token = $resp->data->token;
    $this->assertTrue(
      preg_match('/^'.$usersFile[0]->getKey().'\/'.$itemsFile[1]->getKey().'\/.+\..+$/', $token) > 0,
      'Upload token ('.$token.') is not of the form <userid>/<itemid>/*.*');
    $this->assertTrue(file_exists($this->getTempDirectory().'/'.$token),
      'Token placeholder file '.$token.' was not created in the temp dir');

    // attempt the upload
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['itemid'] = $itemsFile[1]->getKey();
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertTrue(file_exists($assetstoreFile), 'File was not written to the assetstore');
    $this->assertEquals(filesize($assetstoreFile), $length, 'Assetstore file is the wrong length');
    $this->assertEquals(md5_file($assetstoreFile), $md5, 'Assetstore file had incorrect checksum');

    // make sure it was uploaded to the head revision of the item
    $itemDao = $this->Item->load($itemsFile[1]->getKey());
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);


    // when calling midas.upload.perform 2x in a row with the same params
    // (same upload token, same file that had just been uploaded),
    // the response should be an invalid token, -141.
    //
    // This is because the token is good for a single upload, and it no longer
    // exists once the original upload is finished.
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['itemid'] = $itemsFile[1]->getKey();
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $resp = $this->_callJsonApi();
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->stat, 'fail');
    $this->assertEquals($resp->code, -141);

    // Check that a redundant upload yields a blank upload token and a new reference
    // redundant upload meaning uploading a checksum that already exists
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test2.txt';
    $this->params['checksum'] = $md5;
    $this->params['itemid'] = $itemsFile[1]->getKey();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    $this->assertEquals($token, '', 'Redundant content upload did not return a blank token');

    // check that the new bitstream has been created
    // in the generatetoken step
    $itemDao = $this->Item->load($itemsFile[1]->getKey());
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 2, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);
    $this->assertEquals($bitstreams[1]->name, 'test2.txt');
    $this->assertEquals($bitstreams[1]->sizebytes, $length);
    $this->assertEquals($bitstreams[1]->checksum, $md5);

    //separate testing for item create and delete
    // create a new item in the user root folder
    // use folderid 1000
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item';
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');

    // generate upload token
    // when we generate an upload token for a newly created item with a
    // previously uploaded bitstream, and we are passing the checksum,
    // we expect that the item will create a new revision for the bitstream,
    // but pass back an empty upload token, since we have the bitstream content
    // already and do not need to actually upload it
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test3.txt';
    $this->params['checksum'] = $md5;
    $this->params['itemid'] = $generatedItemId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    $this->assertEquals($token, '', 'Redundant content upload did not return a blank token');

    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test3.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    // delete the newly created item
    $this->Item->delete($itemDao);

    // create a new item in the user root folder
    // use folderid 1000
    // need a new item because we are testing functionality involved with
    // a new item
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item2';
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');

    // generate upload token
    // when we generate an upload token for a newly created item without any
    // previously uploaded bitstream (and we don't pass a checksum),
    // we expect that the item will not create any new revision for the bitstream,
    // and that a non-blank upload token will be returned.
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test.txt';
    $this->params['itemid'] = $generatedItemId;
    $resp = $this->_callJsonApi();
    $token = $resp->data->token;

    // verify the token
    $this->assertTrue(
      preg_match('/^'.$usersFile[0]->getKey().'\/'.$generatedItemId.'\/.+\..+$/', $token) > 0,
      'Upload token ('.$token.') is not of the form <userid>/<itemid>/*.*');
    $this->assertTrue(file_exists($this->getTempDirectory().'/'.$token),
      'Token placeholder file '.$token.' was not created in the temp dir');

    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');

    // upload to revision 1, this should be an error since there is no such rev
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['itemid'] = $generatedItemId;
    $this->params['revision'] = '1'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $resp = $this->_callJsonApi();
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->stat, 'fail');
    $this->assertEquals($resp->code, -150);

    // upload to head revision, this should create a revision 1 and
    // put the bitstream there
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['itemid'] = $generatedItemId;
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $this->params['DBG'] = 'true';
    $resp = $this->_callJsonApi();

    // ensure that there is 1 revision with 1 bitstream
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the new item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    unlink($this->getTempDirectory().'/test.txt');
    }

  /** test the bitstream count functionality on all resource types */
  public function testBitstreamCount()
    {
    $bitstreamsFile = $this->loadData('Bitstream', 'default');
    $bitstream = $this->Bitstream->load($bitstreamsFile[0]->getKey());
    $expectedSize = $bitstream->getSizebytes();

    // Test passing a bad uuid
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = 'notavaliduuid';
    $resp = $this->_callJsonApi();
    $this->assertEquals($resp->message, 'No resource for the given UUID.');
    $this->assertEquals($resp->stat, 'fail');
    $this->assertNotEquals($resp->code, 0);

    // Test count bitstreams in community
    $this->resetAll();
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = '4e311fdf82107d245f0798d654fc24205f2621eb72777';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->count, 0);
    $this->assertEquals($resp->data->size, 0); //no bitstreams in this community

    // Test count bitstreams in folder without privileges - should fail
    $this->resetAll();
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = '4e311fdf82007c245b07d8d6c4fcb4205f2621eb72751';
    $resp = $this->_callJsonApi();
    $this->assertEquals($resp->message, 'Invalid policy');
    $this->assertEquals($resp->stat, 'fail');
    $this->assertNotEquals($resp->code, 0);

    // Test count bitstreams in folder with privileges - should succeed
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = '4e311fdf82007c245b07d8d6c4fcb4205f2621eb72761';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->count, 1);
    $this->assertEquals($resp->data->size, $expectedSize);

    // Test count bitstreams in item
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = '4e311fdf82007c245b07d8d6c4fcb4205f2621eb72750';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->count, 1);
    $this->assertEquals($resp->data->size, $expectedSize);
    }

  /** test item creation and deletion */
  public function testCreateitemDeleteitem()
    {
    // create an item with only required options
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item_1';
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getName(), $this->params['name'], 'Item name is not set correctly');
    $this->assertEquals($itemDao->getDescription(), '', 'Item name is not set correctly');

    // delete it
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.delete';
    $this->params['id'] = $generatedItemId;
    $resp = $this->_callJsonApi();
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertFalse($itemDao, 'Item should have been deleted, but was not.');

    // create an item with required options, plus description and uuid
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item_2';
    $this->params['description'] = 'my item description';
    $this->params['uuid'] = uniqid() . md5(mt_rand());
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getName(), $this->params['name'], 'Item name is not set correctly');
    $this->assertEquals($itemDao->getUuid(), $this->params['uuid'], 'Item uuid is not set correctly');
    $this->assertEquals($itemDao->getDescription(), $this->params['description'], 'Item description is not set correctly');

    // delete the second one
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.delete';
    $this->params['id'] = $generatedItemId;
    $resp = $this->_callJsonApi();
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertFalse($itemDao, 'Item should have been deleted, but was not.');
    }

  /** Test the server info reporting methods */
  public function testInfoMethods()
    {
    // Test midas.version
    $this->params['method'] = 'midas.version';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->version, Zend_Registry::get('configDatabase')->version);

    // Test midas.modules.list
    $this->resetAll();
    $this->params['method'] = 'midas.modules.list';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertNotEmpty($resp->data->modules);
    $this->assertTrue(in_array('api', $resp->data->modules));

    // Test midas.methods.list
    $this->resetAll();
    $this->params['method'] = 'midas.methods.list';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertNotEmpty($resp->data->methods);
    foreach($resp->data->methods as $method)
      {
      $this->assertNotEmpty($method->name);
      $this->assertNotEmpty($method->help);
      $this->assertTrue(isset($method->help->description));
      $this->assertTrue(isset($method->help->params));
      $this->assertTrue(isset($method->help->example));
      $this->assertTrue(isset($method->help->return));

      // Test a specific method's params list
      if($method->name == 'login')
        {
        $this->assertNotEmpty($method->help->params->appname);
        $this->assertNotEmpty($method->help->params->apikey);
        $this->assertNotEmpty($method->help->params->email);
        }
      }

    // Test midas.info
    $this->resetAll();
    $this->params['method'] = 'midas.info';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // We should get version
    $this->assertEquals($resp->data->version, Zend_Registry::get('configDatabase')->version);

    // We should get modules list
    $this->assertNotEmpty($resp->data->modules);
    $this->assertTrue(in_array('api', $resp->data->modules));

    // We should get methods list
    $this->assertNotEmpty($resp->data->methods);
    foreach($resp->data->methods as $method)
      {
      $this->assertNotEmpty($method->name);
      $this->assertNotEmpty($method->help);
      $this->assertTrue(isset($method->help->description));
      $this->assertTrue(isset($method->help->params));
      $this->assertTrue(isset($method->help->example));
      $this->assertTrue(isset($method->help->return));
      }
    }
  }
