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

/** Web API Authentication Component */
class Remoteprocessing_JobComponent extends AppComponent
{

  /** Constructor */
  function __construct()
    {
    }

  /** Init Remote Params */
  public function initJobParameters($resultCallback, $inputArray = array(), $ouputArray = array(), $additionalParams = array())
    {
    $return = array();
    $notifications = Zend_Registry::get('notifier')->getNotifications();
    if(!isset($notifications[$resultCallback]))
      {
      throw new Zend_Exception("Unable to find callback ".$resultCallback);
      }
    if(!is_array($inputArray) || !is_array($ouputArray) || !is_array($additionalParams))
      {
      throw new Zend_Exception("Error params");
      }

    $return = $additionalParams;
    $return['resultCallback'] = $resultCallback;
    $return['input'] = array();
    $return['output'] = array();

    foreach($inputArray as $input)
      {
      $return['input'][] = $input;
      }

    foreach($ouputArray as $output)
      {
      $return['output'][] = $output;
      }

    return $return;
    }

  /** parse parameters and create job tree*/
  public function processJobParameters($params)
    {
    // convert array keys to uuid
    $newParams = array();
    foreach($params['params'] as $key => $jobParam)
      {
      $params['params'][$key]['uuid'] = uniqid() . md5(mt_rand());
      }

    foreach($params['params'] as $key => $jobParam)
      {
      $tmpUuid = $jobParam['uuid'];
      $newParams[$tmpUuid] = $jobParam;
      $newParams[$tmpUuid]['parents'] = array();
      if(isset($jobParam['parents']))
        {
        foreach($jobParam['parents'] as $parent)
          {
          $newParams[$tmpUuid]['parents'][] = $params['params'][$parent]['uuid'];
          }
        }
      }

    // create tree
    $tree = $this->_createJobTree($newParams);

    // spicific tree modification
    $componentLoader = new MIDAS_ComponentLoader();
    $executableComponent = $componentLoader->loadComponent('Executable', 'remoteprocessing');
    $internalComponent = $componentLoader->loadComponent('Internal', 'remoteprocessing');
    $userComponent = $componentLoader->loadComponent('User', 'remoteprocessing');

    list($newParams, $tree) = $executableComponent->treeProcessing($newParams, $tree);
    list($newParams, $tree) = $internalComponent->treeProcessing($newParams, $tree);
    list($newParams, $tree) = $userComponent->treeProcessing($newParams, $tree);
    // create and save job workflow
    $this->_createWorkflow($newParams, $tree);
    }

  /** create a tree of the job's workflow */
  private function _createJobTree($params, $parent = null)
    {
    $tree = array();
    foreach($params as $key => $jobParam)
      {
      if((empty($jobParam['parents']) && $parent == null) || (!empty($jobParam['parents']) && in_array($parent, $jobParam['parents'])))
        {
        $tree[$jobParam['uuid']] = $this->_createJobTree($params, $key);
        }
      }
    return $tree;
    }

  /** create and save workflow */
  private function _createWorkflow($params, $tree, $parent = null)
    {
    foreach($tree as $uuid => $children)
      {
      $jobParam = $params[$uuid];
      $parents = array();
      if($parent != null)
        {
        $parents[] = $parent;
        }

      $script = (isset($jobParam['script'])) ? $jobParam['script'] : "";
      $os = (isset($jobParam['os'])) ? $jobParam['os'] : "linux";
      $type = (isset($jobParam['type'])) ? $jobParam['type'] : MIDAS_REMOTEPROCESSING_TYPE_REMOTE_PYTHON;
      $condition = (isset($jobParam['condition'])) ? $jobParam['condition'] : "";
      $expiration = (isset($jobParam['expiration'])) ? $jobParam['expiration'] : null;
      $uuid = (isset($jobParam['uuid'])) ? $jobParam['uuid'] : null;

      $job = $this->createJob($jobParam, $parents, $script, $type, $os, $condition, $expiration, $uuid);
      var_dump($job);
      $this->_createWorkflow($params, $children, $job);
      }
    }

  /** create a job */
  public function createJob($params = array(), $parents = array(), $script = '', $type = MIDAS_REMOTEPROCESSING_TYPE_REMOTE_PYTHON, $os = "linux", $condition = "", $expiration = null, $uuid = null )
    {
    if($uuid == null)
      {
      $uuid = uniqid() . md5(mt_rand());
      $params['uuid'] = $uuid;
      }
    $modelLoader = new MIDAS_ModelLoader;
    $jobModel = $modelLoader->loadModel("Job", "remoteprocessing");
    $job = $jobModel->getByUuid($uuid);
    if($job == null)
      {
      $itemModel = $modelLoader->loadModel("Item");
      require_once BASE_PATH.'/modules/remoteprocessing/models/dao/JobDao.php';
      $job = new Remoteprocessing_JobDao();
      $job->setScript($script);
      if($uuid != null)
        {
        $job->setUuid($uuid);
        }
      else
        {
        $job->setUuid(uniqid() . md5(mt_rand()));
        }
      unset($params['script']);
      $job->setOs($os);
      unset($params['os']);
      $job->setType($type);
      unset($params['type']);
      $job->setCondition($condition);
      unset($params['condition']);
      if(isset($expiration) && $expiration != null)
        {
        $job->setExpirationDate($expiration);
        }
      else
        {
        $date = new Zend_Date();
        $date->add('5', Zend_Date::HOUR);
        $job->setExpirationDate($date->toString('c'));
        }

      if(isset($params['creator_id']))
        {
        $job->setCreatorId($params['creator_id']);
        }
      if(isset($params['job_name']))
        {
        $job->setName($params['job_name']);
        }

      $job->setParams(JsonComponent::encode($params));
      $jobModel->save($job);

      if(!empty($params['input']))
        {
        foreach($params['input'] as $itemId)
          {
          if($itemId instanceof ItemDao)
            {
            $item = $itemId;
            }
          elseif(is_numeric($itemId))
            {
            $item = $itemModel->load($itemId);
            }
          else
            {
            continue;
            }

          if($item != false && $item->getKey() != $params['executable'])
            {
            $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT);
            }
          elseif($item != false)
            {
            $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_EXECUTABLE);
            }
          }
        }
      }

    foreach($parents as $parent)
      {
      if(!$parent instanceof Remoteprocessing_JobDao)
        {
        throw new Zend_Exception("Should be a job.");
        }
      $jobModel->addParent($job, $parent);
      }
    return $job;
    }

  /** init Job*/
  public function scheduleJob($parameters, $script, $os = MIDAS_REMOTEPROCESSING_OS_WINDOWS, $fire_time = false, $time_interval = false, $only_once = true, $condition = '')
    {
    $scriptParams['script'] = $script;
    $scriptParams['os'] = $os;
    $scriptParams['condition'] = $condition;
    $scriptParams['params'] = $parameters;

    $scheduleParams['task'] = 'TASK_REMOTEPROCESSING_ADD_JOB';
    $scheduleParams['params'] = $scriptParams;
    if(is_string($fire_time))
      {
      $scheduleParams['fire_time'] = strtotime($fire_time);
      }

    if(!$only_once && $time_interval !== false)
      {
      $scheduleParams['run_only_once'] = false;
      $scheduleParams['time_interval'] = $time_interval;
      }

    if(isset($scheduleParams['fire_time']))
      {
      Zend_Registry::get('notifier')->callback("CALLBACK_SCHEDULER_SCHEDULE_TASK", $scheduleParams);
      }
    else
      {
      Zend_Registry::get('notifier')->callback("CALLBACK_REMOTEPROCESSING_ADD_JOB", $scriptParams);
      }
    }

  /** compute logs (return an xml file) */
  public function computeLogs($job, $logs, $params)
    {
    unset($params['log']);
    $componentLoader = new MIDAS_ComponentLoader();
    $utilityComponent = $componentLoader->loadComponent('Utility');
    $logs = str_replace("\r\n", "", $logs);
    $logs = str_replace("\r\r", "\r", $logs);
    $xml = "<?xml version='1.0'?>\n";
    $xml .= "<Job id='".$job->getKey()."' name='".$job->getName()."'>\n";
    $xml .= "<JobParameters>\n";
    $xml .= "<![CDATA[".JsonComponent::encode($params)."]]>";
    $xml .= "</JobParameters>\n";
    $logs = explode("-COMMAND\r", $logs);
    if(count($logs) < 2)
      {
      return "";
      }
    unset($logs[0]);
    foreach($logs as $log)
      {
      $failed = false;
      $tmp = explode("-EXECUTION TIME\r", $log);
      $command = $tmp[0];
      $tmp = explode("-STDOUT\r", $tmp[1]);
      $executionTime = $tmp[0];
      $tmp = explode("-STDERR\r", $tmp[1]);
      $stdout = $tmp[0];
      $stderr = $tmp[1];

      $search = array(' ', "\t", "\n", "\r");
      $cleanedStderr = str_replace($search, '', $stderr);
      if(!empty($cleanedStderr))
        {
        $failed = true;
        }
      $lowerout = strtolower($stdout);
      if(strpos($lowerout, "error") !== false)
        {
        $failed = true;
        }

      $xml .= "<Process status=";
      if($failed)
        {
        $xml .= "'failed'";
        }
      else
        {
        $xml .= "'passed'";
        }
      $xml .= ">\n";
      $xml .= "<Command>\n";
      $xml .= "<![CDATA[".$command."]]>";
      $xml .= "</Command>\n";
      $xml .= "<ExecutionTime>\n";
      $xml .= "<![CDATA[".$executionTime."]]>";
      $xml .= "</ExecutionTime>\n";
      $xml .= "<Output>\n";
      $xml .= "<![CDATA[".$stdout."]]>";
      $xml .= "</Output>\n";
      $xml .= "<Error>\n";
      $xml .= "<![CDATA[".$stderr."]]>";
      $xml .= "</Error>\n";
      $xml .= "</Process>";
      }
    $xml .= "</Job>\n";
    return $xml;
    }

  /** convertXmlREsults */
  public function convertXmlREsults($xml)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadmodel('Job', 'remoteprocessing');
    $itemModel = $modelLoader->loadmodel('Item');

    $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if(!$xml)
      {
      return;
      }
    $return = array();
    $return['job'] = $jobModel->load((int) $xml->attributes()->id[0]);
    $return['params'] = JsonComponent::decode((string) $xml->JobParameters);
    $return['process'] = array();
    $i = 1;
    foreach($xml->Process as $process)
      {
      $tmp = array();
      $tmp['status'] = (string) $process->attributes()->status[0];
      $tmp['command'] = trim((string) $process->Command);
      $tmp['stderr'] = trim((string) $process->Error);
      $tmp['stdout'] = trim((string) $process->Output);
      $tmp['xmlStdout'] = simplexml_load_string($tmp['stdout'], 'SimpleXMLElement', LIBXML_NOCDATA);
      $tmp['time'] = (float) trim(str_replace("s", "", (string) $process->ExecutionTime)); //convert in milliseconds
      $tmp['output'] = array();
      $tmp['parameters'] = array();
      foreach($return['params']['parametersList'] as $key => $parameter)
        {
        $tmp['parameters'][$key] = trim($return['params']['optionMatrix'][$i][$key]);
        }
      if(isset($return['params']['outputKeys'][$i]))
        {
        foreach($return['params']['outputKeys'][$i] as $itemId)
          {
          $tmp['output'][] = $itemModel->load($itemId);
          }
        }
      $return['process'][] = $tmp;
      $i++;
      }
    return $return;
    }

}
