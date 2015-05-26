<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/
include_once BASE_PATH.'/library/KWUtils.php';

/** Mock object class that will intercept calls to "exec" for testing purposes,
 *  as test setups may not have certain exes installed on the system that the
 *  exec will try to run.
 */
class Batchmake_ExecutorMock
{
    protected $mockExes;

    /**
     * constructor.
     */
    public function __construct()
    {
        // assign exe names to mock objects
        $this->mockExes = array();
        include_once BASE_PATH.'/modules/batchmake/tests/library/BatchmakeMock.php';
        $this->mockExes[MIDAS_BATCHMAKE_EXE] = new Batchmake_BatchmakeMock();
        include_once BASE_PATH.'/modules/batchmake/tests/library/CondorSubmitDagMock.php';
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
     *
     * @param type $command
     * @param type $output
     * @param type $chdir
     * @param type $return_val
     */
    public function exec($command, &$output = null, $chdir = '', &$return_val = null)
    {
        $matched = false;
        foreach ($this->mockExes as $exeName => $mockExe) {
            if (preg_match('/'.$exeName.'/', $command)) {
                $matched = true;
                $mockExe->exec($command, $output, $chdir, $return_val);
            }
        }
        if (!$matched) {
            KWUtils::exec($command, $output, $chdir, $return_val);
        }
    }
} // end class
