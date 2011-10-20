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

/** Component for api methods */
class Remoteprocessing_ApiComponent extends AppComponent
{



  /**
   * @param tmp_dir the path to the batchmake temp dir
   * @param bin_dir the path to the batchmake bin dir, should have BatchMake exe
   * @param script_dir the path to the batchmake script dir, where bms files live
   * @param app_dir the path to the dir housing executables
   * @param data_dir the path to the data export dir
   * @param condor_bin_dir the path to the location of the condor executables
   * @return an array, the first value is a 0 if the config is incorrect or 1
   * if the config is correct, the second value is a list of individual config values and their statuses.
   */
  public function testconfig($params)
    {

    }




} // end class




