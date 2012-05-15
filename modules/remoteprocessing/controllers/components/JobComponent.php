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

  /** createFormatedXml*/
  public function createFormatedXmlFromArray($array)
    {
    $string = "<?xml version='1.0'?>\n";
    $string .= "<Job uuid='".$array['jobUuid']."' creator_id='".$array['creatorId']."' workflow_uuid='".$array['workflowUuid']."' >\n";
    $string .= "<Name>".$array['name']."</Name>\n";
    $string .= "<StartDate>".$array['job']->getStartDate()."</StartDate>\n";
    $string .= "<Parents>\n";
    foreach($array['parents'] as $parent)
      {
      $string .= "<Parents>".$parent['uuid']."</Parents>\n";
      }
    $string .= "</Parents>\n";
    $string .= "<JobParameters>".JsonComponent::encode($array['params'])."</JobParameters>\n";
    $string .= "<MidasOutputFolder>".$array['outputFolder']['uuid']."</MidasOutputFolder>\n";
    $string .= "<Tasks>\n";
    foreach($array['tasks'] as $task)
      {
      if($task['status'] == 'passed')
        {
        $string .= "<Task status = 'passed'>\n";
        }
      else
        {
        $string .= "<Task status = 'failed'>\n";
        }
      $string .= "<Command><![CDATA[".$task['command']."]]></Command>\n";
      $string .= "<ExecutionTime><![CDATA[".$task['time']."]]></ExecutionTime>\n";
      $string .= "<RawOutput><![CDATA[".$task['stdout']."]]></RawOutput>\n";
      $string .= "<Error><![CDATA[".$task['stderr']."]]></Error>\n";
      $string .= "<TaskParameters>".JsonComponent::encode($task['parameters'])."</TaskParameters>\n";
      $string .= "<Outputs>\n";
      foreach($task['outputFiles'] as $output)
        {
        if(!empty($output['name']))
          {
          $string .= "<Output type='file' uuid='".$output['uuid']."' name='".$output['name']."'>".$output['fileName']."</Output>\n";
          }
        }
      foreach($task['outputParam'] as $output)
        {
        if(!empty($output['name']))
          {
          $string .= "<Output type='".$output['type']."'  name='".$output['name']."'>".$output['value']."</Output>\n";
          }
        }
      $string .= "</Outputs>\n";
      $string .= "<Inputs>\n";
      foreach($task['inputFiles'] as $output)
        {
        if(!empty($output['name']))
          {
          $string .= "<Input type='file' uuid='".$output['uuid']."' name='".$output['name']."'>".$output['fileName']."</Input>\n";
          }
        }
      foreach($task['inputParam'] as $output)
        {
        if(!empty($output['name']))
          {
          $string .= "<Input type='".$output['type']."'  name='".$output['name']."'>".$output['value']."</Input>\n";
          }
        }
      $string .= "</Inputs>\n";
      $string .= "</Task>\n";
      }
    $string .= "</Tasks>\n";
    $string .= "</Job>\n";
    return $string;
    }

  /** getOutputParamsFromDao */
  public function getOutputParamsFromDao($job, $includeItems = false)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadModel('Job', 'remoteprocessing');
    $itemModel = $modelLoader->loadModel('Item');
    $items = $jobModel->getRelatedItems($job, MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS);
    if(!empty($items))
      {
      $revision = $itemModel->getLastRevision($items[0]);
      $bitstreams = $revision->getBitstreams();
      if(count($bitstreams) == 1)
        {
        return  $this->getOutputParams($this->convertXmlResults(file_get_contents($bitstreams[0]->getFullPath()), $includeItems));
        }
      }
    return array(array(), array());
    }

  /** getInputParamsFromDao */
  public function getInputParamsFromDao($job, $includeItems = false)
    {
    if(!$job instanceof Remoteprocessing_JobDao)
      {
      throw new Zend_Exception("Should be a job.");
      }
    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadModel('Job', 'remoteprocessing');
    $itemModel = $modelLoader->loadModel('Item');
    $items = $jobModel->getRelatedItems($job, MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS);
    if(!empty($items))
      {
      $revision = $itemModel->getLastRevision($items[0]);
      $bitstreams = $revision->getBitstreams();
      if(count($bitstreams) == 1)
        {
        return  $this->getInputParams($this->convertXmlResults(file_get_contents($bitstreams[0]->getFullPath()), $includeItems));
        }
      }
    return array(array(), array());
    }

  /** getOutputParams from a formatted array */
  public function getOutputParams($array)
    {
    $metrics = array();
    $values = array();
    foreach($array['tasks'] as $keyTask => $task)
      {
      $name = ucfirst(strtolower("Execution-Time"));
      if(!isset($metrics[$name]))
        {
        $metrics[$name] = array(0, 0);
        }
      if(is_numeric($task['time']))
        {
        $metrics[$name][0]++;
        $metrics[$name][1] += $task['time'];
        }
      $values[$keyTask][$name] = $task['time'];
      foreach($task['outputParam'] as $output)
        {
        $name = ucfirst(strtolower($output['name']));
        if(!isset($metrics[$name]))
          {
          $metrics[$name] = array(0, 0);
          }
        if(is_numeric($output['value']))
          {
          $metrics[$name][0]++;
          $metrics[$name][1] += $output['value'];
          }
        $values[$keyTask][$name] = $output['value'];
        }

      if(!empty($task['outputFiles']))
        {
        foreach($task['outputFiles'] as $output)
          {
          $name = ucfirst(strtolower($output['name']));
          if(isset($output['item']))
            {
            $values[$keyTask][$name] = $output['item'];
            }
          else
            {
            $values[$keyTask][$name] = $output['uuid'];
            }
          }
        }
      }

    return array($metrics, $values);
    }

  /** getMetrics from a formatted array */
  public function getInputParams($array)
    {
    $metrics = array();
    $values = array();
    foreach($array['tasks'] as $keyTask => $task)
      {
      foreach($task['inputParam'] as $output)
        {
        $name = ucfirst(strtolower($output['name']));
        if(!isset($metrics[$name]))
          {
          $metrics[$name] = true;
          }
        $values[$keyTask][$name] = $output['value'];
        }
      }
    return array($metrics, $values);
    }

  /** convertXmlREsults */
  public function convertXmlResults($xml, $includeDaos = true)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $jobModel = $modelLoader->loadmodel('Job', 'remoteprocessing');
    $workflowModel = $modelLoader->loadmodel('Workflow', 'remoteprocessing');
    $itemModel = $modelLoader->loadmodel('Item');
    $folderModel = $modelLoader->loadmodel('Folder');

    $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if(!$xml)
      {
      return;
      }

    try
      {
      $return = array();
      if(isset($xml->attributes()->uuid[0]))
        {
        $return['jobUuid'] = (string) $xml->attributes()->uuid[0];
        }
      else
        {
        $return['jobUuid'] = uniqid() . md5(mt_rand());
        }
      $return['job'] = $jobModel->getByUuid($return['jobUuid']);

      if(isset($xml->attributes()->creator_id[0]))
        {
        $return['creatorId'] = (int) $xml->attributes()->creator_id[0];
        }

      $return['params'] = array();
      if(isset($xml->JobParameters))
        {
        $return['params'] = JsonComponent::decode((string) $xml->JobParameters);
        }

      $return['name'] = $return['jobUuid'];
      if(isset($xml->Name))
        {
        $return['name'] = trim((string) $xml->Name);
        }

      $return['startDate'] = date('c');
      if(isset($xml->StartDate))
        {
        $return['startDate'] = trim((string) $xml->StartDate);
        if(is_numeric($return['startDate']))
          {
          $return['startDate'] = date('c', $return['startDate']);
          }
        elseif(strtotime($return['startDate']) !== false)
          {
          $return['startDate'] = date('c', strtotime($return['startDate']));
          }
        else
          {
          $return['startDate'] = date('c');
          }
        }

      $return['workflowUuid'] = (string) $xml->attributes()->workflow_uuid[0];
      if($includeDaos)
        {
        $return['workflow'] = $workflowModel->getByUuid($return['workflowUuid']);
        }
      $return['parents'] = array();
      if(isset($xml->Parents))
        {
        foreach($xml->Parents->Parent as $parent)
          {
          if($includeDaos)
            {
            $return['parents'][] = array('uuid' => (string) $parent, 'job' => $jobModel->getByUuid((string) $parent));
            }
          else
            {
            $return['parents'][] = array('uuid' => (string) $parent);
            }
          }
        }

      $return['outputFolder'] = array('uuid' => '', 'folder' => false);
      if(isset($xml->MidasOutputFolder))
        {
        if(is_numeric((string) $xml->MidasOutputFolder))
          {
          $tmpFolder = $folderModel->load((string) $xml->MidasOutputFolder);
          if($tmpFolder != false)
            {
            $return['outputFolder'] = array('uuid' => $tmpFolder->getUuid(), 'folder' => $tmpFolder);
            }
          }
        else
          {
          if($includeDaos)
            {
            $return['outputFolder'] = array('uuid' => (string) $xml->MidasOutputFolder, 'folder' => $folderModel->getByUuid((string) $xml->MidasOutputFolder));
            }
          else
            {
            $return['outputFolder'] = array('uuid' => (string) $xml->MidasOutputFolder);
            }
          }
        }
      $return['tasks'] = array();
      foreach($xml->Tasks->Task as $process)
        {
        $tmp = array();
        $tmp['status'] = (string) $process->attributes()->status[0];
        $tmp['command'] = trim((string) $process->Command);
        $tmp['stderr'] = trim((string) $process->Error);
        $tmp['stdout'] = trim((string) $process->RawOutput);
        $tmp['time'] = (float) trim(str_replace("s", "", (string) $process->ExecutionTime)); //convert in milliseconds
        $tmp['outputFiles'] = array();
        $tmp['outputParam'] = array();
        $tmp['inputFiles'] = array();
        $tmp['inputParam'] = array();
        $tmp['parameters'] = array();
        if(isset($process->TaskParameters))
          {
          $tmp['parameters'] = JsonComponent::decode((string) $process->TaskParameters);
          }

        if(!empty($tmp['parameters']))
          {
          foreach($return['params']['parametersList'] as $key => $parameter)
            {
            $tmp['parameters'][$key] = trim($return['params']['optionMatrix'][$i][$key]);
            }
          }
        if(isset($process->Outputs))
          {
          foreach($process->Outputs->Output as $output)
            {
            $type = (string) $output->attributes()->type[0];
            switch($type)
              {
              case 'file':
                $uuid = "";
                if(isset($output->attributes()->uuid[0]))
                  {
                  $uuid = (string) $output->attributes()->uuid[0];
                  }
                if($includeDaos)
                  {
                  $tmp['outputFiles'][] = array('uuid' => $uuid, 'name' => (string) $output->attributes()->name[0], 'fileName' => trim((string) $output), 'item' => $itemModel->getByUuid($uuid));
                  }
                else
                  {
                  $tmp['outputFiles'][] = array('uuid' => $uuid, 'name' => (string) $output->attributes()->name[0], 'fileName' => trim((string) $output));
                  }
                break;
              case 'float':
              case 'int':
              case 'double':
              case 'string':
                $tmp['outputParam'][] = array('type' => $type, 'name' => (string) $output->attributes()->name[0], 'value' => trim((string) $output));
                break;
              default:
                throw new Zend_Exception("Error Output type");
              }
            }
          }

        if(isset($process->Inputs))
          {
          foreach($process->Inputs->Input as $input)
            {
            $type = (string) $input->attributes()->type[0];
            switch($type)
              {
              case 'file':
                $uuid = "";
                if(isset($input->attributes()->uuid[0]))
                  {
                  $uuid = (string) $input->attributes()->uuid[0];
                  }
                if($includeDaos)
                  {
                  $tmp['inputFiles'][] = array('uuid' => $uuid, 'name' => (string) $input->attributes()->name[0], 'fileName' => trim((string) $input), 'item' => $itemModel->getByUuid($uuid));
                  }
                else
                  {
                  $tmp['inputFiles'][] = array('uuid' => $uuid, 'name' => (string) $input->attributes()->name[0], 'fileName' => trim((string) $input));
                  }
                break;
              case 'float':
              case 'int':
              case 'double':
              case 'string':
                $tmp['inputParam'][] = array('type' => $type, 'name' => (string) $input->attributes()->name[0], 'value' => trim((string) $input));
                break;
              default:
                throw new Zend_Exception("Error Input type");
              }
            }
          }
        $return['tasks'][] = $tmp;
        }
      }
    catch(Exception $e)
      {
      throw new Zend_Exception($e->getMessage());
      }
    return $return;
    }

}
