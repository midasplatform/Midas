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

/** Tests the functionality of the web API methods */
class ApiCallMethodsTest extends ControllerTestCase
  {
  /** set up tests */
  public function setUp()
    {
    $this->enabledModules = array('api');
    parent::setUp();
    }

  /** Make sure our index page lists out the methods */
  public function testWebApiHelpIndex()
    {
    $this->dispatchUrI($this->webroot.'api');
    $this->assertModule('api');
    $this->assertController('index');
    $this->assertAction('index');
    $this->assertQuery('ul.listmethods');
    $this->assertTrue(strpos($this->getBody(), 'midas.version') !== false);
    }
  }
