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
require_once BASE_PATH.'/modules/batchmake/constant/module.php';
require_once BASE_PATH.'/modules/batchmake/tests/controllers/BatchmakeControllerTest.php';

/** config controller tests*/
class ConfigControllerTest extends BatchmakeControllerTest
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
    // for now just get the page
    $page = '/batchmake/index/index';
    $this->params = array();
    $this->getRequest()->setMethod('GET');
    $this->dispatchUrI($page);
    }

  }
