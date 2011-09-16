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



/**
 * KWBatchmakeComponent tests
 */
class KWBatchmakeComponentTest extends ControllerTestCase
  {

  protected $kwBatchmakeComponent;
  protected $applicationConfig;

  /** constructor */
  public function __construct()
    {
    // need to include the module constant for this test
    require_once BASE_PATH.'/modules/batchmake/constant/module.php';
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $this->kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    }

  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User');
    $this->enabledModules = array('batchmake');
    parent::setUp();
    }

  /**
   * tests config setup, relies on an alternate testing config to be defined,
   * these properties should all point to the batchmake module testfiles dirs.
   */
  public function testIsConfigCorrect()
    {
    $this->assertTrue($this->kwBatchmakeComponent->isConfigCorrect());
    // start out with know correct set
    $badConfigVals = $this->kwBatchmakeComponent->loadConfigProperties(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    // change a value to something bad
    $badConfigVals[MIDAS_BATCHMAKE_DATA_DIR_PROPERTY] = '/unlikely/to/work/right';
    $this->assertFalse($this->kwBatchmakeComponent->isConfigCorrect($badConfigVals));
    }

  /**
   * tests that all the bmScripts that have been entered for testing are found
   */
  public function testGetBatchmakeScripts()
    {
    $foundTestScripts = $this->kwBatchmakeComponent->getBatchmakeScripts();
    sort($foundTestScripts);
    $expectedTestScripts = array("Compiles.bms", "Myscript2.bms", "noscripts.bms",
        "anotherscript.bms", "anotherscriptwitherrors.bms", "bmmswitherrors.bms",
        "cycle1.bms", "cycle31.bms", "cycle32.bms", "cycle33.bms", "nocycle1.bms",
        "nocycle2.bms", "nocycle3.bms", "myscript.bms", "PixelCounter.bms",
        "CompileErrors.bms");
    sort($expectedTestScripts);
    $this->assertEquals($foundTestScripts, $expectedTestScripts);
    }

  /**
   * helper function to clear out any files in a directory
   */
  protected function clearDirFiles($dirToClear)
    {
    foreach(scandir($dirToClear) as $filename)
      {
      if($filename && $filename != '.' && $filename != '..')
        {
        unlink($dirToClear.'/'.$filename);
        }
      }
    }


  /**
   * helper function to run a test case
   */
  protected function preparePipelineScriptsTestcase($workDir, $scriptName, $expectedSet)
    {
    // clear the directory of any existing files
    $this->clearDirFiles($workDir);

    // try symlinking all the batchmake files starting with $scriptName
    $bmScriptsProcessed = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);

    // check that the correct batchmake scripts are there, and only those
    // easiest just to add '.' and '..' to expected list
    $expectedSet[] = '..';
    $expectedSet[] = '.';
    sort($expectedSet);

    $foundScripts = scandir($workDir);
    sort($foundScripts);

    $this->assertEquals($expectedSet, $foundScripts,
            "Expected batchmake scripts not found rooted from ".$scriptName);

    // add in '.' and '..'
    $bmScriptsProcessed[] = '.';
    $bmScriptsProcessed[] = '..';
    sort($bmScriptsProcessed);

    // also check that the set of scripts returned from the method is this same set
    $this->assertEquals($expectedSet, $bmScriptsProcessed,
            "Expected batchmake scripts not equal to those returned from processing ".$scriptName);
    }

  /**
   * helper function to run a test case that is expected to throw an exception
   */
  protected function preparePipelineScriptsTestcaseException($workDir, $scriptName)
    {
    try
      {
      // need to suppress error output to keep test from failing, despite exception being caught
      $bmScripts = @$this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
      $this->fail('Expected an exception for $scriptName, but did not get one.');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }
    }


  /** tests preparePipelineScripts, and exercises createTask. */
  public function testPreparePipelineScripts()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $workDir = $this->kwBatchmakeComponent->createTask($userDao);

    $scriptName = 'anotherscript.bms';
    $expectedSet = array("myscript.bms", "Myscript2.bms",
         "anotherscript.bms", "noscripts.bms", "PixelCounter.bms");
    $this->preparePipelineScriptsTestcase($workDir, $scriptName, $expectedSet);

    $scriptName = "noscripts.bms";
    $expectedSet = array("noscripts.bms");
    $this->preparePipelineScriptsTestcase($workDir, $scriptName, $expectedSet);

    // try symlinking all the batchmake files starting with anotherscriptwitherrors.bms
    // expect an exception, as this script includes a non-existent script
    $scriptName = 'anotherscriptwitherrors.bms';
    $this->preparePipelineScriptsTestcaseException($workDir, $scriptName);

    // cycle detection tests

    // check a script with no cycle,1->2, 1->3, 3->2
    // clear the directory of the symlinked files
    $scriptName = "nocycle1.bms";
    $expectedSet = array("nocycle1.bms", "nocycle2.bms", "nocycle3.bms");
    $this->preparePipelineScriptsTestcase($workDir, $scriptName, $expectedSet);

    // expect an exception, as this script has a simple cycle
    // 1->1
    $scriptName = 'cycle1.bms';
    $this->preparePipelineScriptsTestcaseException($workDir, $scriptName);

    // check a script with a more complex cycle, 1->2, 1->3, 2->3, 3->2
    $scriptName = 'cycle31.bms';
    $this->preparePipelineScriptsTestcaseException($workDir, $scriptName);
    }


  /** tests preparePipelineBmms */
  public function testPreparePipelineBmms()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $workDir = $this->kwBatchmakeComponent->createTask($userDao);

    // try a script that refers to a non-existant bmm
    $scriptName = 'bmmswitherrors.bms';
    $bmScripts = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
    try
      {
      $bmms = $this->kwBatchmakeComponent->preparePipelineBmms($workDir, $bmScripts);
      $this->fail('Expected an exception for '.$scriptName.', but did not get one.');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }

    // now try symlinking all the batchmake files starting with anotherscript.bms
    $scriptName = 'anotherscript.bms';
    $bmScripts_anotherscript = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->preparePipelineBmms($workDir, $bmScripts_anotherscript);
    // these come as [ name of app => script where found ]
    // convert them to a form useful for comparison
    $processedBmms_anotherscript = array();
    foreach($bmms as $bmm => $script)
      {
      $processedBmms_anotherscript[] = $bmm.'.bmm';
      }
    sort($processedBmms_anotherscript);

    $globOutput = glob($workDir.'/*.bmm');
    // strip off the path
    $foundBmms_anotherscript = array();
    foreach($globOutput as $bmm)
      {
      $foundBmms_anotherscript[] = basename($bmm);
      }
    sort($foundBmms_anotherscript);

    $expectedBmms_anotherscript = array("AnotherApp.bmm", "MyApp2.bmm",
      "PixelCounter.bmm", "TestApp1.bmm", "TestApp2.bmm", "myapp.bmm");
    sort($expectedBmms_anotherscript);

    // compare the three arrays
    $this->assertEquals($processedBmms_anotherscript, $expectedBmms_anotherscript, "BMMs: processed != expected, for anotherscript.bms");
    $this->assertEquals($processedBmms_anotherscript, $foundBmms_anotherscript, "BMMs: processed != found, for anotherscript.bms");
    }







  /** tests testCompileBatchMakeScript */
  public function testCompileBatchMakeScript()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $workDir = $this->kwBatchmakeComponent->createTask($userDao);

    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->preparePipelineBmms($workDir, $bmScripts);

    // this one should work
    $this->kwBatchmakeComponent->compileBatchMakeScript($workDir, $scriptName);

    // now try a script that doesn't compile
    $scriptName = 'CompileErrors.bms';
    $bmScripts = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->preparePipelineBmms($workDir, $bmScripts);
    try
      {
      $this->kwBatchmakeComponent->compileBatchMakeScript($workDir, $scriptName);
      $this->fail('Should have had a compile error but did not, testCompileBatchMakeScript');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }

    }

  /** tests generateCondorDag */
  public function testGenerateCondorDag()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $workDir = $this->kwBatchmakeComponent->createTask($userDao);

    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->preparePipelineBmms($workDir, $bmScripts);

    // try to generate the Condor script
    $dagJobFile = $this->kwBatchmakeComponent->generateCondorDag($workDir, $scriptName);
    $this->assertEquals($dagJobFile, 'Compiles.bms.dagjob');
    // check that dag files and condor job files were created
    $condorFiles = array($dagJobFile, 'Compiles.1.bms.dagjob', 'Compiles.3.bms.dagjob', 'Compiles.5.bms.dagjob');
    foreach($condorFiles as $condorFile)
      {
      $this->assertFileExists($workDir.'/'.$condorFile);
      }
    // now look for some specific strings
    $contents = file_get_contents($workDir.'/'. 'Compiles.bms.dagjob');
    $dagjobStrings = array('Job job3', 'Job job5', 'PARENT job1 CHILD job3', 'PARENT job3 CHILD job5');
    foreach($dagjobStrings as $string)
      {
      $this->assertTrue(preg_match("/".$string."/", $contents, $matches) === 1);
      }
    }


  /** tests function testCondorSubmitDag */
  public function testCondorSubmitDag()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $workDir = $this->kwBatchmakeComponent->createTask($userDao);

    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->preparePipelineScripts($workDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->preparePipelineBmms($workDir, $bmScripts);

    $dagScript = $this->kwBatchmakeComponent->generateCondorDag($workDir, $scriptName);
    $this->kwBatchmakeComponent->condorSubmitDag($workDir, $dagScript);
    // how to check this now?
    }


  } // end class
