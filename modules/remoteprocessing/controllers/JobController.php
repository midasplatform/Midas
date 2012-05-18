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
/** job controller*/
class Remoteprocessing_JobController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder');
  public $_components = array('Upload');
  public $_moduleComponents = array('Executable', 'Job');
  public $_moduleModels = array('Job', 'Workflow', 'Workflowdomain', 'WorkflowdomainPolicyuser');

  /** manage jobs */
  function manageAction()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->view->header = $this->t("Manage Your Workflows");

    $modelLoad = new MIDAS_ModelLoader();
    $schedulerJobModel = $modelLoad->loadModel('Job', 'scheduler');
    $this->view->scheduledJobs = $schedulerJobModel->getJobsByTaskAndCreator('TASK_REMOTEPROCESSING_ADD_JOB', $this->userSession->Dao);
    $this->view->relatedJobs = $this->Remoteprocessing_Job->getByUser($this->userSession->Dao, 10);
    $this->view->domains = $this->Remoteprocessing_Workflowdomain->getUserDomains($this->userSession->Dao);
    }

  /** init a job*/
  function initAction()
    {
    $this->view->header = $this->t("Workflow Wizard");
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $scheduled = $this->_getParam("scheduled");
    if(isset($scheduled))
      {
      $scheduled = true;
      }
    else
      {
      $scheduled = false;
      }

    $itemId = $this->_getParam("itemId");
    if(isset($itemId))
      {
      $itemDao = $this->Item->load($itemId);
      if($itemDao === false)
        {
        throw new Zend_Exception("This item doesn't exist.");
        }
      $this->view->itemDao = $itemDao;
      }

    $this->view->domains = $this->Remoteprocessing_Workflowdomain->getUserDomains($this->userSession->Dao, MIDAS_POLICY_WRITE);

    $this->view->json['job']['scheduled'] = $scheduled;
    $this->view->scheduled = $scheduled;
    if($this->_request->isPost())
      {
      $jobResults = $this->_getParam("results");
      $workflowDomainId = $this->_getParam("workflowName");
      if(isset($workflowDomainId))
        {
        $workflowDomain = $this->Remoteprocessing_Workflowdomain->load($this->_getParam("workflowDomain"));
        }

      if(!isset($workflowDomain) || $workflowDomain == false)
        {
        require_once BASE_PATH.'/modules/remoteprocessing/models/dao/WorkflowdomainDao.php';
        $workflowDomain = new Remoteprocessing_WorkflowdomainDao();
        $workflowDomain->setName($this->_getParam("workflowDomainName"));
        $workflowDomain->setDescription($this->_getParam("workflowDomainDescription"));
        $this->Remoteprocessing_Workflowdomain->save($workflowDomain);
        $this->Remoteprocessing_WorkflowdomainPolicyuser->createPolicy($this->userSession->Dao, $workflowDomain, MIDAS_POLICY_ADMIN);
        }

      require_once BASE_PATH.'/modules/remoteprocessing/models/dao/WorkflowDao.php';
      $workflow = new Remoteprocessing_WorkflowDao();
      $workflow->setName($this->_getParam("workflowName"));
      $workflow->setDescription($this->_getParam("workflowDescription"));
      $workflow->setUuid($this->_getParam("workflowUuid"));
      $workflow->setWorkflowdomainId($workflowDomain->getKey());
      $this->Remoteprocessing_Workflow->save($workflow);
      return;
      /** Work in progress
      $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
      $metaContent = new SimpleXMLElement(file_get_contents($metaFile->getFullPath()));
      $this->disableLayout();
      $this->disableView();

      $jobCommands = array();

      foreach($jobResults as $jobResult)
        {
        $jobCommand = array();
        $itemDao = $this->Item->load($jobResult['executable']);
        if($itemDao === false)
          {
          throw new Zend_Exception("This item doesn't exist.");
          }
        if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
          {
          throw new Zend_Exception("Problem policies.");
          }

        $jobCommand['parents'] = array();
        $jobCommand['executable'] = $itemDao->getKey();
        $jobCommand['job_name'] = $jobResult['name'];
        $jobCommand['creator_id'] = $this->userSession->Dao->getKey();
        $jobCommand['type'] = MIDAS_REMOTEPROCESSING_TYPE_EXECUTABLE;
        $ext = end(explode('.', $itemDao->getName()));
        if($ext == 'exe')
          {
          $jobCommand['os'] = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
          }
        else
          {
          $jobCommand['os'] = MIDAS_REMOTEPROCESSING_OS_LINUX;
          }

        // transform post results to a command array using the executable definition file
        $cmdOptions = array();
        $parametersList = array();
        $i = 0;
        foreach($metaContent->option as $option)
          {
          if(!isset($jobResult['options'][$i]))
            {
            continue;
            }

          $result = $jobResult['options'][$i];
          if($option->channel == 'ouput')
            {
            $resultArray = explode(";;", $result);
            $folder = $this->Folder->load($resultArray[0]);
            if($folder == false || !$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE))
              {
              throw new Zend_Exception('Unable to find folder or permission error');
              }
            $cmdOptions[$i] = array('type' => 'output', 'folderId' => $resultArray[0], 'fileName' => $resultArray[1]);
            }
          else if($option->field->external == 1)
            {
            $parametersList[$i] = (string)$option->name;
            if(strpos($result, 'folder') !== false)
              {
              $folder = $this->Folder->load(str_replace('folder', '', $result));
              if($folder == false || !$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ))
                {
                throw new Zend_Exception('Unable to find folder or permission error');
                }
              $items = $folder->getItems();
              $cmdOptions[$i] = array('type' => 'input', 'item' => array(), 'folder' => $folder->getKey());
              }
            else if(is_numeric($result))
              {
              $item = $this->Item->load($result);
              if($item == false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
                {
                throw new Zend_Exception('Unable to find item');
                }
              $cmdOptions[$i] = array('type' => 'input', 'item' => array($item));
              }
            else
              {
              $result = str_replace("jobOuput;;", "", $result);
              $resultArray = explode("-", $result);
              $cmdOptions[$i] = array('type' => 'input', 'item' => array(), 'job' => $resultArray[0], 'parameter' => $resultArray[1]);
              }
            }
          else
            {
            $parametersList[$i] = (string)$option->name;
            $cmdOptions[$i] = array('type' => 'param', 'values' => array());
            if(strpos($result, ';') !== false)
              {
              $cmdOptions[$i]['values'] = explode(';', $result);
              }
            elseif(strpos($result, '-') !== false && strpos($result, '-') !== 0)
              {
              $tmpArray = explode('(', $result);
              if(count($tmpArray) == 1)
                {
                $step = 1;
                }
              else
                {
                $step = substr($tmpArray[1], 0, strlen($tmpArray[1])-1);
                }

              $tmpArray = explode('-', $tmpArray[0]);
              $start = $tmpArray[0];
              $end = $tmpArray[1];
              for($j = $start;$j <= $end;$j = $j + $step)
                {
                $cmdOptions[$i]['values'][] = $j;
                }
              }
            else
              {
              $cmdOptions[$i]['values'][] = $result;
              }

            if(!empty($option->tag))
              {
              $cmdOptions[$i]['tag'] = (string)$option->tag;
              }
            }
          $i++;
          }
        $jobCommand['parametersList'] = $parametersList;
        $jobCommand['cmdOptions'] = $cmdOptions;
        $jobCommands[] = $jobCommand;
        }

      // init parents
      foreach($jobCommands as $key => $jobCommand)
        {
        foreach($jobCommand['cmdOptions'] as $option)
          {
          if(isset($option['job']))
            {
            foreach($jobCommands as $key2 => $jc)
              {
              if($jc['job_name'] == $option['job'])
                {
                $jobCommands[$key]['parents'][] = $key2;
                }
              }
            }
          }
        }

      $fire_time = false;
      $time_interval = false;
      if(isset($_POST['date']))
        {
        $fire_time = $_POST['date'];
        if($_POST['interval'] != 0)
          {
          $time_interval = $_POST['interval'];
          }
        }
      if($time_interval === false)
        {
        $only_once = true;
        }
      else
        {
        $only_once = false;
        }
      $this->ModuleComponent->Job->scheduleJob($jobCommands, '', MIDAS_REMOTEPROCESSING_OS_LINUX, $fire_time, $time_interval, $only_once);     */
      }
    }

  /** return the executable form (should be an ajax call) */
  /*
  function getinitexecutableAction()
    {
    $this->disableLayout();
    $itemId = $this->_getParam("itemId");
    $scheduled = $this->_getParam("scheduled");
    if(isset($scheduled) && $scheduled == 1)
      {
      $scheduled = true;
      }
    else
      {
      $scheduled = false;
      }

    $this->view->scheduled = $scheduled;
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }

    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }

    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    if($metaFile == false)
      {
      throw new Zend_Exception("Unable to find meta information");
      }

    $metaContent = new SimpleXMLElement(file_get_contents($metaFile->getFullPath()));
    $this->view->metaContent = $metaContent;

    $this->view->itemDao = $itemDao;
    $this->view->json['item'] = $itemDao->toArray();
    }
  */

  /** view a job */
  function viewAction()
    {
    $this->view->header = $this->t("Job");
    $jobId = $this->_getParam("jobId");
    $jobDao = $this->Remoteprocessing_Job->load($jobId);
    if(!$jobDao)
      {
      throw new Zend_Exception("Unable to find job.");
      }

    if(!$this->Remoteprocessing_Job->policyCheck($jobDao, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("Permissions error.");
      }

    $this->view->isJobAdmin = $this->logged && ($this->Remoteprocessing_Job->policyCheck($jobDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN) || $jobDao->getCreatorId() == $this->userSession->Dao->getKey());

    if(isset($_GET['delete']) && $this->view->isJobAdmin)
      {
      $this->Remoteprocessing_Job->delete($jobDao);
      $this->_redirect('/');
      }

    $this->view->job = $jobDao;
    $workflow = $jobDao->getWorkflows();
    $this->view->workflow = $workflow[0];
    $this->view->domain = $workflow[0]->getDomain();
    $this->view->header = $this->t("Job: ".$jobDao->getName());
    $items = $jobDao->getItems();
    $inputs = array();
    $outputs = array();
    $parametersList = array();
    $executable = false;
    $log = false;

    foreach($items as $key => $item)
      {
      if(!$this->Item->policyCheck($item, $this->userSession->Dao))
        {
        unset($items[$key]);
        continue;
        }
      if($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_EXECUTABLE)
        {
        $executable = $item;
        }
      elseif($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT)
        {
        $inputs[$item->getName()] = $item;
        }
      elseif($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_OUPUT)
        {
        $reviesion = $this->Item->getLastRevision($item);
        $metadata = $this->ItemRevision->getMetadata($reviesion);
        $item->metadata = $metadata;

        foreach($metadata as $m)
          {
          if($m->getElement() == 'parameter')
            {
            $parametersList[$m->getQualifier()] = $m->getQualifier();
            $item->metadataValues[$m->getQualifier()] = $m->getValue();
            }
          elseif($m->getElement() == 'fileInput')
            {
            $parametersList[$m->getQualifier()] = $m->getQualifier();
            $item->metadataValues[$m->getQualifier()] = $this->Item->getByUuid($m->getValue());
            }
          }

        $outputs[] = $item;
        }
      elseif($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS)
        {
        $reviesion = $this->Item->getLastRevision($item);
        $metadata = $this->ItemRevision->getMetadata($reviesion);
        $item->metadata = $metadata;

        $bitstreams = $reviesion->getBitstreams();
        if(count($bitstreams) == 1)
          {
          $log = file_get_contents($bitstreams[0]->getFullPath());
          }
        }
      }
    $this->view->outputs = $outputs;
    $this->view->log = $log;
    $this->view->results =  $this->ModuleComponent->Job->convertXmlREsults($log);

    $this->view->inputs = $inputs;
    if(empty($this->view->results))
      {
      $this->view->inputs = array();
      $this->view->metrics = array();
      $this->view->inputsValues = array();
      $this->view->metricsValues = array();
      }
    else
      {
      list($this->view->inputs, $this->view->inputsValues) = $this->ModuleComponent->Job->getInputParams($this->view->results);
      list($this->view->metrics, $this->view->metricsValues) = $this->ModuleComponent->Job->getOutputParams($this->view->results);
      }
    $this->view->executable = $executable;
    $this->view->parameters = $parametersList;
    }

  /** Valid  entries (ajax) */
  public function validentryAction()
    {
    if(!$this->isTestingEnv())
      {
      $this->requireAjaxRequest();
      }

    $this->disableLayout();
    $this->disableView();
    $entry = $this->_getParam("entry");
    $type = $this->_getParam("type");
    if(!is_string($entry) || !is_string($type))
      {
      echo 'false';
      return;
      }
    switch($type)
      {
      case 'isexecutable' :
        $itemDao = $this->Item->load($entry);
        if($itemDao !== false && $this->ModuleComponent->Executable->getExecutable($itemDao) !== false)
          {
          echo "true";
          }
        else
          {
          echo "false";
          }
        return;
      case 'ismeta' :
        $itemDao = $this->Item->load($entry);
        if($itemDao !== false && $this->ModuleComponent->Executable->getMetaIoFile($itemDao) !== false)
          {
          echo "true";
          }
        else
          {
          echo "false";
          }
        return;
      default :
        echo "false";
        return;
      }
    } //end valid entry

  /** Get  entries (ajax)*/
  public function getentryAction()
    {
    $this->disableLayout();
    $this->disableView();
    $entry = $this->_getParam("entry");
    $type = $this->_getParam("type");
    if(!is_string($type))
      {
      echo JsonComponent::encode(false);
      return;
      }
    switch($type)
      {
      case 'getRecentExecutable' :
        $recent = array();
        foreach($this->userSession->uploaded as $item)
          {
          $item = $this->Item->load($item);

          if($item != false && $this->ModuleComponent->Executable->getExecutable($item) !== false)
            {
            $recent[] = $item->toArray();
            }
          }
        echo JsonComponent::encode($recent);
        return;
      default :
        echo JsonComponent::encode(false);
        return;
      }
    } //end valid entry

}//end class
