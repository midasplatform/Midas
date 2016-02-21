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

/** Test the scalar model. */
class Tracker_ScalarModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('default'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query("SELECT setval('tracker_submission_submission_id_seq', (SELECT MAX(submission_id) FROM tracker_submission)+1);");
            $db->query("SELECT setval('tracker_scalar_scalar_id_seq', (SELECT MAX(scalar_id) FROM tracker_scalar)+1);");
        }
        parent::setUp();
    }

    /** testScalarModel */
    public function testScalarModel()
    {
        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');

        $communityId = '2000';
        $producerDisplayName = 'Test Producer';
        $producerRevision = 'deadbeef';
        $submitTime = 'now';
        $submissionUuid = $uuidComponent->generate();

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $userModel->load($usersFile[0]->getKey());

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var Tracker_SubmissionDao $submissionDao */
        $submissionDao = $submissionModel->getOrCreateSubmission($producerDao, $submissionUuid);
        $submissionId = $submissionDao->getKey();

        // Create two scalars, each tied to a separate trend.
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        /** @var Tracker_ScalarModel $scalarModel */
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');

        /** @var Tracker_TrendDao $metricTrend0 */
        $metricTrend0 = $trendModel->load(1);
        $scalarValue0 = 0;
        $params0 = array(
            'num_param' => 90,
            'text_param' => 'master',
            'emptystring_param' => '',
            'null_param' => null,
        );
        /** @var Tracker_ScalarDao $scalarDao0 */
        $scalarDao0 = $scalarModel->addToTrend(
            $metricTrend0,
            $submitTime,
            $submissionId,
            $producerRevision,
            $scalarValue0,
            $userDao,
            true,
            true,
            'http://buildresultsurl',
            'master',
            $params0);

        // Ensure value and params are properly set on scalar.
        $paramChecks = array(
            'num_param' => array('found' => false, 'type' => 'numeric', 'val' => 90),
            'text_param' => array('found' => false, 'type' => 'text', 'val' => 'master'),
            'emptystring_param' => array('found' => false, 'type' => 'text', 'val' => ''),
            'null_param' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        $scalarDao0Params = $scalarDao0->getParams();
        foreach ($scalarDao0Params as $param) {
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

        $this->assertEquals($scalarDao0->getValue(), $scalarValue0);

        // Scalar the second.
        /** @var Tracker_TrendDao $metricTrend1 */
        $metricTrend1 = $trendModel->load(2);
        $scalarValue1 = 1;
        $params1 = array(
            'num_param' => 92,
            'text_param' => 'dev',
            'null_param' => null,
            'emptystring_param' => '',
        );
        /** @var Tracker_ScalarDao $scalarDao1 */
        $scalarDao1 = $scalarModel->addToTrend(
            $metricTrend1,
            $submitTime,
            $submissionId,
            $producerRevision,
            $scalarValue1,
            $userDao,
            true,
            true,
            'http://buildresultsurl',
            'dev',
            $params1);

        // Ensure value and params are properly set on scalar.
        $paramChecks = array(
            'num_param' => array('found' => false, 'type' => 'numeric', 'val' => 92),
            'text_param' => array('found' => false, 'type' => 'text', 'val' => 'dev'),
            'emptystring_param' => array('found' => false, 'type' => 'text', 'val' => ''),
            'null_param' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        $scalarDao1Params = $scalarDao1->getParams();
        /** @var Tracker_ParamModel $param */
        foreach ($scalarDao1Params as $param) {
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

        $this->assertEquals($scalarDao1->getValue(), $scalarValue1);

        // Delete scalars and ensure params are deleted.
        $scalarModel->delete($scalarDao0);
        $scalarModel->delete($scalarDao1);

        /** @var Tracker_ParamModel $paramModel */
        $paramModel = MidasLoader::loadModel('Param', 'tracker');
        /** @var Tracker_ParamModel $scalarParam */
        foreach ($scalarDao0Params as $scalarParam) {
            $scalarParamReloaded = $paramModel->load($scalarParam->getParamId());
            $this->assertFalse($scalarParamReloaded, 'Scalar param should have been deleted');
        }
        /** @var Tracker_ParamModel $scalarParam */
        foreach ($scalarDao1Params as $scalarParam) {
            $scalarParamReloaded = $paramModel->load($scalarParam->getParamId());
            $this->assertFalse($scalarParamReloaded, 'Scalar param should have been deleted');
        }
    }
}
