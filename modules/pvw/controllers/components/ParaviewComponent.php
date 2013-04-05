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

/** 
 * This component is used to create and manages
 * paraview (pvpython) instances.
 */
class Pvw_ParaviewComponent extends AppComponent
{
  /**
   * Creates a new pvpython instance and a corresponding database record for it.
   * @param item The item dao to visualize
   * @return The pvw_instance dao
   */
  public function createAndStartInstance($item, $appname)
    {
    $pvpython = $this->Setting->getValueByName('pvpython', $this->moduleName);
    $staticContent = $this->Setting->getValueByName('staticcontent', $this->moduleName);
    $application = BASE_PATH.'/modules/pvw/apps/'.$appname.'.py';
    if(!is_file($application))
      {
      throw new Zend_Exception('No such application: '.$appname, 400);
      }

    $dataPath = ''; //TODO symlink the item data somewhere.
    $port = 9021; // TODO dynamically select port from a resource pool
    $cmdArray = array($pvpython, $application, '--port', $port, '--data', $dataPath);

    // Set static content root if necessary
    if($staticContent && is_dir($staticContent))
      {
      $cmdArray[] = '--content'; // If we want pvw to serve its own static content, pass this arg
      $cmdArray[] = $staticContent;
      }

    // Now start the instance
    $cmd = join(' ', $cmdArray);
    exec(sprintf("%s > %s 2>&1 & echo $!", $cmd, '/dev/null'), $output);
    $pid = trim(join('', $output));
    if(!is_numeric($pid))
      {
      throw new Zend_Exception('Expected pid output, got: '.$pid, 500);
      }

    $instance = MidasLoader::newDao('InstanceDao', 'pvw');
    $instance->setItemId($item->getKey());
    $instance->setPid($pid);
    $instance->setPort($port);
    $instance->setSid(''); // todo?
    $instance->setCreationDate(date('c'));

    $instanceModel = MidasLoader::loadModel('Instance', 'pvw');
    $instanceModel->save($instance);
    return $instance;
    }

  /**
   * Return whether or not the given instance is still running
   * @param instance The pvw_instance dao
   */
  public function isRunning($instance)
    {
    exec('ps '.$instance->getPid(), $output);
    return count($output) >= 2;
    }
} // end class
