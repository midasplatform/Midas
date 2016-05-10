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

/** Test the submission model. */
class Tracker_SubmissionModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('default'), 'tracker'); // module dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');

        parent::setUp();
    }

    /** testSubmissionModel */
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
        /** @var Tracker_SubmissionDao $submissionDao1 */
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

    /** testGetTabularSubmissionDetails */
    public function testGetTabularSubmissionDetails()
    {
        /** @var Tracker_SubmissionModel $submissionModel */
        $submissionModel = MidasLoader::loadModel('Submission', 'tracker');
        /** @var Tracker_SubmissionDao $submissionDao1 */
        $submission1Dao = $submissionModel->load(1);
        /** @var Tracker_SubmissionDao $submissionDao2 */
        $submission2Dao = $submissionModel->load(2);
        /** @var Tracker_SubmissionDao $submissionDao8 */
        $submission8Dao = $submissionModel->load(8);

        /** @var Tracker_ProducerModel $producerModel */
        $producerModel = MidasLoader::loadModel('Producer', 'tracker');
        /** @var Tracker_ProducerDao $producerDao */
        $producerDao = $producerModel->load(100);

        $submission1KeyTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao);
        // 40 key metrics plus header row
        $this->assertEquals(41, count($submission1KeyTable));

        $submission1AllTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, false);
        // 40 key metrics plus 1 non-key metric row plus header row
        $this->assertEquals(42, count($submission1AllTable));

       // Need to back up by 2 days since the previous submission is exactly 24 hours before.
        $submission1_1day_KeyTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, true, 2);
        // 40 key metrics per submission plus header row
        $this->assertEquals(81, count($submission1_1day_KeyTable));

        // Need to back up by 2 days since the previous submission is exactly 24 hours before.
        $submission1_1day_AllTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, false, 2);
        // 40 key metrics per submission plus one non-key metric per submission plus header row
        $this->assertEquals(83, count($submission1_1day_AllTable));

        // Back up by 3 days.
        $submission1_3day_KeyTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, true, 3);
        // 40 key metrics per first two submissions
        // 8 key metric for third submission
        // plus header row
        $this->assertEquals(89, count($submission1_3day_KeyTable));

        // Back up by 3 days.
        $submission1_3day_AllTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, false, 3);
        // 40 key metrics per first two submissions
        // 8 key metric for third submission
        // 1 non-key metric per first two submissions
        // plus header row
        $this->assertEquals(91, count($submission1_3day_AllTable));

        // Get them all.
        $submission1_10day_KeyTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, true, 10);
        // 40 key metrics per first two submissions
        // 8 key metric for (3, 4, 5, 6, 7) submission
        // plus header row
        $this->assertEquals(121, count($submission1_10day_KeyTable));

        // Get them all.
        $submission1_10day_AllTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission1Dao, false, 10);
        // 40 key metrics per first two submissions
        // 8 key metric for (3, 4, 5, 6, 7) submission
        // 1 non-key metric per first two submissions
        // plus header row
        $this->assertEquals(123, count($submission1_10day_AllTable));

        // Ignore submission 1.
        $submission2_10day_KeyTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission2Dao, true, 10);
        // 40 key metrics for submission 2
        // 8 key metric for (3, 4, 5, 6, 7) submission
        // plus header row
        $this->assertEquals(81, count($submission2_10day_KeyTable));

        // Get them all.
        $submission2_10day_AllTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission2Dao, false, 10);
        // 40 key metrics for submission 2
        // 8 key metric for (3, 4, 5, 6, 7) submission
        // 1 non-key metric per first two submissions
        // plus header row
        $this->assertEquals(82, count($submission2_10day_AllTable));

        // A different branch with only 1 submission of 1 key metric scalar, plus the header row.
        $submission8_10day_KeyTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission8Dao, true, 10);
        $this->assertEquals(2, count($submission8_10day_KeyTable));
        $submission8_10day_AllTable = $submissionModel->getTabularSubmissionDetails($producerDao, $submission8Dao, false, 10);
        $this->assertEquals(2, count($submission8_10day_AllTable));
    }
}
