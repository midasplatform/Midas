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
class ApiControllerTest extends ControllerTestCase
  {

  /** set up tests */
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'api'); // module dataset
    $this->setupDatabase(array('default'), 'validation'); // module dataset
    $this->enabledModules = array('api', 'validation');
    $this->_models = array('User', 'Folder');
    $this->_daos = array('User', 'Folder');

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

    // **IMPORTANT** This will clear any params that were set before this
    // function was called
    $this->resetAll();
    return $resp->data->token;
    }

  /** test getAllDashboards */
  public function testGetAllDashboards()
    {
    $this->params['method'] = 'midas.validation.getalldashboards';
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $dashboards = $resp->data;
    $this->assertEquals(1, count($dashboards));
    $this->assertEquals($dashboards[0]->dashboard_id, "1");
    $this->assertEquals($dashboards[0]->owner_id, "1");
    $this->assertEquals($dashboards[0]->name, "foo");
    $this->assertEquals($dashboards[0]->description, "bar");
    $this->assertEquals($dashboards[0]->truthfolder_id, "1");
    $this->assertEquals($dashboards[0]->testingfolder_id, "2");
    $this->assertEquals($dashboards[0]->trainingfolder_id, "3");
    }

  /** test getDashboard */
  public function testGetDashboard()
    {
    $this->params['method'] = 'midas.validation.getdashboard';
    $this->params['dashboard_id'] = 1;
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $dashboard = $resp->data;
    $this->assertEquals($dashboard->dashboard_id, "1");
    $this->assertEquals($dashboard->owner_id, "1");
    $this->assertEquals($dashboard->name, "foo");
    $this->assertEquals($dashboard->description, "bar");
    $this->assertEquals($dashboard->truthfolder_id, "1");
    $this->assertEquals($dashboard->testingfolder_id, "2");
    $this->assertEquals($dashboard->trainingfolder_id, "3");
    }

  /** test getDashboard (failure case) */
  public function testGetDashboardFailure()
    {
    $this->params['method'] = 'midas.validation.getdashboard';
    $this->params['dashboard_id'] = 2;
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    $this->assertEquals($resp->stat, "fail");
    $this->assertEquals($resp->message,
                        "No dashboard found with that id.");
    $this->assertEquals($resp->code, -1);
    }

  /** test createDashboard */
  public function testCreateDashboard()
    {

    $this->params['method'] = 'midas.validation.createdashboard';
    $this->params['name'] = "testing123";
    $this->params['description'] = "testing456";
    $this->params['token'] = $this->_loginUsingApiKey();
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $dashboardId = $resp->data->dashboard_id;
    $this->resetAll();

    $this->params['method'] = 'midas.validation.getdashboard';
    $this->params['dashboard_id'] = $dashboardId;
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $dashboard = $resp->data;
    $this->assertEquals($dashboard->dashboard_id, $dashboardId);
    $this->assertEquals($dashboard->name, "testing123");
    $this->assertEquals($dashboard->description, "testing456");
    }

  /** test createDashboard (without admin creds)*/
  public function testCreateDashboardFailure()
    {
    $this->params['method'] = 'midas.validation.createdashboard';
    $this->params['name'] = "testing123";
    $this->params['description'] = "testing456";
    $this->request->setMethod('POST');
    $resp = $this->_callJsonApi();
    var_dump($resp);
    }

}
