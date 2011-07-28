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
