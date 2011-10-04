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
   * if the config is correct, the second value is a list of individual
   * config values and their status.
   */
  public function testConfig($value)
    {
    // any values that aren't filled in, fill them in with a blank
    $expectedKeys = array("tmp_dir", "bin_dir", "script_dir", "app_dir", "data_dir", "condor_bin_dir");
    $replacements = array();
    foreach($value as $key=>$value)
      {
      if(!isset($key))
        {
        $replacements[$key] = "";   
        }
      } 
    foreach($replacements as $replacement=>$val)
      {
      $value[$replacement] = $val;
      }
    
    
    $this->_checkKeys(array('item_id', 'metric_name'), $value);  
      
    $componentLoader = new MIDAS_ComponentLoader();
    $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
    return array($kwbatchmakeComponent->testconfig($value));
    }
    
        
    
 
} // end class




