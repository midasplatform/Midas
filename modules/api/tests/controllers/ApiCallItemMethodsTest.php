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

require_once BASE_PATH . '/modules/api/tests/controllers/ApiCallMethodsTest.php';

/** Tests the functionality of the web API Item and Bistream methods */
class ApiCallItemMethodsTest extends ApiCallMethodsTest
  {
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

  /** Test the item.duplicate method */
  public function testItemDuplicate()
    {
    $itemsFile = $this->loadData('Item', 'default');

    $this->resetAll();
    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.duplicate';
    $this->params['id'] = $itemsFile[0]->getKey();
    $this->params['dstfolderid'] = 1002;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $dupItemId = $resp->data->item_id;
    $dupItemDao = $this->Item->load($dupItemId);
    $owningFolders = $dupItemDao->getFolders();
    $this->assertEquals($owningFolders[0]->getKey(), 1002);

    $origItemDao = $this->Item->load($itemsFile[0]->getKey());
    $this->assertEquals($resp->data->name, $origItemDao->getName());
    $this->assertEquals($resp->data->description, $origItemDao->getDescription());

    $revisions = $dupItemDao->getRevisions();
    $this->assertEquals(count($revisions), 2);
    $revision1 = $revisions[0]->toArray();
    $this->assertEquals($revision1['revision'], '1');
    $revision2 = $revisions[1]->toArray();
    $this->assertEquals($revision2['revision'], '2');
    }

  /** Test the item.share method */
  public function testItemShare()
    {
    $itemsFile = $this->loadData('Item', 'default');

    $this->resetAll();
    $origItemDao = $this->Item->load($itemsFile[0]->getKey());
    $owningFolders = $origItemDao->getFolders();
    $this->assertTrue(is_array($owningFolders));
    $this->assertEquals(count($owningFolders), 1);
    $token = $this->_loginAsNormalUser();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.share';
    $this->params['id'] = $itemsFile[0]->getKey();
    $this->params['dstfolderid'] = 1002;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertTrue(is_array($resp->data->owningfolders));
    $this->assertEquals(count($resp->data->owningfolders), 2);
    $this->assertEquals($resp->data->owningfolders[0]->folder_id, '1001');
    $this->assertEquals($resp->data->owningfolders[1]->folder_id, '1002');
    }

  /** Test the item.move method */
  public function testItemMove()
    {
    $token = $this->_loginAsNormalUser();

    // check that user can't move an item they only have read on
    $this->resetAll();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.move';
    $this->params['id'] = 1004;
    $this->params['srcfolderid'] = 1014;
    $this->params['dstfolderid'] = 1002;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // check that user can't move an item they only have read on
    $this->resetAll();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.move';
    $this->params['id'] = 1005;
    $this->params['srcfolderid'] = 1014;
    $this->params['dstfolderid'] = 1002;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // check that user can't move an item to a folder they only have read on
    $this->resetAll();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.move';
    $this->params['id'] = 1006;
    $this->params['srcfolderid'] = 1014;
    $this->params['dstfolderid'] = 1012;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    $this->resetAll();
    $origItemDao = $this->Item->load(1006);
    $owningFolders = $origItemDao->getFolders();
    $this->assertTrue(is_array($owningFolders ));
    $this->assertEquals(count($owningFolders), 1);
    $this->assertEquals($owningFolders[0]->getKey(), '1014');

    // check that user can move from admin item to write folder
    $this->resetAll();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.move';
    $this->params['id'] = 1006;
    $this->params['srcfolderid'] = 1014;
    $this->params['dstfolderid'] = 1013;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $this->assertTrue(is_array($resp->data->owningfolders));
    $this->assertEquals(count($resp->data->owningfolders), 1);
    $this->assertEquals($resp->data->owningfolders[0]->folder_id, '1013');
    }

  /** Test file upload */
  public function testBitstreamUpload()
    {
    $this->resetAll();
    $itemsFile = $this->loadData('Item', 'default');

    // This method is quite complex, so here is a listing of all
    // the tests performed.  Also, the upload.generatetoken and
    // upload.perform have been reworked for 3.2.8, but the previous
    // versions of the API are tested here for backwards compatability
    // (the previous versions of the APIs should still work).
    // Each request gets a new count, related requests are grouped.
    /*
     * 1 generate upload token for an item lacking permissions, ERROR
     *
     * 2 generate an upload token for an item with permissions
     * 3 upload using 2's token to the head revision
     * 4 upload again using 2's token, tokens should be 1 time use, ERROR
     *
     * 5 generate an upload token with a checksum of an existing bitstream
     *
     * 6 create a new item in the user root folder
     * 23 create a duplicate name item in user root folder with item.create
     * 24 create a duplicate name item in user root folder with generatetoken
     * 7 generate an upload token for 6's item, using a checksum of an existing bitstream
     *
     * 8 create a new item in the user root folder
     * 9 generate an upload token for 8's item, without a checksum
     *10 upload to 8's item revision 1, which doesn't exist, ERROR
     *11 upload to 8's item head revision, this will create a revision 1
     *12 upload to 8's item head revision again, tests using head revision when one exists
     *
     *13 generatetoken without folderid or itemid, ERROR
     *
     *14 generatetoken with folderid and itemid, ERROR
     *
     *15 generatetoken passing in folderid, default of Public
     *
     *16 generatetoken passing in folderid and setting optional values with Private itemprivacy
     *17 upload without passing a revision to 16's item, this should create a revision 1
     *18 generate a token for 16's item
     *19 upload to an item with an existing revision, without the revision parameter, which should create a new revision 2
     *20 generate a token for 16's item, after creating 2 new revisions, 3 and 4
     *21 upload to an item with 4 revisions, with revision parameter of 3, check that revision 3 has the bitstream and revision 4 has 0 bitstreams
     *
     *22 generatetoken passing in folderid and a checksum of an existing bitstream,
     *   and passing Public explicitly
     *25 generatetoken passing in folderid and checksum of an existing bitstream, but the user
     *   has no read access to the already existing item, so exception is thrown
     *
     */

    // 1
    // generate an upload token
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test.txt';
    $this->params['checksum'] = 'foo';
    // call should fail for the first item since we don't have write permission
    $this->params['itemid'] = $itemsFile[0]->getKey();
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY, 'Invalid policy or itemid');

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

    // 2
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
      preg_match('/^'.$usersFile[0]->getKey().'\/'.$itemsFile[1]->getKey().'\/.+/', $token) > 0,
      'Upload token ('.$token.') is not of the form <userid>/<itemid>/*');
    $this->assertTrue(file_exists($this->getTempDirectory().'/'.$token),
      'Token placeholder file '.$token.' was not created in the temp dir');

    // 3
    // attempt the upload
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $changes = 'revision message';
    $this->params['changes'] = $changes;
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
    $this->assertEquals($revisions[0]->getChanges(), $changes, 'Wrong number of bitstreams in the revision');
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    // 4
    // when calling midas.upload.perform 2x in a row with the same params
    // (same upload token, same file that had just been uploaded),
    // the response should be an invalid token.
    //
    // This is because the token is good for a single upload, and it no longer
    // exists once the original upload is finished.
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_UPLOAD_TOKEN);

    // 5
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

    // separate testing for item create and delete

    // 6
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

    // 23
    // create a new item in the user root folder with a duplicate name, which
    // will result in an item with an amended name from the request
    // use folderid 1000
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item';
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedDuplicateItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedDuplicateItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');
    $this->assertEquals($itemDao->getName(), "created_item (1)", 'Duplicate Item has wrong name');
    // delete the newly created item
    $this->Item->delete($itemDao);

    // 24
    // create a new item in the user root folder with a duplicate name, which
    // will result in an item with an amended name from the request, using
    // upload.generatetoken, use folderid 1000
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['name'] = 'created_item';
    $this->params['filename'] = 'created_item';
    $this->params['folderid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    $tokenParts = explode("/", $token);
    $generatedDuplicateItemId = $tokenParts[1];//var_dump($tokenParts);
    $itemDao = $this->Item->load($generatedDuplicateItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');
    $this->assertEquals($itemDao->getName(), "created_item (1)", 'Duplicate Item has wrong name');
    // delete the newly created item
    $this->Item->delete($itemDao);

    // 25
    // Just like #22, but we want to make sure the clone existing bitstream behavior
    // doesn't happen when the user doesn't have read access to the item with the given checksum.
    // Otherwise for an attacker, having the checksum of a bitstream would allow them to exploit
    // upload.generatetoken to gain read access to that bitstream, even if they didn't have
    // read access on it.
    $existingItem = $this->Item->load(1001); // this should be the item with the existing bitstream in it
    $policy = $this->Itempolicyuser->getPolicy($usersFile[0], $existingItem);
    $this->Itempolicyuser->delete($policy); // delete permission for the user on the item
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'some_new_file.txt';
    $this->params['checksum'] = $md5;
    $this->params['folderid'] = '1000';
    $this->params['itemprivacy'] = 'Public';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    $this->assertNotEquals($token, '', 'User needs read access to existing bitstream to clone it');
    $this->Itempolicyuser->createPolicy($usersFile[0], $existingItem, $policy->getPolicy()); //restore policy state

    // 7
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

    // 8
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

    // 9
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
      preg_match('/^'.$usersFile[0]->getKey().'\/'.$generatedItemId.'\/.+/', $token) > 0,
      'Upload token ('.$token.') is not of the form <userid>/<itemid>/*.*');
    $this->assertTrue(file_exists($this->getTempDirectory().'/'.$token),
      'Token placeholder file '.$token.' was not created in the temp dir');

    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');

    // 10
    // upload to revision 1, this should be an error since there is no such rev
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['revision'] = '1'; //upload into revision 1
    $this->params['testingmode'] = 'true';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

    // 11
    // upload to head revision, this should create a revision 1 and
    // put the bitstream there
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $this->params['DBG'] = 'true';
    $this->_callJsonApi();

    // ensure that there is 1 revision with 1 bitstream
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the new item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    // 12
    // upload to head revision again, to test using the head revision when one exists
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = 'test.txt';
    $this->params['length'] = $length;
    $this->params['revision'] = 'head'; //upload into head revision
    $this->params['testingmode'] = 'true';
    $this->params['DBG'] = 'true';
    $this->_callJsonApi();

    // ensure that there is 1 revision with 1 bitstream, will be only one bitstream
    // since we are uploading the same file
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the new item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, 'test.txt');
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    // 13
    // test upload.generatetoken without folderid or itemid
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test.txt';
    $resp = $this->_callJsonApi();
    // should be an error
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

    // 14
    // test upload.generatetoken with folderid and itemid
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = 'test.txt';
    $this->params['itemid'] = '0';
    $this->params['folderid'] = '0';
    $resp = $this->_callJsonApi();
    // should be an error
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

    // 15
    // test upload.generatetoken passing in folderid
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $filename = 'test.txt';
    $this->params['filename'] = $filename;
    $this->params['folderid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    // don't know the itemid as it is generated, but can get it from the token
    $this->assertTrue(
      preg_match('/^'.$usersFile[0]->getKey().'\/(\d+)\/.+/', $token, $matches) > 0,
      'Upload token ('.$token.') is not of the form <userid>/<itemid>/*.*');
    $generatedItemId = $matches[1];
    $this->assertTrue(file_exists($this->getTempDirectory().'/'.$token),
      'Token placeholder file '.$token.' was not created in the temp dir');
    // test that the item was created without any revisions
    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');
    // test that the properties of the item are as expected with defaults
    $this->assertEquals($itemDao->getName(), $filename, 'Expected a different name for generated item');
    $this->assertEquals($itemDao->getDescription(), '', 'Expected a different description for generated item');
    $this->assertEquals($itemDao->getPrivacyStatus(), MIDAS_PRIVACY_PRIVATE, 'Expected a different privacy_status for generated item');
    // delete the newly created item
    $this->Item->delete($itemDao);

    // 16
    // test upload.generatetoken passing in folderid and setting optional values
    $filename = 'test.txt';
    $description = 'generated item description';
    $itemname = 'generated item name';
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = $filename;
    $this->params['folderid'] = '1000';
    $this->params['itemprivacy'] = 'Public';
    $this->params['itemdescription'] = $description;
    $this->params['itemname'] = $itemname;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    // don't know the itemid as it is generated, but can get it from the token
    $this->assertTrue(
      preg_match('/^'.$usersFile[0]->getKey().'\/(\d+)\/.+/', $token, $matches) > 0,
      'Upload token ('.$token.') is not of the form <userid>/<itemid>/*.*');
    $generatedItemId = $matches[1];
    $this->assertTrue(file_exists($this->getTempDirectory().'/'.$token),
      'Token placeholder file '.$token.' was not created in the temp dir');
    // test that the item was created without any revisions
    $itemDao = $this->Item->load($generatedItemId);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 0, 'Wrong number of revisions in the new item');
    // test that the properties of the item are as expected with parameters
    $this->assertEquals($itemDao->getName(), $itemname, 'Expected a different name for generated item');
    $this->assertEquals($itemDao->getDescription(), $description, 'Expected a different description for generated item');
    $this->assertEquals($itemDao->getPrivacyStatus(), MIDAS_PRIVACY_PRIVATE, 'Expected a different privacy_status for generated item');

    // 17
    // upload without passing a revision, this should create a revision 1 and
    // put the bitstream there
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = $filename;
    $this->params['length'] = $length;
    $this->params['testingmode'] = 'true';
    $this->params['DBG'] = 'true';
    $this->_callJsonApi();

    // ensure that there is 1 revision with 1 bitstream
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the new item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    // 18
    // test uploading a file into an existing item with an existing revision,
    // but without passing the revision parameter, which should create a new
    // revision.
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = $filename;
    $this->params['itemid'] = $generatedItemId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;

    // 19
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = $filename;
    $this->params['length'] = $length;
    $this->params['testingmode'] = 'true';
    $this->params['DBG'] = 'true';
    $this->_callJsonApi();

    // ensure that there are now 2 revisions with 1 bitstream each
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 2, 'Wrong number of revisions in the new item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in revision 1');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);
    $bitstreams = $revisions[1]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in revision 2');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);

    // now to test upload a bitstream to a specific revision
    // create 2 new revisions on this item, 3 and 4, then add to the 3rd
    Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH.'/core/models/dao');
    $revision = new ItemRevisionDao();
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 3');
    $this->Item->addRevision($itemDao, $revision);
    $revision = new ItemRevisionDao();
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 4');
    $this->Item->addRevision($itemDao, $revision);
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 4, 'Wrong number of revisions in the new item');

    // 20
    // test uploading a file into an existing item with an existing revision,
    // passing the revision parameter of 3, which should add to revision 3
    // first generate the token
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = $filename;
    $this->params['itemid'] = $generatedItemId;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;

    // 21
    // now upload to revision 3
    $this->resetAll();
    $this->params['method'] = 'midas.upload.perform';
    $this->params['uploadtoken'] = $token;
    $this->params['filename'] = $filename;
    $this->params['length'] = $length;
    $this->params['revision'] = '3';
    $this->params['testingmode'] = 'true';
    $this->params['DBG'] = 'true';
    $this->_callJsonApi();

    // ensure that there are now 4 revisions, the first 3 having 1 bitstream and the last having 0
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 4, 'Wrong number of revisions in the new item');
    $rev1 = $this->Item->getRevision($itemDao, '1');
    $bitstreams = $rev1->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in revision 1');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);
    $rev2 = $this->Item->getRevision($itemDao, '2');
    $bitstreams = $rev2->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in revision 2');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);
    $rev3 = $this->Item->getRevision($itemDao, '3');
    $bitstreams = $rev3->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in revision 3');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);
    $rev4 = $this->Item->getRevision($itemDao, '4');
    $bitstreams = $rev4->getBitstreams();
    $this->assertEquals(count($bitstreams), 0, 'Wrong number of bitstreams in revision 4');

    // delete the newly created item
    $this->Item->delete($itemDao);

    // 22
    // test upload.generatetoken passing in folderid
    // when we generate an upload token for an item that will be created by
    // the upload.generatetoken method,
    // with a previously uploaded bitstream, and we are passing the checksum,
    // we expect that the item will create a new revision for the bitstream,
    // but pass back an empty upload token, since we have the bitstream content
    // already and do not need to actually upload it; also test passing in Public
    // explictly
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.upload.generatetoken';
    $this->params['filename'] = $filename;
    $this->params['checksum'] = $md5;
    $this->params['folderid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $token = $resp->data->token;
    $this->assertEquals($token, '', 'Redundant content upload did not return a blank token');

    // this is hackish, since we aren't getting back the itemid for the
    // newly generated item, we know it will be the next id in the sequence
    // this at least allows us to test it
    $generatedItemId = $generatedItemId + 1;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getPrivacyStatus(), MIDAS_PRIVACY_PRIVATE, 'Expected a different privacy_status for generated item');
    $revisions = $itemDao->getRevisions();
    $this->assertEquals(count($revisions), 1, 'Wrong number of revisions in the item');
    $bitstreams = $revisions[0]->getBitstreams();
    $this->assertEquals(count($bitstreams), 1, 'Wrong number of bitstreams in the revision');
    $this->assertEquals($bitstreams[0]->name, $filename);
    $this->assertEquals($bitstreams[0]->sizebytes, $length);
    $this->assertEquals($bitstreams[0]->checksum, $md5);
    // delete the newly created item
    $this->Item->delete($itemDao);

    unlink($this->getTempDirectory().'/test.txt');
    }

  /** test the bitstream count functionality on all resource types */
  public function testBitstreamCount()
    {
    $bitstreamsFile = $this->loadData('Bitstream', 'default');
    $bitstream_1 = $this->Bitstream->load($bitstreamsFile[0]->getKey());
    $bitstream_1_expectedSize = $bitstream_1->getSizebytes();
    $bitstream_2 = $this->Bitstream->load($bitstreamsFile[1]->getKey());
    $totalExpectedSize = $bitstream_1_expectedSize + $bitstream_2->getSizebytes();

    // Test passing a bad uuid
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = 'notavaliduuid';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER, 'No resource for the given UUID.');

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
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY, 'Invalid policy');

    // Test count bitstreams in folder with privileges - should succeed
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = '4e311fdf82007c245b07d8d6c4fcb4205f2621eb72761';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->count, 2);
    $this->assertEquals($resp->data->size, $totalExpectedSize);

    // Test count bitstreams in item
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.bitstream.count';
    $this->params['uuid'] = '4e311fdf82007c245b07d8d6c4fcb4205f2621eb72750';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->count, 1);
    $this->assertEquals($resp->data->size, $bitstream_1_expectedSize);
    }

  /** test item creation and deletion */
  public function testCreateitemDeleteitem()
    {
    $itemModel = MidasLoader::loadModel('Item');
    $userModel = MidasLoader::loadModel('User');
    $itemsFile = $this->loadData('Item', 'default');

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
    // default privacy should inherit from parents, so no public read access
    $this->assertPrivacyStatus(array(), array($itemDao), MIDAS_PRIVACY_PRIVATE);

    // delete the first created one via the api
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

    // create an item with passed in Public, ensure that it is Public
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item_Public';
    $this->params['parentid'] = '1000';
    $this->params['privacy'] = 'Public';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getName(), $this->params['name'], 'Item name is not set correctly');
    $this->assertEquals($itemDao->getDescription(), '', 'Item name is not set correctly');
    // test the default privacy is Public
    $this->assertPrivacyStatus(array(), array($itemDao), MIDAS_PRIVACY_PUBLIC);
    $this->Item->delete($itemDao);

    // create an item with passed in Private, ensure that it is Private
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item_Private';
    $this->params['parentid'] = '1000';
    $this->params['privacy'] = 'Private';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getName(), $this->params['name'], 'Item name is not set correctly');
    $this->assertEquals($itemDao->getDescription(), '', 'Item name is not set correctly');
    // test the default privacy is Public
    $this->assertPrivacyStatus(array(), array($itemDao), MIDAS_PRIVACY_PRIVATE);
    $this->Item->delete($itemDao);

    // create an item with required options, plus description and uuid
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'created_item_2';
    $this->params['description'] = 'my item description';
    $uuid = uniqid() . md5(mt_rand());
    $this->params['uuid'] = $uuid;
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getName(), $this->params['name'], 'Item name is not set correctly');
    $this->assertEquals($itemDao->getUuid(), $this->params['uuid'], 'Item uuid is not set correctly');
    $this->assertEquals($itemDao->getDescription(), $this->params['description'], 'Item description is not set correctly');

    // change the name of the item by passing in the uuid
    $changedName = 'created_item_2_changed_name';
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = $changedName;
    $this->params['uuid'] = $uuid;
    $this->params['privacy'] = 'Public';
    $this->params['parentid'] = '1000';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $generatedItemId = $resp->data->item_id;
    $itemDao = $this->Item->load($generatedItemId);
    $this->assertEquals($itemDao->getName(), $changedName, 'Item name is not set correctly');

    // change the name of the item by passing in the uuid; its bitstream name is not updated.
    $changedName = 'created_item_3_changed_name_not_update_bitstream';
    $this->resetAll();
    $itemDao = $this->Item->load($itemsFile[0]->getKey());
    $revisionDao = $this->Item->getLastRevision($itemDao);
    $bitstreams = $revisionDao->getBitstreams();
    $this->assertEquals(count($bitstreams), 1);
    $bitstream_id = $bitstreams[0]->getKey();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = $changedName;
    $this->params['uuid'] = $itemDao->getUuid();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $itemDao = $this->Item->load($itemsFile[0]->getKey());
    $this->assertEquals($itemDao->getName(), $changedName, 'Item name is not set correctly');
    $bitstreamDao = $this->Bitstream->load($bitstream_id);
    $this->assertNotEquals($bitstreamDao->getName(), $changedName, 'Bitstream name should not be updated');
    // bitstreamname is also updated
    $changedName = 'created_item_4_changed_name_also_update_bitstream';
    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = $changedName;
    $this->params['uuid'] = $itemDao->getUuid();
    $this->params['updatebitstream'] = '';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $itemDao = $this->Item->load($itemsFile[0]->getKey());
    $this->assertEquals($itemDao->getName(), $changedName, 'Item name is not set correctly');
    $bitstreamDao = $this->Bitstream->load($bitstream_id);
    $this->assertEquals($bitstreamDao->getName(), $changedName, 'Bitstream name is not updated');

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

    $userDao = $userModel->load('1');
    $readItem = $itemModel->load('1004');
    $writeItem = $itemModel->load('1005');
    $adminItem = $itemModel->load('1006');
    $nonWrites = array($readItem);
    $nonAdmins = array($readItem, $writeItem);
    $writes = array($writeItem, $adminItem);

    // try to set name with read, should fail
    foreach($nonWrites as $item)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.item.create';
      $this->params['name'] = 'readItem';
      $this->params['uuid'] = $item->getUuid();
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }
    // try to set name with writes, should pass
    foreach($writes as $item)
      {
      // get the current item name
      $freshItem = $itemModel->load($item->getItemId());
      $itemName = $freshItem->getName();
      $newItemName = $itemName . "suffix";
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.item.create';
      $this->params['name'] = $newItemName;
      $this->params['uuid'] = $item->getUuid();
      $resp = $this->_callJsonApi();
      $this->_assertStatusOk($resp);
      $refreshItem = $itemModel->load($item->getItemId());
      // ensure that the name was properly updated
      $this->assertEquals($newItemName, $refreshItem->getName(), 'Item name should have been changed');
      }
    // try to set privacy without admin, should fail
    foreach($nonAdmins as $item)
      {
      $this->resetAll();
      $this->params['token'] = $this->_loginAsUser($userDao);
      $this->params['method'] = 'midas.item.create';
      $this->params['name'] = 'write$item';
      $this->params['uuid'] = $item->getUuid();
      $this->params['privacy'] = 'Private';
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

     // try to set privacy to an invalid string
    $this->resetAll();
    $this->params['token'] = $this->_loginAsUser($userDao);
    $this->params['method'] = 'midas.item.create';
    $this->params['name'] = 'writeitem';
    $this->params['uuid'] = $adminItem->getUuid();
    $this->params['privacy'] = 'El Duderino';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

    // want to test changing privacy using this api method on an extant item
    // test cases   Public -> Public
    //              Public -> Private
    //              Private -> Private
    //              Private -> Public
    $privacyStatuses = array(MIDAS_PRIVACY_PUBLIC, MIDAS_PRIVACY_PRIVATE);
    $privacyStrings = array(MIDAS_PRIVACY_PUBLIC => "Public", MIDAS_PRIVACY_PRIVATE => "Private");
    foreach($privacyStatuses as $initialStatus)
      {
      foreach($privacyStatuses as $finalStatus)
        {
        $this->initializePrivacyStatus(array(), array($adminItem), $initialStatus);

        // try to set privacy with admin, should pass
        $this->resetAll();
        $this->params['token'] = $this->_loginAsUser($userDao);
        $this->params['method'] = 'midas.item.create';
        $this->params['name'] = 'writeitem';
        $this->params['uuid'] = $adminItem->getUuid();
        $this->params['privacy'] = $privacyStrings[$finalStatus];
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);

        $adminItem = $itemModel->load($adminItem->getItemId());
        $this->assertPrivacyStatus(array(), array($adminItem), $finalStatus);
        }
      }
    }

  /** Test searching for item by name */
  public function testItemSearchByName()
    {
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.searchbyname';
    $this->params['name'] = 'invalid name of an item';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEmpty($resp->data->items);

    // Anon user should not get search results for private item
    $this->resetAll();
    $this->params['method'] = 'midas.item.searchbyname';
    $this->params['name'] = 'name 2';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEmpty($resp->data->items);

    // Anon user should get search results for public item
    $this->resetAll();
    $this->params['method'] = 'midas.item.searchbyname';
    $this->params['name'] = 'name 1';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(count($resp->data->items), 1);

    // Logged-in user should get search results for private item that they have access to
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.searchbyname';
    $this->params['name'] = 'name 2';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(count($resp->data->items), 1);
    }

  /** Test bitstream edit function */
  public function testBitstreamEdit()
    {
    $oldBitstream = $this->Bitstream->load(1);
    $this->assertNotEquals($oldBitstream->getName(), 'newname.jpeg');
    $this->assertNotEquals($oldBitstream->getMimetype(), 'image/jpeg');

    // User without item write access should throw an exception
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.bitstream.edit';
    $this->params['name'] = 'fail';
    $this->params['id'] = '1';
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // Test getting a user by first name and last name
    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.bitstream.edit';
    $this->params['name'] = 'newname.jpeg';
    $this->params['mimetype'] = 'image/jpeg';
    $this->params['id'] = '1';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    $newBitstream = $this->Bitstream->load(1);
    $this->assertEquals($newBitstream->getName(), 'newname.jpeg');
    $this->assertEquals($newBitstream->getMimetype(), 'image/jpeg');
    }

  /** Test bitstream delete function */
  public function testBitstreamDelete()
    {
    $bitstreamsFile = $this->loadData('Bitstream', 'default');
    $bitstream_id = $bitstreamsFile[0]->getKey();
    // User without item write access should throw an exception
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.bitstream.delete';
    $this->params['id'] = $bitstream_id;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    // Test getting a user by first name and last name
    $this->resetAll();
    $this->params['token'] = $this->_loginAsAdministrator();
    $this->params['method'] = 'midas.bitstream.delete';
    $this->params['id'] = $bitstream_id;
    $resp = $this->_callJsonApi();
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);

    $bitstreamDao = $this->Bitstream->load($bitstream_id);
    $this->assertFalse($bitstreamDao, 'Bitstream should have been deleted, but was not.');
    }

  /* helper function for item.setmetadata calls */
  private function _callSetmetadata($itemId, $element, $value, $qualifier = null, $type = null, $revision = null, $failureCode = null)
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.setmetadata';
    $this->params['itemId'] = $itemId;
    $this->params['element'] = $element;
    $this->params['value'] = $value;
    if(isset($qualifier))
      {
      $this->params['qualifier'] = $qualifier;
      }
    if(isset($type))
      {
      $this->params['type'] = $type;
      }
    if(isset($revision))
      {
      $this->params['revision'] = $revision;
      }
    $resp = $this->_callJsonApi();
    if(isset($failureCode))
      {
      $this->_assertStatusFail($resp, $failureCode);
      }
    else
      {
      $this->_assertStatusOk($resp);
      }
    return $resp;
    }

  /* helper function for item.setmultiplemetadata calls */
  private function _callSetmultiplemetadata($itemId, $metadata, $count = null, $revision = null, $failureCode = null)
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.setmultiplemetadata';
    $this->params['itemid'] = $itemId;
    if(isset($count))
      {
      $this->params['count'] = $count;
      }
    else
      {
      $this->params['count'] = sizeof($metadata);
      }

    $index = 1;
    $keys = array('element', 'value', 'qualifier', 'type');
    foreach($metadata as $metadatum)
      {
      foreach($keys as $key)
        {
        if(array_key_exists($key, $metadatum))
          {
          $this->params[$key.'_'.$index] = $metadatum[$key];
          }
        }
      $index = $index + 1;
      }
    if(isset($revision))
      {
      $this->params['revision'] = $revision;
      }
    $resp = $this->_callJsonApi();
    if(isset($failureCode))
      {
      $this->_assertStatusFail($resp, $failureCode);
      }
    else
      {
      $this->_assertStatusOk($resp);
      }
    return $resp;
    }

  /* helper function for item.getmetadata calls */
  private function _callGetmetadata($itemId, $revision = null, $failureCode = null)
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.getmetadata';
    $this->params['id'] = $itemId;
    if(isset($revision))
      {
      $this->params['revision'] = $revision;
      }
    $resp = $this->_callJsonApi();
    if(isset($failureCode))
      {
      $this->_assertStatusFail($resp, $failureCode);
      }
    else
      {
      $this->_assertStatusOk($resp);
      }
    return $resp;
    }

  /* helper function for item.deletemetadata calls */
  private function _callDeletemetadata($itemId, $element, $qualifier = null, $type = null, $revision = null, $failureCode = null)
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.deletemetadata';
    $this->params['itemid'] = $itemId;
    $this->params['element'] = $element;
    if(isset($qualifier))
      {
      $this->params['qualifier'] = $qualifier;
      }
    if(isset($type))
      {
      $this->params['type'] = $type;
      }
    if(isset($revision))
      {
      $this->params['revision'] = $revision;
      }
    $resp = $this->_callJsonApi();
    if(isset($failureCode))
      {
      $this->_assertStatusFail($resp, $failureCode);
      }
    else
      {
      $this->_assertStatusOk($resp);
      }
    return $resp;
    }

  /* helper function for item.deletemetadata.all calls */
  private function _callDeletemetadataAll($itemId, $revision = null, $failureCode = null)
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginAsNormalUser();
    $this->params['method'] = 'midas.item.deletemetadata.all';
    $this->params['itemid'] = $itemId;
    if(isset($revision))
      {
      $this->params['revision'] = $revision;
      }
    $resp = $this->_callJsonApi();
    if(isset($failureCode))
      {
      $this->_assertStatusFail($resp, $failureCode);
      }
    else
      {
      $this->_assertStatusOk($resp);
      }
    return $resp;
    }

  /** Test item metadata functions */
  public function testItemMetadata()
    {
    $usersFile = $this->loadData('User', 'default');

    // add metadata to an invalid item, should be an error
    $element1 = "meta_element_1";
    $value1 = "meta_value_1";
    $this->_callSetmetadata("-1", $element1, $value1, null, null, null, MIDAS_INVALID_POLICY);

    // get metadata to an invalid item, should be an error
    $this->_callGetmetadata("-1", null, MIDAS_INVALID_POLICY);

    $multiElement1 = "multi_meta_element_1";
    $multiValue1 = "multi_meta_value_1";
    $multiElement2 = "multi_meta_element_2";
    $multiValue2 = "multi_meta_value_2";
    $multiElement3 = "multi_meta_element_3";
    $multiValue3 = "multi_meta_value_3";
    $metadata = array(array('element' => $multiElement1, 'value' => $multiValue1),
                array('element' => $multiElement2, 'value' => $multiValue2),
                array('element' => $multiElement3, 'value' => $multiValue3));
    $metadata_with_2 = array(array('element' => $multiElement1, 'value' => $multiValue1),
                       array('element' => $multiElement2, 'value' => $multiValue2));
    $metadata_mismatched = array(array('element' => $multiElement1, 'value' => $multiValue1),
                       array('element' => $multiElement2));

    // add multiple metadata to an invalid item, should be an error
    $this->_callSetmultiplemetadata("-1", $metadata, null, null, MIDAS_INVALID_POLICY);

    // delete metadata from an invalid item, should be an error
    $this->_callDeletemetadata("-1", $element1, null, null, null, MIDAS_INVALID_POLICY);

    // delete all metadata from an invalid item, should be an error
    $this->_callDeletemetadataAll("-1", null, MIDAS_INVALID_POLICY);

    // create a new item, it will have zero revisions
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

    // add metadata to this item, should be an error b/c no revisions
    $this->_callSetmetadata($generatedItemId, $element1, $value1, null, null, null, MIDAS_INVALID_POLICY);

    // add multiple metadata to this item, should be an error b/c no revisions
    $this->_callSetmultiplemetadata($generatedItemId, $metadata, null, null, MIDAS_INVALID_POLICY);
    // add multiple metadata that is mismatched with the count, should be an error
    $this->_callSetmultiplemetadata($generatedItemId, $metadata_with_2, 3, null, MIDAS_INVALID_PARAMETER);
    // add multiple metadata that is mismatched b/w element and value, should be an error
    $this->_callSetmultiplemetadata($generatedItemId, $metadata_mismatched, null, null, MIDAS_INVALID_PARAMETER);

    // get metadata on this item, should be an error b/c no revisions
    $this->_callGetmetadata($generatedItemId, null, MIDAS_INVALID_POLICY);

    // delete metadata on this item, should be an error b/c no revisions
    $this->_callDeletemetadata($generatedItemId, $element1, null, null, null, MIDAS_INVALID_POLICY);

    // delete all metadata from the last revision of this item, should be an error as no such revision exists
    $this->_callDeletemetadataAll($generatedItemId, null, MIDAS_INVALID_POLICY);

    // delete all metadata all revisions of this item, should be an error as no revisions exist
    $this->_callDeletemetadataAll($generatedItemId, "all", MIDAS_INVALID_POLICY);

    // add a revision to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 1');
    $this->Item->addRevision($itemDao, $revision);

    // add metadata to this item
    $this->_callSetmetadata($generatedItemId, $element1, $value1);

    // check that the values are correct, at the same time check getmetadata
    $resp = $this->_callGetmetadata($generatedItemId);
    $metadataArray = $resp->data;
    $this->assertEquals($metadataArray[0]->element, $element1, "Expected metadata element would be ".$element1);
    $this->assertEquals($metadataArray[0]->value, $value1, "Expected metadata value would be ".$value1);
    $this->assertEquals($metadataArray[0]->qualifier, '', "Expected metadata qualifier would be ''");
    $this->assertEquals($metadataArray[0]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // check the same call, this time passing revision = 1
    $resp = $this->_callGetmetadata($generatedItemId, "1");
    $metadataArray = $resp->data;
    $this->assertEquals($metadataArray[0]->element, $element1, "Expected metadata element would be ".$element1);
    $this->assertEquals($metadataArray[0]->value, $value1, "Expected metadata value would be ".$value1);
    $this->assertEquals($metadataArray[0]->qualifier, '', "Expected metadata qualifier would be ''");
    $this->assertEquals($metadataArray[0]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // add additional metadata
    $element2 = "meta_element_2";
    $value2 = "meta_value_2";
    $qualifier2 = "meta_qualifier_2";
    $this->_callSetmetadata($generatedItemId, $element2, $value2, $qualifier2);

     // check that the metadata was added
    $resp = $this->_callGetmetadata($generatedItemId, "1");
    $metadataArray = $resp->data;
    if($metadataArray[0]->element === $element1)
      {
      $ind1 = 0;
      $ind2 = 1;
      }
    else
      {
      $ind1 = 1;
      $ind2 = 0;
      }
    $this->assertEquals($metadataArray[$ind1]->element, $element1, "Expected metadata element would be ".$element1);
    $this->assertEquals($metadataArray[$ind1]->value, $value1, "Expected metadata value would be ".$value1);
    $this->assertEquals($metadataArray[$ind1]->qualifier, '', "Expected metadata qualifier would be ''");
    $this->assertEquals($metadataArray[$ind1]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);
    $this->assertEquals($metadataArray[$ind2]->element, $element2, "Expected metadata element would be ".$element2);
    $this->assertEquals($metadataArray[$ind2]->value, $value2, "Expected metadata value would be ".$value2);
    $this->assertEquals($metadataArray[$ind2]->qualifier, $qualifier2, "Expected metadata qualifier would be ".$qualifier2);
    $this->assertEquals($metadataArray[$ind2]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // add a revision 2 to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 2');
    $this->Item->addRevision($itemDao, $revision);

    // add metadata to rev 2
    $rev2element = "meta_element_rev_2";
    $rev2value = "meta_value_rev_2";
    $this->_callSetmetadata($generatedItemId, $rev2element, $rev2value);

    // get the metadata from rev 2
    $resp = $this->_callGetmetadata($generatedItemId);
    $metadataArray = $resp->data;
    $this->assertEquals($metadataArray[0]->element, $rev2element, "Expected metadata element would be ".$rev2element);
    $this->assertEquals($metadataArray[0]->value, $rev2value, "Expected metadata value would be ".$rev2value);
    $this->assertEquals($metadataArray[0]->qualifier, '', "Expected metadata qualifier would be ''");
    $this->assertEquals($metadataArray[0]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // get the metadata from rev 1, checking if revision param works
    $resp = $this->_callGetmetadata($generatedItemId, "1");
    $metadataArray = $resp->data;
    if($metadataArray[0]->element === $element1)
      {
      $ind1 = 0;
      $ind2 = 1;
      }
    else
      {
      $ind1 = 1;
      $ind2 = 0;
      }
    $this->assertEquals($metadataArray[$ind1]->element, $element1, "Expected metadata element would be ".$element1);
    $this->assertEquals($metadataArray[$ind1]->value, $value1, "Expected metadata value would be ".$value1);
    $this->assertEquals($metadataArray[$ind1]->qualifier, '', "Expected metadata qualifier would be ''");
    $this->assertEquals($metadataArray[$ind1]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);
    $this->assertEquals($metadataArray[$ind2]->element, $element2, "Expected metadata element would be ".$element2);
    $this->assertEquals($metadataArray[$ind2]->value, $value2, "Expected metadata value would be ".$value2);
    $this->assertEquals($metadataArray[$ind2]->qualifier, $qualifier2, "Expected metadata qualifier would be ".$qualifier2);
    $this->assertEquals($metadataArray[$ind2]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // add a revision 3 to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 3');
    $this->Item->addRevision($itemDao, $revision);
    // add a revision 4 to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 3');
    $this->Item->addRevision($itemDao, $revision);

    // add metadata to rev 3
    $rev3element = "meta_element_rev_3";
    $rev3value = "meta_value_rev_3";
    $this->_callSetmetadata($generatedItemId, $rev3element, $rev3value, null, null, '3');

    // check that revision 3 has the metadata
    $resp = $this->_callGetmetadata($generatedItemId, "3");
    $metadataArray = $resp->data;
    $this->assertEquals($metadataArray[0]->element, $rev3element, "Expected metadata element would be ".$rev3element);
    $this->assertEquals($metadataArray[0]->value, $rev3value, "Expected metadata value would be ".$rev3value);
    $this->assertEquals($metadataArray[0]->qualifier, '', "Expected metadata qualifier would be ''");
    $this->assertEquals($metadataArray[0]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // check that revision 4 doesn't have any metadata
    $resp = $this->_callGetmetadata($generatedItemId, "4");
    $this->assertTrue(is_array($resp->data), "Expected an empty array from the getmetadata call");
    $this->assertEquals(sizeof($resp->data), 0, "Expected an empty array from the getmetadata call, but size was ".sizeof($resp->data));

    // add a revision 5 to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 5');
    $this->Item->addRevision($itemDao, $revision);
    // add a revision 6 to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 6');
    $this->Item->addRevision($itemDao, $revision);
    // add a revision 7 to the item
    $revision = MidasLoader::newDao('ItemRevisionDao');
    $revision->setUser_id($usersFile[0]->getKey());
    $revision->setChanges('revision 7');
    $this->Item->addRevision($itemDao, $revision);

    // add multiple metadata to this item, revision 5 to test revision param
    $this->_callSetmultiplemetadata($generatedItemId, $metadata, null, '5');
    $resp = $this->_callGetmetadata($generatedItemId, "5");
    $metadataArray = $resp->data;
    $this->assertEquals(sizeof($resp->data), 3, "Expected an array of size 3, but size was ".sizeof($metadataArray));
    // check that all expected values are there
    foreach($metadata as $metadatum)
      {
      $found = false;
      foreach($metadataArray as $storedMetadatum)
        {
        if($storedMetadatum->element === $metadatum['element'] &&
           $storedMetadatum->value === $metadatum['value'])
          {
          $found = true;
          }
        }
      $this->assertTrue($found, "didn't find expected element ".$metadatum['element']);
      }

    // check that revision 7 doesn't have any metadata
    $resp = $this->_callGetmetadata($generatedItemId, "7");
    $this->assertTrue(is_array($resp->data), "Expected an empty array from the getmetadata call");
    $this->assertEquals(sizeof($resp->data), 0, "Expected an empty array from the getmetadata call, but size was ".sizeof($resp->data));

    // add multiple metadata without a revision, should go to the head revision
    $this->_callSetmultiplemetadata($generatedItemId, $metadata);
    $resp = $this->_callGetmetadata($generatedItemId);
    $metadataArray = $resp->data;
    $this->assertEquals(sizeof($metadataArray), 3, "Expected an array of size 3, but size was ".sizeof($metadataArray));
    // check that all expected values are there
    foreach($metadata as $metadatum)
      {
      $found = false;
      foreach($metadataArray as $storedMetadatum)
        {
        if($storedMetadatum->element === $metadatum['element'] &&
           $storedMetadatum->value === $metadatum['value'])
          {
          $found = true;
          }
        }
      $this->assertTrue($found, "didn't find expected element ".$metadatum['element']);
      }

    // delete metadata from revision 1, should leave 1 metadata on that revision
    $this->_callDeletemetadata($generatedItemId, $element1, null, null, "1");
    $resp = $this->_callGetmetadata($generatedItemId, "1");
    $metadataArray = $resp->data;
    $this->assertEquals($metadataArray[0]->element, $element2, "Expected metadata element would be ".$element2);
    $this->assertEquals($metadataArray[0]->value, $value2, "Expected metadata value would be ".$value2);
    $this->assertEquals($metadataArray[0]->qualifier, $qualifier2, "Expected metadata qualifier would be ".$qualifier2);
    $this->assertEquals($metadataArray[0]->metadatatype, MIDAS_METADATA_TEXT, "Expected metadata type would be ".MIDAS_METADATA_TEXT);

    // delete metadata without passing revision, should delete from head revision
    $this->_callDeletemetadata($generatedItemId, $multiElement1);
    $resp = $this->_callGetmetadata($generatedItemId);
    $metadataArray = $resp->data;
    // make sure 2 expected metadata rows are still there
    $this->assertEquals(sizeof($metadataArray), 2, "Expected an array of size 2, but size was ".sizeof($metadataArray));
    // remove the deleted row from $metadata before checking
    array_shift($metadata);
    foreach($metadata as $metadatum)
      {
      $found = false;
      foreach($metadataArray as $storedMetadatum)
        {
        if($storedMetadatum->element === $metadatum['element'] &&
           $storedMetadatum->value === $metadatum['value'])
          {
          $found = true;
          }
        }
      $this->assertTrue($found, "didn't find expected element ".$metadatum['element']);
      }

    // try to delete a non-existent metadata row, should return false
    $resp = $this->_callDeletemetadata($generatedItemId, "some_new_element");
    $this->assertTrue($resp->data == "", "Deleting a non-existent metadata row should return false.");

    // delete all metadata from revision 5 of this item
    $this->_callDeletemetadataAll($generatedItemId, "5");
    $resp = $this->_callGetmetadata($generatedItemId, "5");
    $this->assertEquals(sizeof($resp->data), 0, "Expected an empty array from the getmetadata call, but size was ".sizeof($resp->data));
    // head revision should still have 2 metadata rows (and other revisions have metadata also)
    $resp = $this->_callGetmetadata($generatedItemId);
    $this->assertEquals(sizeof($resp->data), 2, "Expected an array of size 2, but size was ".sizeof($metadataArray));

    // delete all metadata without passing revision, should delete from head
    $this->_callDeletemetadataAll($generatedItemId);
    $resp = $this->_callGetmetadata($generatedItemId);
    $this->assertEquals(sizeof($resp->data), 0, "Expected an empty array from the getmetadata call, but size was ".sizeof($resp->data));

    // delete all metadata from all revisions
    $this->_callDeletemetadataAll($generatedItemId, "all");
    $revisions = $itemDao->getRevisions();
    foreach($revisions as $revision)
      {
      $revisionNumber = $revision->getRevision();
      $resp = $this->_callGetmetadata($generatedItemId, $revisionNumber);
      $this->assertEquals(sizeof($resp->data), 0, "Expected an empty array from the getmetadata call for revision ".$revisionNumber.", but size was ".sizeof($resp->data));
      }

    // delete the newly created item
    $this->Item->delete($itemDao);
    }

  /**
   * Test the item.add.policygroup and item.remove.policygroup api calls.
   */
  public function testItemAddRemovePolicygroup()
    {
    $userModel = MidasLoader::loadModel('User');
    $groupModel = MidasLoader::loadModel('Group');

    $userDao = $userModel->load('1');
    $itemModel = MidasLoader::loadModel('Item');
    $readItem = $itemModel->load('1004');
    $writeItem = $itemModel->load('1005');
    $adminItem = $itemModel->load('1006');
    $nonAdmins = array($readItem, $writeItem);

    $params = array('method' => 'midas.item.add.policygroup',
                    'token' => $this->_loginAsUser($userDao));

    $deletioncommMemberGroup = $groupModel->load('3005');

    // try to add without admin, should fail
    foreach($nonAdmins as $item)
      {
      $this->resetAll();
      $params['item_id'] = $item->getItemId();
      $params['group_id'] = $deletioncommMemberGroup->getGroupId();
      $params['policy'] = 'Admin';
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // try to set an invalid policy, should fail
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['group_id'] = $deletioncommMemberGroup->getGroupId();
    $params['policy'] = 'Arithmatic';
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

    // add a policy to the item, check that the item has the policy
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['group_id'] = $deletioncommMemberGroup->getGroupId();
    $params['policy'] = 'Write';
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->success, "true", 'itemgrouppolicy addition did not work as expected.');

    $this->assertPolicygroupExistence(array(), array($adminItem), $deletioncommMemberGroup, MIDAS_POLICY_WRITE);

    // change the policy, check that the item has the current policy
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['group_id'] = $deletioncommMemberGroup->getGroupId();
    $params['policy'] = 'Read';
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->success, "true", 'itemgrouppolicy addition did not work as expected.');

    $this->assertPolicygroupExistence(array(), array($adminItem), $deletioncommMemberGroup, MIDAS_POLICY_READ);

    // test remove
    $params = array('method' => 'midas.item.remove.policygroup',
                    'token' => $params['token']);

    // try to remove without admin, should fail
    foreach($nonAdmins as $item)
      {
      $this->resetAll();
      $params['item_id'] = $item->getItemId();
      $params['group_id'] = $deletioncommMemberGroup->getGroupId();
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // remove the policy, check that it is gone
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['group_id'] = $deletioncommMemberGroup->getGroupId();
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->success, "true", 'itemgrouppolicy removal did not work as expected.');

    $this->assertPolicygroupNonexistence(array(), array($adminItem), $deletioncommMemberGroup);
    }

  /**
   * Test the item.add.policygroup and item.remove.policyuser api calls.
   */
  public function testItemAddRemovePolicyuser()
    {
    $userModel = MidasLoader::loadModel('User');

    $userDao = $userModel->load('1');
    $itemModel = MidasLoader::loadModel('Item');
    $readItem = $itemModel->load('1004');
    $writeItem = $itemModel->load('1005');
    $adminItem = $itemModel->load('1006');
    $nonAdmins = array($readItem, $writeItem);

    $params = array('method' => 'midas.item.add.policyuser',
                    'token' => $this->_loginAsUser($userDao));

    $targetUser = $userModel->load('2');

    // try to add without admin, should fail
    foreach($nonAdmins as $item)
      {
      $this->resetAll();
      $params['item_id'] = $item->getItemId();
      $params['user_id'] = $targetUser->getUserId();
      $params['policy'] = 'Admin';
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // try to set an invalid policy, should fail
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['user_id'] = $targetUser->getUserId();
    $params['policy'] = 'Arithmatic';
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_PARAMETER);

    // add a policy to the item, check that the item has the policy
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['user_id'] = $targetUser->getUserId();
    $params['policy'] = 'Write';
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->success, "true", 'itemuserpolicy addition did not work as expected.');

    $this->assertPolicyuserExistence(array(), array($adminItem), $targetUser, MIDAS_POLICY_WRITE);

    // change the policy on the item, check that the policy is correct
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['user_id'] = $targetUser->getUserId();
    $params['policy'] = 'Read';
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->success, "true", 'itemuserpolicy addition did not work as expected.');

    $this->assertPolicyuserExistence(array(), array($adminItem), $targetUser, MIDAS_POLICY_READ);

    // test remove
    $params = array('method' => 'midas.item.remove.policyuser',
                    'token' => $params['token']);

    // try to remove without admin, should fail
    foreach($nonAdmins as $item)
      {
      $this->resetAll();
      $params['item_id'] = $item->getItemId();
      $params['user_id'] = $targetUser->getUserId();
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);
      }

    // remove the policy, check that it is gone
    $this->resetAll();
    $params['item_id'] = $adminItem->getItemId();
    $params['user_id'] = $targetUser->getUserId();
    $this->params = $params;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals($resp->data->success, "true", 'itemuserpolicy removal did not work as expected.');

    $this->assertPolicyuserNonexistence(array(), array($adminItem), $targetUser);
    }

  /** Test the item.list.permissions method */
  public function testItemListPermissions()
    {
    $userModel = MidasLoader::loadModel('User');
    $userDao = $userModel->load('1');
    $itemModel = MidasLoader::loadModel('Item');
    $adminItem = $itemModel->load('1006');

    $params = array('method' => 'midas.item.list.permissions',
                    'token' => $this->_loginAsUser($userDao));
    $invalidItemId = -10;

    // test with item the user has admin over
    $requiredParams = array(
      array('name' => 'item_id', 'valid' => $adminItem->getItemId(), 'invalid' => $invalidItemId));

    $memberUser = $userModel->load('4');
    $modUser = $userModel->load('5');

    // first assert that these invalid users have the expected rights
    $this->assertFalse($itemModel->policyCheck($adminItem, null, MIDAS_POLICY_READ), 'anonymous user should not have read access to admin item');
    $this->assertFalse($itemModel->policyCheck($adminItem, null, MIDAS_POLICY_WRITE), 'anonymous user should not have write access to admin item');
    $this->assertFalse($itemModel->policyCheck($adminItem, null, MIDAS_POLICY_ADMIN), 'anonymous user should not have admin access to admin item');
    $this->assertTrue($itemModel->policyCheck($adminItem, $memberUser, MIDAS_POLICY_READ), 'member user should have read access to admin item');
    $this->assertFalse($itemModel->policyCheck($adminItem, $memberUser, MIDAS_POLICY_WRITE), 'member user should not have write access to admin item');
    $this->assertFalse($itemModel->policyCheck($adminItem, $memberUser, MIDAS_POLICY_ADMIN), 'member user should not have admin access to admin item');
    $this->assertTrue($itemModel->policyCheck($adminItem, $modUser, MIDAS_POLICY_READ), 'moderator user should have read access to admin item');
    $this->assertTrue($itemModel->policyCheck($adminItem, $modUser, MIDAS_POLICY_WRITE), 'moderator user should have write access to admin item');
    $this->assertFalse($itemModel->policyCheck($adminItem, $modUser, MIDAS_POLICY_ADMIN), 'moderator user should not have admin access to admin item');

    $invalidUsers = array($memberUser, $modUser, null);
    $this->exerciseInvalidCases($params['method'], $userDao, $invalidUsers, $requiredParams);

    // now with admin perms which are valid
    $this->assertTrue($itemModel->policyCheck($adminItem, $userDao, MIDAS_POLICY_ADMIN), 'admin user should have admin access to admin item');

    // first check both privacy statuses
    $privacyStatuses = array(MIDAS_PRIVACY_PUBLIC, MIDAS_PRIVACY_PRIVATE);

    foreach($privacyStatuses as $privacyStatus)
      {
      $this->initializePrivacyStatus(array(), array($adminItem), $privacyStatus);

      $this->resetAll();
      $params['item_id'] = $adminItem->getItemId();
      $this->params = $params;
      $resp = $this->_callJsonApi();
      $this->_assertStatusOk($resp);

      $this->assertPrivacyStatus(array(), array($adminItem), $privacyStatus);
      }

    // ensure user perms are correct from the most recent call
    $privilegeCodes = array("Admin" => MIDAS_POLICY_ADMIN, "Write" => MIDAS_POLICY_WRITE, "Read" => MIDAS_POLICY_READ);
    $userPolicies = $adminItem->getItempolicyuser();
    $apiUserPolicies = $resp->data->user;
    foreach($userPolicies as $userPolicy)
      {
      $user = $userPolicy->getUser();
      $userId = (string)$user->getUserId();
      $userFound = false;
      foreach($apiUserPolicies as $apiUserPolicy)
        {
        if($apiUserPolicy->user_id == $userId)
          {
          $userFound = true;
          $apiPolicyCode = $privilegeCodes[$apiUserPolicy->policy];
          $this->assertEquals($apiPolicyCode, $userPolicy->getPolicy());
          }
        }
      $this->assertTrue($userFound, 'API call missing user '. $userId);
      }
    // ensure group perms are correct
    $groupPolicies = $adminItem->getItempolicygroup();
    $apiGroupPolicies = $resp->data->group;
    foreach($groupPolicies as $groupPolicy)
      {
      $group = $groupPolicy->getGroup();
      $groupId = (string)$group->getGroupId();
      $groupFound = false;
      foreach($apiGroupPolicies as $apiGroupPolicy)
        {
        if($apiGroupPolicy->group_id == $groupId)
          {
          $groupFound = true;
          $apiPolicyCode = $privilegeCodes[$apiGroupPolicy->policy];
          $this->assertEquals($apiPolicyCode, $groupPolicy->getPolicy());
          }
        }
      $this->assertTrue($groupFound, 'API call missing group '. $groupId);
      }
    }
  }
