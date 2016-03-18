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

        parent::setUp();
    }

    /** testScalarModel */
    public function testScalarModel()
    {
        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');

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
        $metricTrend0 = $trendModel->load(1001);
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
            $submissionDao,
            $scalarValue0
        );


        $this->assertEquals($scalarDao0->getValue(), $scalarValue0);

        // Scalar the second.
        /** @var Tracker_TrendDao $metricTrend1 */
        $metricTrend1 = $trendModel->load(1002);
        $scalarValue1 = 1;

        /** @var Tracker_ScalarDao $scalarDao1 */
        $scalarDao1 = $scalarModel->addToTrend(
            $metricTrend1,
            $submissionDao,
            $scalarValue1
        );

        $this->assertEquals($scalarDao1->getValue(), $scalarValue1);
    }
}
