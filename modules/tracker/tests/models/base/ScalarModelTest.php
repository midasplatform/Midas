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

/** test ScalarModel */
class Tracker_ScalarModelTest extends DatabaseTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('default'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        parent::setUp();
    }

    /** testScalarModel */
    public function testScalarModel()
    {
        $uuidComponent = MidasLoader::loadComponent('Uuid');

        $communityId = '2000';
        $producerDisplayName = 'Test Producer';
        $producerRevision = 'deadbeef';
        $submitTime = 'now';
        $submissionUuid = $uuidComponent->generate();

        $userModel = MidasLoader::loadModel('User');
        $usersFile = $this->loadData('User', 'default');
        $userDao = $userModel->load($usersFile[0]->getKey());

        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        $producerDao = $producerModel->load(100);

        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        $submissionDao = $submissionModel->getOrCreateSubmission($producerDao, $submissionUuid);
        $submissionId = $submissionDao->getKey();

        // Create two scalars, each tied to a separate trend.

        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        $scalarModel = MidasLoader::loadModel('Scalar', 'tracker');

        $metric0trend = $trendModel->load(1);
        $scalarValue0 = '0';
        $params0 = array(
            'num_param' => 90,
            'text_param' => 'master',
            'null_param' => '',
        );
        $scalarDao0 = $scalarModel->addToTrend(
            $metric0trend,
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

        $metric1trend = $trendModel->load(2);
        $scalarValue1 = '1';
        $params1 = array(
            'num_param' => 92,
            'text_param' => 'dev',
            'null_param' => '',
        );
        $scalarDao1 = $scalarModel->addToTrend(
            $metric1trend,
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
            'null_param' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        $scalarDao1Params = $scalarDao1->getParams();
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

        $paramModel = MidasLoader::loadModel('Param', 'tracker');
        foreach ($scalarDao0Params as $scalarParam) {
            $scalarParamReloaded = $paramModel->load($scalarParam->getParamId());
            $this->assertFalse($scalarParamReloaded, 'Scalar param should have been deleted');
        }
        foreach ($scalarDao1Params as $scalarParam) {
            $scalarParamReloaded = $paramModel->load($scalarParam->getParamId());
            $this->assertFalse($scalarParamReloaded, 'Scalar param should have been deleted');
        }
    }
}
