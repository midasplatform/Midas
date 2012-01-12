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
      if(!$input instanceof ItemDao)
        {
        throw new Zend_Exception("Error params. Shoud be an itemdao");
        }
      $return['input'][] = $input->getKey();
      }

    foreach($ouputArray as $output)
      {
      if(!is_string($output))
        {
        throw new Zend_Exception("Error params. Shoud be a string");
        }
      $return['output'][] = $output;
      }

    return $return;
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
