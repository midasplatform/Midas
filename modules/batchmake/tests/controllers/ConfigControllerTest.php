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

  protected $kwBatchmakeComponent;


  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User');
    $this->enabledModules = array('batchmake');
    parent::setUp();
    if(!isset($this->kwBatchmakeComponent))
      {
      require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
      $this->kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent($this->setupAndGetConfig());
      }
    }



  /** test index action*/
  public function testIndexAction()
    {
    $this->dispatchUrI("/batchmake/config/index");
    $body = $this->getBody();

    $this->assertModule("batchmake");
    $this->assertController('config');
    $this->assertAction("index");
    if(strpos($body, "Batchmake Configuration") === false)
      {
      $this->fail('Unable to find body element');
      }

    $this->assertQuery("form#configForm");
    $applicationConfig = $this->setupAndGetConfig();
    $this->params = array();
    $this->params[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] = $applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY];
    $this->params[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY] = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $this->params[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY] = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $this->params[MIDAS_BATCHMAKE_APP_DIR_PROPERTY] = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    $this->params[MIDAS_BATCHMAKE_DATA_DIR_PROPERTY] = $applicationConfig[MIDAS_BATCHMAKE_DATA_DIR_PROPERTY];
    $this->params[MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY] = $applicationConfig[MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY];
    // @TODO get these tests to a better state, testing more
    // luckily, almost all of the functionality goes through KWBatchmakeComponent
    // which is reasonably well tested
    $this->params['submit'] = 'submitConfig';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/batchmake/config", null, true);
    }


  }
