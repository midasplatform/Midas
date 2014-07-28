<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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
    // Should throw an exception for no bitstream parameter
    $this->dispatchUrI('/keyfiles/download/batch', null, true);

    // Get some coverage on the batch controller
    $this->resetAll();
    $this->dispatchUrI('/keyfiles/download/batch?items=1-2-3-&folders=1000', null);
    $this->assertController('download');
    $this->assertAction('batch');
    }
  }
