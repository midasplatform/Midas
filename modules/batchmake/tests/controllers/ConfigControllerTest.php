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

  protected $kwBatchmakeComponent;
  protected $applicationConfig;
    
    
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User');
    //$this->_daos = array('User');//
    //$this->_moduleModels = array('Task');//
    $this->enabledModules = array('batchmake');

   
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $this->kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $this->kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $this->applicationConfig = $this->kwBatchmakeComponent->getApplicationConfigProperties();

    
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
    /*
    //  
    $usersFile = $this->loadData('User', 'default');
    var_dump($usersFile);
    $userDao = $this->User->load($usersFile[0]->getKey());
    echo $userDao->getKey();
    echo 'admin='.$userDao->getAdmin();
    $userDao->setAdmin(1);
    echo 'admin='.$userDao->getAdmin();
    $this->User->save($userDao);
    $userDao = $this->User->load($usersFile[0]->getKey());
    echo 'admin='.$userDao->getAdmin();
    
    

    
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    //$preKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();
    //$this->assertEquals(strlen($preKey), 32);

    
      // create a task
    //$taskDao = $this->Batchmake_Task->createTask($userDao);
    // check for app, how?
    // 
    // 
    // create a dir
    // copy bms and bmm to dir
    // 
    // 
    
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    echo "task:".$taskId." for user:".$userId;
    */  
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
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $this->assertTrue($kwBatchmakeComponent->isConfigCorrect());
    }  
  
  /**
   * tests that all the bmScripts that have been entered for testing are found
   */
  public function testGetBatchmakeScripts()
    {
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $foundTestScripts = $kwBatchmakeComponent->getBatchmakeScripts();
    $expectedTestScripts = array("Myscript2.bms","PixelCounter.bms","anotherscript.bms",
        "anotherscriptwitherrors.bms","bmmswitherrors.bms","myscript.bms","noscripts.bms");
    foreach($expectedTestScripts as $script)
      {
      $this->assertContains($script, $foundTestScripts);
      }
    }
    
  /**
   * @TODO some model testing, and better ways of loading the models
   * test creating a model and creating the right subdirs
   */ 
    
  public function testCreateSubDirectories()
    {
    // test creating directories, in the same setup as would be used for
    // batchmake processing.  so create a task, then create a nested
    // set of directories based on the taskId
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $applicationConfig = $kwBatchmakeComponent->getApplicationConfigProperties();
    $modelLoad = new MIDAS_ModelLoader();
    $batchmakeTaskModel = $modelLoad->loadModel('Task', 'batchmake');
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $taskDao = $batchmakeTaskModel->createTask($userDao);
    $userId = $taskDao->getUserId();
    $taskId = $taskDao->getKey();
    $subdirs = array(MIDAS_BATCHMAKE_SSP_DIR, $userId, $taskId);
    $tmpDir = $kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    // now check that all the subdirs have been created
    $pathToCheck = $applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . '/';
    $this->assertFileExists($pathToCheck);
    foreach($subdirs as $subdir)
      {
      $pathToCheck = $pathToCheck . '/' . $subdir;
      $this->assertFileExists($pathToCheck);
      $this->assertTrue(is_dir($pathToCheck));
      }
    }    

    
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
    
  protected function fASLDBSWCDtestcase($scriptDir, $tmpDir, $scriptName, $expectedSet)
    {
    // clear the directory of any existing files
    $this->clearDirFiles($tmpDir);  
    
    // try symlinking all the batchmake files starting with $scriptName
    $bmScriptsProcessed = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir,$tmpDir,$scriptName);
    
    // check that the correct batchmake scripts are there, and only those
    // easiest just to add '.' and '..' to expected list
    $expectedSet[] = '..';
    $expectedSet[] = '.';
    sort($expectedSet);

    $foundScripts = scandir($tmpDir);
    sort($foundScripts);
    
    $this->assertEquals($expectedSet, $foundScripts, "Expected batchmake scripts not found rooted from ".$scriptName);

    // add in '.' and '..' 
    $bmScriptsProcessed[] = '.';
    $bmScriptsProcessed[] = '..';
    sort($bmScriptsProcessed);
    
    // also check that the set of scripts returned from the method is this same set
    $this->assertEquals($expectedSet, $bmScriptsProcessed, "Expected batchmake scripts not equal to those returned from processing ".$scriptName);
    }
    
  protected function fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName)
    {
    try
      {
      // need to suppress error output to keep test from failing, despite exception being caught
      $bmScripts = @$this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir,$tmpDir,$scriptName);
      $this->fail('Expected an exception for $scriptName, but did not get one.');
      }  
    catch (Zend_Exception $ze)
      {
      // this is the correct behavior
      }
    }
    
    

  public function testFindAndSymLinkDependentBatchmakeScripts()
    {
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $applicationConfig = $kwBatchmakeComponent->getApplicationConfigProperties();
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
    $tmpDir = $kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);

    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    
  
    $scriptName = 'anotherscript.bms';
    $expectedSet = array("myscript.bms","Myscript2.bms",
         "anotherscript.bms","noscripts.bms","PixelCounter.bms");
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
    $expectedSet = array("nocycle1.bms","nocycle2.bms","nocycle3.bms");
    $this->fASLDBSWCDtestcase($scriptDir, $tmpDir, $scriptName, $expectedSet);
  
    // expect an exception, as this script has a simple cycle
    // 1->1
    $scriptName = 'cycle1.bms';
    $this->fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName);
    
    // check a script with a more complex cycle, 1->2, 1->3, 2->3, 3->2
    $scriptName = 'cycle31.bms';
    $this->fASLDBSWCDtestcaseException($scriptDir, $tmpDir, $scriptName);      
    }
    
    
  public function xtestFindAndSymLinkDependentBmms()
    {
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $applicationConfig = $kwBatchmakeComponent->getApplicationConfigProperties();
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
    $tmpDir = $kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    
    // now try symlinking all the batchmake files starting with anotherscript.bms
    $scriptName = 'anotherscript.bms';
    $bmScripts_anotherscript = $kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScripts($scriptDir,$tmpDir,$scriptName);
 
    
    $bmms = $kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts_anotherscript);
    // these come as [ name of app => script where found ]
    // convert them to a form useful for comparison
    $processedBmms_anotherscript = array();
    foreach($bmms as $bmm=>$script)
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
    sort( $foundBmms_anotherscript);
    
    $expectedBmms_anotherscript = array("AnotherApp.bmm","MyApp2.bmm",
      "PixelCounter.bmm","TestApp1.bmm","TestApp2.bmm","myapp.bmm");
    sort($expectedBmms_anotherscript);
    
    // compare the three arrays
    $this->assertEquals($processedBmms_anotherscript, $expectedBmms_anotherscript, "BMMs: processed != expected, for anotherscript.bms");
    $this->assertEquals($processedBmms_anotherscript, $foundBmms_anotherscript, "BMMs: processed != found, for anotherscript.bms");
  
    }
    
    
    
    
    
    
    
  public function xtestExec()
    {
    // not sure how to test this exactly, for now create a tmp dir, check
    // the value of pwd in it
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $kwBatchmakeComponent = new Batchmake_KWBatchmakeComponent();
    $kwBatchmakeComponent->setAlternateConfig(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    $applicationConfig = $kwBatchmakeComponent->getApplicationConfigProperties();
    $tmpDir = $applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY];
    // delete any old files used by this test
    $execDir = $tmpDir . '/' . 'exectmp';
    if(is_dir($execDir))
      {
      rmdir($execDir);
      }
    mkdir($execDir);
    $cmd = 'pwd';
    $chdir = $execDir;
    $kwBatchmakeComponent->exec($cmd, $output, $chdir, $returnVal);
    // $output should have one value, the same as execDir
    $this->assertEquals($execDir, $output[0]);
    // return_val should be 0
    $this->assertEquals($returnVal,0);
    } 
    
  public function xtestAppendStringIfNot()
    {
    // try one that doesn't have the suffix:
    $subject = 'blah';
    $ext = '.exe';
    $subject = $this->kwBatchmakeComponent->appendStringIfNot($subject, $ext);
    $this->assertEquals($subject,'blah.exe');
    // now try one that already has the suffix
    $subject = 'blah';
    $ext = '.exe';
    $subject = $this->kwBatchmakeComponent->appendStringIfNot($subject, $ext);
    $this->assertEquals($subject,'blah.exe');
    }

  public function xtestFindApp()
    {
    // first try something that should be in the path, php, and check that it
    // is executable
    $pathToApp = $this->kwBatchmakeComponent->findApp('php', true);
    // now try something that is unlikely to be in the path
    try
      {
      $pathToApp = $this->kwBatchmakeComponent->findApp('php_exe_that_is_vanishingly_likley_to_be_in_the_path', true);
      $this->fail('Should have caught exception but did not, testFindApp');
      }
    catch(Zend_Exception $ze)
      {
      // correct behavior
      }    
    }    
    
  public function xtestIsExecutable()
    {
    // this is tricky to test, as it is hard to make assumptions that hold
    // up across platforms
    //
    // for now assume that 'pwd' will not be found
    $this->assertFalse($this->kwBatchmakeComponent->isExecutable('pwd',false));
    // but 'pwd' will be found in the path
    $this->assertTrue($this->kwBatchmakeComponent->isExecutable('pwd',true));
    }

    
   
    
  public function xtestPrepareExecCommand()
    {
    $returnVal = $this->kwBatchmakeComponent->prepareExecCommand('php', array('blah1','blah2','blah3'));
    $appPath = $this->kwBatchmakeComponent->findApp('php',true);
    $this->assertEquals($returnVal,"'".$appPath."' 'blah1' 'blah2' 'blah3'");
    }

  public function xtestCompileBatchMakeScript()
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
    $tmpDir = $this->kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScripts($scriptDir,$tmpDir,$scriptName);
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
      // correct behavior
      }   
    
    // this one should work
    $this->kwBatchmakeComponent->compileBatchMakeScript($appDir, $binDir, $tmpDir, $scriptName);

    // now try a script that doesn't compile
    $scriptName = 'CompileErrors.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScripts($scriptDir,$tmpDir,$scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);
    try
      {
      $this->kwBatchmakeComponent->compileBatchMakeScript($appDir, $binDir, $tmpDir, $scriptName);
      $this->fail('Should have had a compile error but did not, testCompileBatchMakeScript');
      }
    catch(Zend_Exception $ze)
      {
      // correct behavior
      }    
      
    }

  public function xtestGenerateCondorDag()
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
    $tmpDir = $this->kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScripts($scriptDir,$tmpDir,$scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);
    
    // try to generate the Condor script
    $dagJobFile = $this->kwBatchmakeComponent->generateCondorDag($appDir, $tmpDir, $binDir, $scriptName);
    $this->assertEquals($dagJobFile,'Compiles.bms.dagjob');
    // check that dag files and condor job files were created
    $condorFiles = array($dagJobFile,'Compiles.1.bms.dagjob','Compiles.3.bms.dagjob','Compiles.5.bms.dagjob');
    foreach($condorFiles as $condorFile)
      {
      $this->assertFileExists($tmpDir.'/'.$condorFile);
      }
    // now look for some specific strings
    $contents = file_get_contents($tmpDir.'/'. 'Compiles.bms.dagjob');
    $dagjobStrings = array('Job job3', 'Job job5','PARENT job1 CHILD job3', 'PARENT job3 CHILD job5');
    foreach($dagjobStrings as $string)
      { 
      $this->assertTrue(preg_match("/".$string."/", $contents, $matches) === 1);
      }
    }
    
       
  public function xtestCondorSubmitDag()
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
    $tmpDir = $this->kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    $condorBinDir = $applicationConfig[MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScripts($scriptDir,$tmpDir,$scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);

    $dagScript = $this->kwBatchmakeComponent->generateCondorDag($appDir, $tmpDir, $binDir, $scriptName);
    $this->kwBatchmakeComponent->condorSubmitDag($condorBinDir, $tmpDir, $dagScript);
    // how to check this now?
    }
/*    
  public function testSubmitCondorJob()
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
    $tmpDir = $this->kwBatchmakeComponent->createSubDirectories($applicationConfig[MIDAS_BATCHMAKE_TMP_DIR_PROPERTY] . "/", $subdirs);
    
    $scriptDir = $applicationConfig[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $binDir = $applicationConfig[MIDAS_BATCHMAKE_BIN_DIR_PROPERTY];
    $appDir = $applicationConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    $condorBinDir = $applicationConfig[MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY];
      
    // a script that compiles
    $scriptName = 'Compiles.bms';
    $bmScripts = $this->kwBatchmakeComponent->findAndSymLinkDependentBatchmakeScripts($scriptDir,$tmpDir,$scriptName);
    $bmms = $this->kwBatchmakeComponent->findAndSymLinkDependentBmms($appDir, $tmpDir, $bmScripts);
    
    $submitFile = $this->kwBatchmakeComponent->generateCondorDagSubmit($condorBinDir, $appDir, $tmpDir, $binDir, $scriptName);
    $this->kwBatchmakeComponent->submitCondorJob($condorBinDir, $tmpDir, $submitFile);
    // now what to check for?
    }
 */     
  }
