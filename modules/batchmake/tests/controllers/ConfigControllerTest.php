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

// need to include the module constant for this test
require_once str_replace('tests', 'constant', str_replace('controllers', 'module.php', dirname(__FILE__)));
/** config controller tests*/
class ConfigControllerTest extends ControllerTestCase
  {

  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->enabledModules = array('batchmake');
    parent::setUp();
    }

  /** test index action*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/batchmake/config/index");
    $body = $this->getBody();
    
    $this->assertAction("index");
    $this->assertModule("batchmake");
    if(strpos($body, "Batchmake Configuration") === false)
      {
      $this->fail('Unable to find body element');
      }
    }

  }
