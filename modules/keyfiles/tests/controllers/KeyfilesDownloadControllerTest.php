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
   * Test downloading of key files
   */
  public function testDownloadKeyfiles()
    {
    $usersFile = $this->loadData('User', 'default');
    $bitstreamsFile = $this->loadData('Bitstream', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());
    $bitstreamDao = $this->Bitstream->load($bitstreamsFile[0]->getKey());

    $url = '/keyfiles/download/bitstream?bitstreamId='.$bitstreamDao->getKey();

    // Should throw an exception for no bitstream parameter
    $this->dispatchUrI('/keyfiles/download/bitstream', null, true);

    // Should throw exception for invalid bitstream id
    $this->resetAll();
    $this->dispatchUrI('/keyfiles/download/bitstream?bitstreamId=98234', null, true);

    // Make sure we get the checksum as the response
    $this->resetAll();
    $this->dispatchUrI($url, $userDao);
    $this->assertEquals($bitstreamDao->getChecksum(), $this->getBody());
    }
}
