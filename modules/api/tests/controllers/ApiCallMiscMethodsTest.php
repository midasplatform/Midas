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
/** Tests the functionality of miscellaneous web API methods */
class ApiCallMiscMethodsTest extends ApiCallMethodsTest
  {
  /** set up tests */
  public function setUp()
    {
    parent::setUp();
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

  /**
   * Test the admin database cleanup method
   */
  public function testAdminDatabaseCleanup()
    {
    $this->params['method'] = 'midas.admin.database.cleanup';
    $this->params['token'] = $this->_loginAsNormalUser();
    $resp = $this->_callJsonApi();
    $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

    $this->resetAll();
    $this->params['method'] = 'midas.admin.database.cleanup';
    $this->params['token'] = $this->_loginAsAdministrator();
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    }
  }
