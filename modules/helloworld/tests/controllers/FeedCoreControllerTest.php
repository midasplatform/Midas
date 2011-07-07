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

/** feed test*/
class FeedControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User', 'Feed');
    $this->_daos = array('User');
    $this->enabledModules = array('helloworld');
    parent::setUp();
    
    }

  /** test index*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/feed");
    $this->assertController("feedCore");
    $this->assertAction("index");   
    
    if(strpos($this->getBody(), "This page replaces the normal feed page.") === false)
      {
      $this->fail('Unable to find body element');
      }
    }
  }
