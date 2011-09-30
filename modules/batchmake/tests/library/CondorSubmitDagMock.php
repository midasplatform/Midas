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
 * Mock object simulating condor_submit_dag executable.
 */
class Batchmake_CondorSubmitDagMock
{

  // Problematic to throw exceptions as many test cases expect exceptions
  // The way around this is to have application code throw exceptions with
  // a known tag string, and have test cases look for that tag


  /**
   * exec method with the same interface as that in KWUtils, will simulate
   * an execution of the condor_submit_dag executable for a few select test cases.
   * @param type $command
   * @param type $output
   * @param type $chdir
   * @param type $return_val
   */
  public function exec($command, &$output = null, $chdir = "", &$return_val = null)
    {
    // just doing the same things as the actual KWUtils exec to check
    // that this function will work the same as the exec function other
    // than the actual exec system call
    if(!empty($chdir) && is_dir($chdir))
      {
      if(!chdir($chdir))
        {
        throw new Zend_Exception("Failed to change directory: [".$chdir."]");
        }
      }
    // on Linux need to add redirection to handle stderr
    $redirect_error = KWUtils::isLinux() ? " 2>&1" : "";
    $command = KWUtils::escapeCommand($command);

    // all test cases should have the proper syntax, verify this
    if(!preg_match('/'.MIDAS_BATCHMAKE_CONDOR_SUBMIT_DAG.".*\'(\S*)\'/", $command, $matches))
      {
      throw new Zend_Exception('malformed condor_submit_dag command');
      }
    $scriptName = $matches[1];

    if(preg_match('/CompileReturnNonzero.dagjob/', $scriptName))
      {
      $return_val = -1;
      $output = array('1 error', '1');
      }
    elseif(preg_match('/Compiles.dagjob/', $scriptName))
      {
      // do a bit of checking
      // ensure the $scriptName exists in the $chdir
      if(!file_exists($chdir . '/' . $scriptName))
        {
        $return_val = -1;
        $output = array('1 error can not find '. $chdir . '/' . $scriptName, '1');
        return;
        }
      $return_val = 0;
      $output = array('');
      }
    else
      {
      throw new Zend_Exception('Unexpected dagjob test script: '.$scriptName);
      }
    }


} // end class