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

// Web API error codes
define('MIDAS_BATCHMAKE_INVALID_POLICY', -151);
define('MIDAS_BATCHMAKE_INVALID_PARAMETER', -150);

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
  /** Return the user dao */
  private function _getUser($args)
    {
    $authComponent = MidasLoader::loadComponent('Authentication', 'api');
    return $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
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

    $kwbatchmakeComponent = MidasLoader::loadComponent('KWBatchmake', 'batchmake');
    return $kwbatchmakeComponent->testconfig($configParams);
    }



  /**
   * Add a condorDag entry to the specified batchmake task
   * @param token Authentication token
   * @param batchmaketaskid The id of the batchmake task for this dag
   * @param dagfilename The filename of the dagfile
   * @param outfilename The filename of the dag processing output
   * @return The created CondorDagDao.
   */
  public function addCondorDag($params)
    {
    $keys = array("batchmaketaskid" => "batchmaketaskid", "dagfilename" => "dagfilename", "outfilename" => "outfilename");
    $this->_checkKeys($keys, $params);

    $userDao = $this->_getUser($params);
    if(!$userDao)
      {
      throw new Exception('Anonymous users may not add condor dags', MIDAS_BATCHMAKE_INVALID_POLICY);
      }

    $taskModel = MidasLoader::loadModel('Task', 'batchmake');
    $condorDagModel = MidasLoader::loadModel('CondorDag', 'batchmake');

    $batchmakeTaskId = $params["batchmaketaskid"];
    $dagFilename = $params["dagfilename"];
    $outFilename = $params["outfilename"];

    $taskDao = $taskModel->load($batchmakeTaskId);
    if(empty($taskDao))
      {
      throw new Exception('Invalid batchmaketaskid specified', MIDAS_BATCHMAKE_INVALID_PARAMETER);
      }
    if($taskDao->getUserId() !== $userDao->getUserId())
      {
      throw new Exception('You are not the owner of this batchmake task', MIDAS_BATCHMAKE_INVALID_POLICY);
      }

    $data = array("batchmake_task_id" => $batchmakeTaskId, "dag_filename" => $dagFilename, "out_filename" => $outFilename);

    $condorDagDao = $condorDagModel->initDao("CondorDag", $data, 'batchmake');
    $condorDagModel->save($condorDagDao);
    return $condorDagDao;
    }


  /**
   * Add a condorJob entry to the specified batchmake task
   * @param token Authentication token
   * @param batchmaketaskid The id of the batchmake task for this dag
   * @param outputfilename The filename of the output file for the job
   * @param errorfilename The filename of the error file for the job
   * @param logfilename The filename of the log file for the job
   * @param postfilename The filename of the post script log file for the job
   * @return The created CondorJobDao.
   */
  public function addCondorJob($params)
    {
    $keys = array("batchmaketaskid" => "batchmaketaskid",
      "jobdefinitionfilename" => "jobdefinitionfilename",
      "outputfilename" => "outputfilename",
      "errorfilename" => "errorfilename",
      "logfilename" => "logfilename",
      "postfilename" => "postfilename");
    $this->_checkKeys($keys, $params);

    $userDao = $this->_getUser($params);
    if(!$userDao)
      {
      throw new Exception('Anonymous users may not add condor jobs', MIDAS_BATCHMAKE_INVALID_POLICY);
      }

    $taskModel = MidasLoader::loadModel('Task', 'batchmake');
    $condorDagModel = MidasLoader::loadModel('CondorDag', 'batchmake');
    $condorJobModel = MidasLoader::loadModel('CondorJob', 'batchmake');

    $batchmakeTaskId = $params["batchmaketaskid"];
    $jobdefinitionFilename = $params["jobdefinitionfilename"];
    $outputFilename = $params["outputfilename"];
    $errorFilename = $params["errorfilename"];
    $logFilename = $params["logfilename"];
    $postFilename = $params["postfilename"];

    $taskDao = $taskModel->load($batchmakeTaskId);
    if(empty($taskDao))
      {
      throw new Exception('Invalid batchmaketaskid specified', MIDAS_BATCHMAKE_INVALID_PARAMETER);
      }
    if($taskDao->getUserId() !== $userDao->getUserId())
      {
      throw new Exception('You are not the owner of this batchmake task', MIDAS_BATCHMAKE_INVALID_POLICY);
      }

    // get the dag via the batchmaketask
    $condorDags = $condorDagModel->findBy("batchmake_task_id", $batchmakeTaskId);
    if(empty($condorDags) || sizeof($condorDags) === 0)
      {
      throw new Exception('There is no condor dag for this batchmaketaskid', MIDAS_BATCHMAKE_INVALID_PARAMETER);
      }
    // take the first if there are multiple
    $condorDagDao = $condorDags[0];
    $condorDagId = $condorDagDao->getCondorDagId();

    $data = array("condor_dag_id" => $condorDagId,
      "jobdefinition_filename" => $jobdefinitionFilename,
      "output_filename" => $outputFilename,
      "error_filename" => $errorFilename,
      "log_filename" => $logFilename,
      "post_filename" => $postFilename);

    $condorJobDao = $condorJobModel->initDao("CondorJob", $data, 'batchmake');
    $condorJobModel->save($condorJobDao);
    return $condorJobDao;
    }



} // end class




