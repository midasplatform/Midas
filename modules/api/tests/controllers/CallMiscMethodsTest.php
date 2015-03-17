<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

require_once BASE_PATH.'/modules/api/tests/controllers/CallMethodsTestCase.php';

/** Tests the functionality of miscellaneous web API methods. */
class Api_CallMiscMethodsTest extends Api_CallMethodsTestCase
{
    /** Test the midas.version method. */
    public function testVersion()
    {
        $this->params['method'] = 'midas.version';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp->data->version, Zend_Registry::get('configDatabase')->version);
    }

    /** Test the midas.modules.list method. */
    public function testModulesList()
    {
        $this->params['method'] = 'midas.modules.list';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertNotEmpty($resp->data->modules);
        $this->assertTrue(in_array('api', $resp->data->modules));
    }

    /** Test the midas.methods.list method. */
    public function testMethodsList()
    {
        $this->params['method'] = 'midas.methods.list';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertNotEmpty($resp->data->methods);
        foreach ($resp->data->methods as $method) {
            $this->assertNotEmpty($method->name);
            $this->assertNotEmpty($method->help);
            $this->assertTrue(isset($method->help->description));
            $this->assertTrue(isset($method->help->params));
            $this->assertTrue(isset($method->help->example));
            $this->assertTrue(isset($method->help->return));

            // Test a specific method's params list
            if ($method->name == 'login') {
                $this->assertNotEmpty($method->help->params->appname);
                $this->assertNotEmpty($method->help->params->apikey);
                $this->assertNotEmpty($method->help->params->email);
            }
        }
    }

    /** Test the midas.info method. */
    public function testInfo()
    {
        $this->params['method'] = 'midas.info';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);

        // We should get version
        $this->assertEquals($resp->data->version, Zend_Registry::get('configDatabase')->version);

        // We should get modules list
        $this->assertNotEmpty($resp->data->modules);
        $this->assertTrue(in_array('api', $resp->data->modules));

        // We should get resources list
        $this->assertNotEmpty($resp->data->resources);
    }

    /** Test the midas.admin.database.cleanup method. */
    public function testAdminDatabaseCleanup()
    {
        $this->params['token'] = $this->_loginAsNormalUser();
        $this->params['method'] = 'midas.admin.database.cleanup';
        $resp = $this->_callJsonApi();
        $this->_assertStatusFail($resp, MIDAS_INVALID_POLICY);

        $this->resetAll();
        $this->params['token'] = $this->_loginAsAdministrator();
        $this->params['method'] = 'midas.admin.database.cleanup';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
    }
}
