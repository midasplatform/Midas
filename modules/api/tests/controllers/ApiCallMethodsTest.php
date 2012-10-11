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
  protected function _callJsonApi($sessionUser = null, $method = 'POST')
    {
    $this->request->setMethod($method);
    $this->dispatchUrI($this->webroot.'api/json', $sessionUser);
    return json_decode($this->getBody());
    }

  /** Make sure we got a good response from a web API call */
  protected function _assertStatusOk($resp)
    {
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);
    $this->assertTrue(isset($resp->data));
    }

  /** Make sure we failed with a given message from the API call */
  protected function _assertStatusFail($resp, $code, $message = false)
    {
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->stat, 'fail');
    $this->assertEquals($resp->code, $code);
    if($message !== false)
      {
      $this->assertEquals($resp->message, $message);
      }
    }

  /** helper function to login as the passed in user. */
  protected function _loginAsUser($userDao)
    {
    $userApiModel = MidasLoader::loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->params['method'] = 'midas.login';
    $this->params['email'] = $userDao->getEmail();
    $this->params['appname'] = 'Default';
    $this->params['apikey'] = $apiKey;

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(strlen($resp->data->token), 40);

    // **IMPORTANT** This will clear any params that were set before this function was called
    $this->resetAll();
    return $resp->data->token;
    }

  /** Authenticate using the default api key for user 1 */
  protected function _loginAsNormalUser()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    return $this->_loginAsUser($userDao);
    }

  /** Authenticate using the default api key */
  protected function _loginAsAdministrator()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());
    return $this->_loginAsUser($userDao);
    }

  }
