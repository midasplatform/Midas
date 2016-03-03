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

require_once BASE_PATH.'/core/tests/controllers/api/RestCallMethodsTestCase.php';

/** API test for tracker module ApiscalarComponent. */
class Tracker_ApiScalarComponentTest extends RestCallMethodsTestCase
{
    public $moduleName = 'tracker';

    /** Setup. */
    public function setUp()
    {
        $this->enabledModules = array('api', 'scheduler', $this->moduleName);
        $this->_models = array('Assetstore', 'Community', 'Setting', 'User');
        $this->setupDatabase(array('default'));
        $this->setupDatabase(array('default'), 'tracker');

        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('tracker_aggregate_metric_spec_aggregate_metric_spec_id_seq', (SELECT MAX(aggregate_metric_spec_id) FROM tracker_aggregate_metric_spec)+1);");
            $db->query("SELECT setval('tracker_scalar_scalar_id_seq', (SELECT MAX(scalar_id) FROM tracker_scalar)+1);");
        }

        ControllerTestCase::setUp();
    }

    /**
     * Test updating an existing scalar with a set of params, via PUT.
     *
     * @throws Zend_Exception
     */
    public function testPUT()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());

        // Create a scalar attached to a trend.
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        /** @var Tracker_TrendDao $trend */
        $trend = $trendModel->load(1001);

        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');
        $scalarArgs = array(
            'trend_id' => $trend->getTrendId(),
            'value' => 42,
            'build_results_url' => 'http://localhost',
        );
        /** @var Tracker_ScalarDao $scalar */
        $scalar = $scalarModel->initDao('Scalar', $scalarArgs, $this->moduleName);
        $scalarModel->save($scalar);

        $token = $this->_loginAsAdministrator();

        $params = array(
            'num_param' => 90,
            'text_param' => 'master',
            'null_param' => '',
        );
        $restParams = array(
            'trend_id' => $trend->getTrendId(),
            'params' => json_encode($params),
            'token' => $token,
        );

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('PUT', '/tracker/scalar/'.$scalar->getScalarId());

        // Ensure params are properly set on scalar.
        $paramChecks = array(
            'num_param' => array('found' => false, 'type' => 'numeric', 'val' => 90),
            'text_param' => array('found' => false, 'type' => 'text', 'val' => 'master'),
            'null_param' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        /** @var Tracker_ParamModel $param */
        foreach ($scalar->getParams() as $param) {
            $checks = $paramChecks[$param->getParamName()];
            $this->assertEquals($checks['type'], $param->getParamType());
            if ($checks['type'] === 'numeric') {
                $this->assertEquals($checks['val'], $param->getNumericValue());
            } else {
                $this->assertEquals($checks['val'], $param->getTextValue());
            }
            $paramChecks[$param->getParamName()]['found'] = true;
        }

        foreach ($paramChecks as $checks) {
            $this->assertTrue($checks['found']);
        }
    }
}
