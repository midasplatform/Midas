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
class Batchmake_Executor
{
  public function exec($command, &$output = null, $chdir = "", &$return_val = null)
    {
    KWUtils::exec($command, $output, $chdir, $return_val);  
    }
} // end class

    
    
