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
    $this->enabledModules = array('example');
    parent::setUp();
    }

  /** test index action*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/example/index/index");
    $body = $this->getBody();
    $this->assertAction("index");
    $this->assertModule("example");
    if(strpos($body, "Welcome to the Example Module") === false)
      {
      $this->fail('Unable to find body element');
      }
    }
  }
