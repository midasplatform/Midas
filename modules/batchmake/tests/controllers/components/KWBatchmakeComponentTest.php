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
    $this->kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();      
    $this->kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $this->applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();
    }
    
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User');
    $this->enabledModules = array('batchmake');
    parent::setUp();
    }

    
  // TEST KWBATCHMAKE COMPONENTS
  // want a better way of doing this, but here is a start
    
  /**
   * tests config setup, relies on an alternate testing config to be defined,
   * these properties should all point to the batchmake module testfiles dirs.
   */
  // should put the kwBatchmakeComponent as an instance variable?
  // hopefully that would remove all of the redundant calls to setting it up
  public function testIsConfigCorrect()
    {
    $this->assertTrue($this->kwBatchmakeComponent->isConfigCorrect());
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
   * @TODO some model testing, and better ways of loading the models
   * test creating a model and creating the right subdirs
   */ 

 
   
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
  protected function fASLDBSWCDtestcase($scriptDir, $tmpDir, $scriptName, $expectedSet)
    {
    // clear the directory of any existing files
    $this->clearDirFiles($tmpDir);  
    
    // try symlinking all the batchmake files starting with $scriptName
    $bmScriptsProcessed = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
    
    // check that the correct batchmake scripts are there, and only those
    // easiest just to add '.' and '..' to expected list
    $expectedSet[] = '..';
    $expectedSet[] = '.';
    sort($expectedSet);

    $foundScripts = scandir($tmpDir);
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
  protected function fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName)
    {
    try
      {
      // need to suppress error output to keep test from failing, despite exception being caught
      $bmScripts = @$this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
      $this->fail('Expected an exception for $scriptName, but did not get one.');
      }  
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }
    }
    
    
  /** tests findAndSymLinkDependentBatchmakeScriptsWithCycleDetection. */
  public function testFindAndSymLinkDependentBatchmakeScriptsWithCycleDetection()
    {
    $applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();
    // create a task
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    // create a tmpDir based on the task and user
    $tmpDir = KWUtils::createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);

    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    
  
    $scriptName = 'anotherscript.bms';
    $expectedSet = array("myscript.bms", "Myscript2.bms",
         "anotherscript.bms", "noscripts.bms", "PixelCounter.bms");
    $this->fASLDBSWCDtestcase($scriptDir, $tmpDir, $scriptName, $expectedSet);
   
    $scriptName = "noscripts.bms";
    $expectedSet = array("noscripts.bms");
    $this->fASLDBSWCDtestcase($scriptDir, $tmpDir, $scriptName, $expectedSet);
    
    // try symlinking all the batchmake files starting with anotherscriptwitherrors.bms
    // expect an exception, as this script includes a non-existent script
    $scriptName = 'anotherscriptwitherrors.bms';
    $this->fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName);
    
    // cycle detection tests
    
    // check a script with no cycle,1->2, 1->3, 3->2
    // clear the directory of the symlinked files
    $scriptName = "nocycle1.bms";
    $expectedSet = array("nocycle1.bms", "nocycle2.bms", "nocycle3.bms");
    $this->fASLDBSWCDtestcase($scriptDir, $tmpDir, $scriptName, $expectedSet);
  
    // expect an exception, as this script has a simple cycle
    // 1->1
    $scriptName = 'cycle1.bms';
    $this->fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName);
    
    // check a script with a more complex cycle, 1->2, 1->3, 2->3, 3->2
    $scriptName = 'cycle31.bms';
    $this->fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName);      
    }
    
    
  /** tests findAndSymLinkDependentBmms */
  public function testFindAndSymLinkDependentBmms()
    {
    $applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();
    // create a task
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    // create a tmpDir based on the task and user
    $tmpDir = KWUtils::createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    
    // now try symlinking all the batchmake files starting with anotherscript.bms
    $scriptName = 'anotherscript.bms';
    $bmScripts_anotherscript = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
 
    
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts_anotherscript);
    // these come as [ name of app => script where found ]
    // convert them to a form useful for comparison
    $processedBmms_anotherscript = array();
    foreach($bmms as $bmm => $script)
      {
      $processedBmms_anotherscript[] = $bmm.'.bmm';    
      }
    sort($processedBmms_anotherscript);
    
    $globOutput = glob($tmpDir.'/*.bmm');
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
    // create a task
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    // create a tmpDir based on the task and user
    $applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();
    $tmpDir = KWUtils::createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);

    // first try with a bad path to BatchMake
    $badBinDir = '/a/dir/not/likely/to/exist';
    try
      {
      $this->kwBatchmakeComponent->compileBatchMakeScript($appDir, $badBinDir, $tmpDir, $scriptName);
      $this->fail('Should have not been able to find BatchMake, but did,  testCompileBatchMakeScript');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }   
    
    // this one should work
    $this->kwBatchmakeComponent->compileBatchMakeScript($appDir, $binDir, $tmpDir, $scriptName);

    // now try a script that doesn't compile
    $scriptName = 'CompileErrors.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);
    try
      {
      $this->kwBatchmakeComponent->compileBatchMakeScript($appDir, $binDir, $tmpDir, $scriptName);
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
    // create a task
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    // create a tmpDir based on the task and user
    $applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();
    $tmpDir = KWUtils::createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);
    
    // try to generate the Condor script
    $dagJobFile = $this->kwBatchmakeComponent->generateCondorDag($appDir, $tmpDir, $binDir, $scriptName);
    $this->assertEquals($dagJobFile, 'Compiles.bms.dagjob');
    // check that dag files and condor job files were created
    $condorFiles = array($dagJobFile, 'Compiles.1.bms.dagjob', 'Compiles.3.bms.dagjob', 'Compiles.5.bms.dagjob');
    foreach($condorFiles as $condorFile)
      {
      $this->assertFileExists($tmpDir.'/'.$condorFile);
      }
    // now look for some specific strings
    $contents = file_get_contents($tmpDir.'/'. 'Compiles.bms.dagjob');
    $dagjobStrings = array('Job job3', 'Job job5', 'PARENT job1 CHILD job3', 'PARENT job3 CHILD job5');
    foreach($dagjobStrings as $string)
      { 
      $this->assertTrue(preg_match("/".$string."/", $contents, $matches) === 1);
      }
    }
    
       
  /** tests function testCondorSubmitDag */
  public function testCondorSubmitDag()
    {
    // create a task
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    // create a tmpDir based on the task and user
    $applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();
    $tmpDir = KWUtils::createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    $condorBinDir = $applicationConfig[MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);

    $dagScript = $this->kwBatchmakeComponent->generateCondorDag($appDir, $tmpDir, $binDir, $scriptName);
    $this->kwBatchmakeComponent->condorSubmitDag($condorBinDir, $tmpDir, $dagScript);
    // how to check this now?
    }



    
    


    


    
   
    
    
    
  } // end class
