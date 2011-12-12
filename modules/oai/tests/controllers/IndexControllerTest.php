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

/** index controller tests*/
class IndexControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->enabledModules = array('oai');
    parent::setUp();
    }

  /** test index action*/
  public function testIndexAction()
    {
    $itemsFile = $this->loadData('Item', 'default');
    $this->params['verb'] = 'GetRecord';
    $this->params['identifier'] = 'oai:midas.foo.com:'.$itemsFile[0]->getUuid();
    $this->params['metadataPrefix'] = 'oai_dc';
    $this->dispatchUrI("/oai");
    $this->assertAction("index");
    $this->assertModule("oai");
    $body = $this->getBody();
    if(strpos($body, 'error') !== false)
      {
      $this->fail('GetRecord error found');
      }

    if(strpos($body, $itemsFile[0]->getUuid()) === false)
      {
      $this->fail('GetRecord unable to find uuid');
      }

    $this->resetAll();
    $this->params['verb'] = 'Identify';
    $this->dispatchUrI("/oai");
    $this->assertAction("index");
    $this->assertModule("oai");
    $body = $this->getBody();
    if(strpos($body, 'error') !== false)
      {
      $this->fail('Identify error found');
      }

    $this->resetAll();
    $this->params['verb'] = 'ListIdentifiers';
    $this->params['metadataPrefix'] = 'oai_dc';
    $this->dispatchUrI("/oai");
    $this->assertAction("index");
    $this->assertModule("oai");
    $body = $this->getBody();
    if(strpos($body, 'error') !== false)
      {
      $this->fail('ListIdentifiers error found');
      }

    if(strpos($body, $itemsFile[0]->getUuid()) === false)
      {
      $this->fail('ListIdentifiers unable to find uuid');
      }


    $this->resetAll();
    $this->params['verb'] = 'ListMetadataFormats';
    $this->params['identifier'] = 'oai:midas.foo.com:'.$itemsFile[0]->getUuid();
    $this->dispatchUrI("/oai");
    $this->assertAction("index");
    $this->assertModule("oai");
    $body = $this->getBody();
    if(strpos($body, 'error') !== false)
      {
      $this->fail('ListMetadataFormats error found');
      }

    if(strpos($body, $itemsFile[0]->getUuid()) === false)
      {
      $this->fail('ListMetadataFormats unable to find uuid');
      }

    $this->resetAll();
    $this->params['verb'] = 'ListSets';
    $this->dispatchUrI("/oai");
    $this->assertAction("index");
    $this->assertModule("oai");
    $body = $this->getBody();
    if(strpos($body, 'error') !== false)
      {
      $this->fail('ListSets error found');
      }

    $folderFiles = $this->loadData('Folder', 'default');
    if(strpos($body, $folderFiles[0]->getUuid()) === false)
      {
      $this->fail('ListSets unable to find uuid');
      }
    }
  }
