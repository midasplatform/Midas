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

/**
 * an executor class for the batchmake module, used to forward calls
 * to exec on to KWUtils.
 */
class Batchmake_Executor
{
  /**
   * forwards a call to this method on to KWUtils.exec, with the same
   * method signature.
   * @param type $command
   * @param type $output
   * @param type $chdir
   * @param type $return_val
   */
  public function exec($command, &$output = null, $chdir = "", &$return_val = null)
    {
    KWUtils::exec($command, $output, $chdir, $return_val);
    }
} // end class



