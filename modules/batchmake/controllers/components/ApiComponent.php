<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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

/** Component for api methods */
class Batchmake_ApiComponent extends AppComponent
{


  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }


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
    // any values that aren't filled in, fill them in with a blank
    $expectedKeys = array("tmp_dir", "bin_dir", "script_dir", "app_dir", "data_dir", "condor_bin_dir");
    $configParams = array();
    foreach($expectedKeys as $propKey)
      {
      if(!isset($params[$propKey]))
        {
        $configParams[$propKey] = "";
        }
      else
        {
        $configParams[$propKey] = $params[$propKey];
        }
      }

    $componentLoader = new MIDAS_ComponentLoader();
    $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
    return $kwbatchmakeComponent->testconfig($configParams);
    }




} // end class




