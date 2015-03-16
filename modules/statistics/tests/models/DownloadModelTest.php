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

/** Test the behavior of the statistics download model */
class Statistics_DownloadModelTest extends DatabaseTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'), 'statistics');
        $this->enabledModules = array('scheduler', 'statistics');
        $this->_models = array();

        parent::setUp();
    }

    /**
     * Test that we receive the correct daily totals from our model
     */
    public function testDailyTotals()
    {
        /** @var Statistics_DownloadModel $downloadModel */
        $downloadModel = MidasLoader::loadModel('Download', 'statistics');

        // Add 50 downloads 3 days ago
        for ($i = 0; $i < 50; $i++) {
            /** @var Statistics_DownloadDao $dao */
            $dao = MidasLoader::newDao('DownloadDao', 'statistics');
            $dao->setItemId(7);
            $dao->setIpLocationId(1);
            $dao->setDate(date('Y-m-d 01:'.str_pad($i, 2, '0', STR_PAD_LEFT).':00', strtotime('-3 day')));
            $downloadModel->save($dao);
        }
        // Add 20 downloads 2 days ago
        for ($i = 0; $i < 20; $i++) {
            /** @var Statistics_DownloadDao $dao */
            $dao = MidasLoader::newDao('DownloadDao', 'statistics');
            $dao->setItemId(7);
            $dao->setIpLocationId(1);
            $dao->setDate(date('Y-m-d 01:'.str_pad($i, 2, '0', STR_PAD_LEFT).':00', strtotime('-2 day')));
            $downloadModel->save($dao);
        }
        $arrayDownload = $downloadModel->getDailyCounts(
            array(7),
            date('Y-m-d H:i:s', strtotime('-20 day'.date('Y-m-d G:i:s'))),
            date('Y-m-d H:i:s')
        );
        $this->assertEquals(count($arrayDownload), 2);
        $this->assertEquals($arrayDownload[date('Y-m-d', strtotime('-3 day'))], 50);
        $this->assertEquals($arrayDownload[date('Y-m-d', strtotime('-2 day'))], 20);
    }
}
