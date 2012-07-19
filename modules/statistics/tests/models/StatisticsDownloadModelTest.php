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

/** Test the behavior of the statistics download model */
class StatisticsDownloadModelTest extends DatabaseTestCase
{
  /** set up tests*/
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
    $downloadModel = MidasLoader::loadModel('Download', 'statistics');

    // Add 50 downloads 3 days ago
    for($i = 0; $i < 50; $i++)
      {
      $dao = MidasLoader::newDao('DownloadDao', 'statistics');
      $dao->setItemId(7);
      $dao->setIpLocationId(1);
      $dao->setDate(date('Y-m-d 01:'.str_pad($i, 2, '0', STR_PAD_LEFT).':00', strtotime('-3 day')));
      $downloadModel->save($dao);
      }
    // Add 20 downloads 2 days ago
    for($i = 0; $i < 20; $i++)
      {
      $dao = MidasLoader::newDao('DownloadDao', 'statistics');
      $dao->setItemId(7);
      $dao->setIpLocationId(1);
      $dao->setDate(date('Y-m-d 01:'.str_pad($i, 2, '0', STR_PAD_LEFT).':00', strtotime('-2 day')));
      $downloadModel->save($dao);
      }
    $arrayDownload = $downloadModel->getDailyCounts(array(7), date('c', strtotime('-20 day'.date('Y-m-d G:i:s'))), date('c'));
    $this->assertEquals(count($arrayDownload), 2);
    $this->assertEquals($arrayDownload[date('Y-m-d', strtotime('-3 day'))], 50);
    $this->assertEquals($arrayDownload[date('Y-m-d', strtotime('-2 day'))], 20);
    }
}
