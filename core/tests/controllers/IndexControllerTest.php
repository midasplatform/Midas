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
/** test index controller*/
class IndexControllerTest extends ControllerTestCase
  {
  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    parent::setUp();
    }

  /** test index*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/index");
    $this->assertController("feed");
    $this->assertAction("index");   
    }

  }
