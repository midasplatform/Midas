<?php /*=========================================================================
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

/** Test the trend model. */
class Tracker_TrendModelTest extends DatabaseTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('aggregateMetric'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');
        parent::setUp();
    }

    /** testGetDistinctBranchesForMetricName */
    public function testGetDistinctBranchesForMetricName()
    {
        /** @var Tracker_TrendModel $trendModel */
        $trendModel = MidasLoader::loadModel('Trend', 'tracker');
        /** @var array $branches */
        $branches = $trendModel->getDistinctBranchesForMetricName(100, 'Greedy error');
        $this->assertEquals(count($branches), 2);
        $this->assertTrue(in_array('master', $branches));
        $this->assertTrue(in_array('test', $branches));
    }
}
