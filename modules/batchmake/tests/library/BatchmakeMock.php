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

/** Mock object used to simulate BatchMake executable for testing. */
class Batchmake_BatchmakeMock
{
    protected $compileFlag = "'-c'";
    protected $generateDagFlag = "'--condor'";

    // Problematic to throw exceptions as many test cases expect exceptions
    // The way around this is to have application code throw exceptions with
    // a known tag string, and have test cases look for that tag

    /**
     * has the same interface as KWUtils.exec, used to simulate a BatchMake
     * executable for testing.
     *
     * @param string $command
     * @param null|mixed $output
     * @param string $chdir
     * @param null|mixed $return_val
     * @throws Zend_Exception
     */
    public function exec($command, &$output = null, $chdir = '', &$return_val = null)
    {
        // just doing the same things as the actual KWUtils exec to check
        // that this function will work the same as the exec function other
        // than the actual exec system call
        if (!empty($chdir) && is_dir($chdir)) {
            if (!chdir($chdir)) {
                throw new Zend_Exception('Failed to change directory: ['.$chdir.']');
            }
        }
        $command = KWUtils::escapeCommand($command);

        // now look for particular commands that this Mock object can service
        if (preg_match('/'.$this->compileFlag.'/', $command)) {
            $this->execCompile($command, $output, $chdir, $return_val);
        } elseif (preg_match('/'.$this->generateDagFlag.'/', $command)) {
            $this->execGenerateCondorDag($command, $output, $chdir, $return_val);
        } else {
            throw new Zend_Exception('unexepected BatchMake command flag:'.$command);
        }
    }

    /**
     * handler function with same interface as KWUtils.exec to simulate the
     * -c functionality of the BatchMake executable.
     *
     * @param  type $command
     * @param  type $output
     * @param  type $chdir
     * @param  type $return_val
     * @return type
     */
    public function execCompile($command, &$output = null, $chdir = '', &$return_val = null)
    {
        // all test cases should have the proper syntax, verify this
        if (!preg_match('/(.*)BatchMake(.*)\-ap(.*)\-p(.*)\-c(.*)/', $command, $matches)
        ) {
            throw new Zend_Exception('malformed BatchMake compile command');
        }

        // now perform particular logic to each of the 4 test cases
        $scriptnameParts = explode('/', $matches[5]);
        $scriptName = $scriptnameParts[count($scriptnameParts) - 1];

        if (preg_match('/CompileErrors.bms/', $scriptName)) {
            $return_val = 0;
            $output = array('1 error', '1');
        } elseif (preg_match('/CompileReturnNonzero.bms/', $scriptName)) {
            $return_val = -1;
            $output = array('1 error', '1');
        } elseif (preg_match('/CompileEmptyOutput.bms/', $scriptName)) {
            $return_val = 0;
            $output = array();
        } elseif (preg_match('/Compiles.bms/', $scriptName)) {
            $return_val = 0;
            $output = array('0 error', '10');
        } else {
            throw new Zend_Exception('Unexpected BatchMake test script: '.$scriptName);
        }
    }

    /**
     * handler function with same interface as KWUtils.exec to simulate the
     * --condor functionality of the BatchMake executable.
     *
     * @param string $command
     * @param null|mixed $output
     * @param string $chdir
     * @param null|mixed $return_val
     * @throws Zend_Exception
     */
    public function execGenerateCondorDag($command, &$output = null, $chdir = '', &$return_val = null)
    {
        // all test cases should have the proper syntax, verify this
        if (!preg_match('/(.*)BatchMake(.*)\-ap.*\'(\S*)\'.*\-p.*\'(\S*)\'.*\--condor(.*)/', $command, $matches)
        ) {
            throw new Zend_Exception('malformed BatchMake compile command');
        }

        // last two parts of the command are bms name and dag name
        $interestedParts = $matches[5];
        $filenames = explode(' ', $interestedParts);
        $scriptName = $filenames[1];

        if (preg_match('/CompileReturnNonzero.bms/', $scriptName)) {
            $return_val = -1;
            $output = array('1 error', '1');
        } elseif (preg_match("/'(.*)Compiles.bms'/", $scriptName, $scriptMatch)) {
            // do a bit of "Compile" type checking
            // check that the ap exists
            $ap = $matches[3];
            if (!is_dir($ap)) {
                // here we simulate compiler errors
                $return_val = 0;
                $output = array('1 error can not find '.$ap, '1');

                return;
            }
            // Compiles.bms refs PixelCounter, ensure PixelCounter.bmm exists in ap
            if (!file_exists($ap.'/PixelCounter.bmm')) {
                // here we simulate compiler errors
                $return_val = 0;
                $output = array('1 error can not find '.$ap.'/PixelCounter.bmm', '1');

                return;
            }
            // check that the p exists
            $p = $matches[4];
            if (!is_dir($p)) {
                // here we simulate compiler errors
                $return_val = 0;
                $output = array('1 error can not find '.$p, '1');

                return;
            }
            // check that the dagDir exists
            $dagDir = $scriptMatch[1];
            if (!is_dir($dagDir)) {
                // here we simulate compiler errors
                $return_val = 0;
                $output = array('1 error can not find '.$dagDir, '1');

                return;
            }
            // check that the bms exists
            $bmsScript = $dagDir.'/Compiles.bms';
            if (!file_exists($bmsScript)) {
                // here we simulate compiler errors
                $return_val = 0;
                $output = array('1 error can not find '.$bmsScript, '1');

                return;
            }
            // now create the needed dagfiles in it
            $condorFiles = array('Compiles.dagjob', 'Compiles.1.dagjob', 'Compiles.3.dagjob', 'Compiles.5.dagjob');
            foreach ($condorFiles as $condorFile) {
                $filePath = $dagDir.'/'.$condorFile;
                // create a blank file
                $asciifile = fopen($filePath, 'w');
                fclose($asciifile);
            }
            $return_val = 0;
            $output = array();
        } else {
            throw new Zend_Exception('Unexpected BatchMake test script: '.$scriptName);
        }
    }
}
