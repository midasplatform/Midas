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

/** Tests the functionality of the web API methods */
class ApiCallMethodsTest extends ControllerTestCase
  {
  /** set up tests */
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'api'); // module dataset
    $this->enabledModules = array('api');
    $this->_models = array('User', 'Folder', 'Item');
    $this->_daos = array('User', 'Folder', 'Item');

    parent::setUp();
    }

  /** Invoke the JSON web API */
  private function _callJsonApi($sessionUser = null)
    {
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

  /** Authenticate using the default api key */
  private function _loginUsingApiKey()
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
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    // No user folders should be visible anonymously
    $this->assertEquals(count($resp->data), 0);

    $this->resetAll();
    $this->params['token'] = $this->_loginUsingApiKey();
    $this->params['method'] = 'midas.user.folders';
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(count($resp->data), 2);

    foreach($resp->data as $folder)
      {
      $this->assertEquals($folder->_model, 'Folder');
      $this->assertEquals($folder->parent_id, 1000);
      }
    $this->assertEquals($resp->data[0]->name, 'User 1 name Folder 2');
    $this->assertEquals($resp->data[1]->name, 'User 1 name Folder 3');
    }

  /** Test listing of visible communities */
  public function testCommunityList()
    {
    $this->resetAll();
    $this->params['token'] = $this->_loginUsingApiKey();
    $this->params['method'] = 'midas.community.list';

    $this->request->setMethod('POST');
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

  /** Test listing of child folders */
  public function testFolderChildren()
    {
    $this->resetAll();
    $token = $this->_loginUsingApiKey();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.folder.children';
    $this->params['id'] = 1000;
    $this->request->setMethod('POST');
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
    $this->request->setMethod('POST');
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
    $token = $this->_loginUsingApiKey();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.get';
    $this->params['id'] = $itemsFile[0]->getKey();
    $this->request->setMethod('POST');
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
    $token = $this->_loginUsingApiKey();
    $this->params['token'] = $token;
    $this->params['method'] = 'midas.item.get';
    $this->params['id'] = $itemsFile[0]->getKey();
    $this->params['head'] = 'true';
    $this->request->setMethod('POST');
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
    $this->request->setMethod('POST');
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
    $this->request->setMethod('POST');
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
  }
