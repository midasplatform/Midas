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
include_once BASE_PATH . '/library/KWUtils.php';
/** Mock object class that will intercept calls to "exec" for testing purposes,
 *  as test setups may not have certain exes installed on the system that the
 *  exec will try to run.
 */
class Batchmake_ExecutorMock
  {

  protected $mockExes;

  /**
   * constructor
   */
  public function __construct()
    {
    // assign exe names to mock objects
    $this->mockExes = array();
    include_once BASE_PATH . '/modules/batchmake/tests/library/BatchmakeMock.php';
    $this->mockExes[MIDAS_BATCHMAKE_EXE] = new Batchmake_BatchmakeMock();
    include_once BASE_PATH . '/modules/batchmake/tests/library/CondorSubmitDagMock.php';
    $this->mockExes[MIDAS_BATCHMAKE_CONDOR_SUBMIT_DAG] = new Batchmake_CondorSubmitDagMock();
    // not yet implemented
    // and if we do implement them, double check the matching logic
    // since condor_submit will match condor_submit_dag
    //$this->mockExes[MIDAS_BATCHMAKE_CONDOR_SUBMIT] = null;
    //$this->mockExes[MIDAS_BATCHMAKE_CONDOR_QUEUE] = null;
    //$this->mockExes[MIDAS_BATCHMAKE_CONDOR_STATUS] = null;
    }


  /**
   * exec method with the same interface as that in KWUtils, will check the
   * command and intercept it if it is a known command with a Mock object
   * that should be used for testing, otherwise it will pass it on to KWUtils.
   * @param type $command
   * @param type $output
   * @param type $chdir
   * @param type $return_val
   */
  public function exec($command, &$output = null, $chdir = "", &$return_val = null)
    {
    $matched = false;
    foreach($this->mockExes as $exeName => $mockExe)
      {
      if(preg_match('/'.$exeName.'/', $command))
        {
        $matched = true;
        $mockExe->exec($command, $output, $chdir, $return_val);
        }
      }
    if(!$matched)
      {
      KWUtils::exec($command, $output, $chdir, $return_val);
      }
    }
} // end class



