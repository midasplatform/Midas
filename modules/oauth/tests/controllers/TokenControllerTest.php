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

/** test oauth token controller */
class OauthTokenControllerTest extends ControllerTestCase
{
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'oauth');
    $this->enabledModules = array('api', 'oauth');
    $this->_models = array('User');

    parent::setUp();
    }

  /**
   * TODO stub
   */
  public function testStub()
    {
    }
}
