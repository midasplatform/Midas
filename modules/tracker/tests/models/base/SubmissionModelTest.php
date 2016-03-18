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
class Tracker_SubmissionModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('default'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');

        parent::setUp();
    }

    /** testScalarModel */
    public function testSubmissionModel()
    {
        /** @var Tracker_ParamModel $paramModel */
        $paramModel = MidasLoader::loadModel('Param', 'tracker');

        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');

        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $userModel->load($usersFile[0]->getKey());

        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);

        $producerRevision = 'deadbeef';
        $uuid0 = $uuidComponent->generate();
        $uuid1 = $uuidComponent->generate();

        $params0 = array(
            'num_param_subtest' => 90,
            'text_param_subtest' => 'master',
            'emptystring_param_subtest' => '',
            'null_param_subtest' => null,
        );
        /** @var Tracker_SubmissionDao $submissionDao0 */
        $submissionDao0 = $submissionModel->createSubmission($producerDao, $uuid0, '', $params0);
        $submissionDao0->setProducerRevision($producerRevision);
        $submissionDao0->setUserId($userDao->getKey());
        $submissionDao0->setBuildResultsUrl('http://buildresultsurl');
        $submissionDao0->setBranch('master');
        $submissionModel->save($submissionDao0);

        // Ensure value and params are properly set on scalar.
        $paramChecks = array(
            'num_param_subtest' => array('found' => false, 'type' => 'numeric', 'val' => 90),
            'text_param_subtest' => array('found' => false, 'type' => 'text', 'val' => 'master'),
            'emptystring_param_subtest' => array('found' => false, 'type' => 'text', 'val' => ''),
            'null_param_subtest' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        $submissionDao0Params = $submissionDao0->getParams();
        var_dump($submissionDao0Params);
        foreach ($submissionDao0Params as $param) {
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

        $params1 = array(
            'num_param_subtest' => 92,
            'text_param_subtest' => 'dev',
            'null_param_subtest' => null,
            'emptystring_param_subtest' => '',
        );
        /** @var Tracker_SubmissionDao $submissionDao0 */
        $submissionDao1 = $submissionModel->createSubmission($producerDao, $uuid1, '', $params1);
        $submissionDao1->setProducerRevision($producerRevision);
        $submissionDao1->setUserId($userDao->getKey());
        $submissionDao1->setBuildResultsUrl('http://buildresultsurl');
        $submissionDao1->setBranch('dev');
        $submissionModel->save($submissionDao1);

        // Ensure value and params are properly set on scalar.
        $paramChecks = array(
            'num_param_subtest' => array('found' => false, 'type' => 'numeric', 'val' => 92),
            'text_param_subtest' => array('found' => false, 'type' => 'text', 'val' => 'dev'),
            'emptystring_param_subtest' => array('found' => false, 'type' => 'text', 'val' => ''),
            'null_param_subtest' => array('found' => false, 'type' => 'text', 'val' => ''),
        );

        $submissionDao1Params = $submissionDao1->getParams();
        /** @var Tracker_ParamModel $param */
        foreach ($submissionDao1Params as $param) {
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

        // Delete scalars and ensure params are deleted.
        $submissionModel->delete($submissionDao0);
        $submissionModel->delete($submissionDao1);

        /** @var Tracker_ParamModel $submissionParam */
        foreach ($submissionDao0Params as $submissionParam) {
            $submissionParamReloaded = $paramModel->load($submissionParam->getParamId());
            $this->assertFalse($submissionParamReloaded, 'Submission param 0 should have been deleted');
        }
        /** @var Tracker_ParamModel $submissionParam */
        foreach ($submissionDao1Params as $submissionParam) {
            $submissionParamReloaded = $paramModel->load($submissionParam->getParamId());
            $this->assertFalse($submissionParamReloaded, 'Submission param 2 should have been deleted');
        }
    }
}
