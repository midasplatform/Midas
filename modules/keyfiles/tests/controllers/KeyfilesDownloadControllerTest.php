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

/** test keyfiles download controller */
class KeyfilesDownloadControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->enabledModules = array('keyfiles');
    $this->_models = array('Bitstream', 'Item', 'User');

    parent::setUp();
    }

  /**
   * Test downloading of a single bitstream key file
   */
  public function testDownloadBitstreamKeyfile()
    {
    $usersFile = $this->loadData('User', 'default');
    $bitstreamsFile = $this->loadData('Bitstream', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());
    $bitstreamDao = $this->Bitstream->load($bitstreamsFile[0]->getKey());

    $url = '/keyfiles/download/bitstream?bitstreamId='.$bitstreamDao->getKey();

    // Should throw an exception for no bitstream parameter
    $this->dispatchUrI('/keyfiles/download/bitstream', null, true);

    // Make sure we get the checksum as the response
    $this->resetAll();
    $this->dispatchUrI($url, $userDao);
    $this->assertEquals($bitstreamDao->getChecksum(), $this->getBody());
    }

  /**
   * Test downloading of a recursive zip of keyfiles
   */
  public function testDownloadZip()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    // Should throw an exception for no bitstream parameter
    $this->dispatchUrI('/keyfiles/download/batch', null, true);

    // Get some coverage on the batch controller
    $this->resetAll();
    $this->dispatchUrI('/keyfiles/download/batch?items=1-2-3-&folders=1000', null);
    $this->assertController('download');
    $this->assertAction('batch');
    }
  }
