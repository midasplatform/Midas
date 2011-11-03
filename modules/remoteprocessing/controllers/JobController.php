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
/** job controller*/
class Remoteprocessing_JobController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder');
  public $_components = array('Upload');
  public $_moduleComponents = array('Executable');
  public $_moduleModels = array('Job');

  /** manage jobs */
  function manageAction()
    {
    $itemId = $this->_getParam("itemId");
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
    $this->view->header = $this->t("Manage Jobs: ".$itemDao->getName());
    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    $this->view->metaFile = $metaFile;
    $this->view->itemDao = $itemDao;

    $this->view->relatedJobs = $this->Remoteprocessing_Job->getRelatedJob($itemDao);

    if(isset($_GET['inprogress']))
      {
      $this->showNotificationMessage('The Job will appear in a next few minutes.');
      }
    }

  /** init a job */
  function initAction()
    {
    $this->view->header = $this->t("Job");
    $itemId = $this->_getParam("itemId");
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
      $this->_redirect('/remoteprocessing/executable/define?init=false&itemId='.$itemDao->getKey());
      return;
      }

    $metaContent = new SimpleXMLElement(file_get_contents($metaFile->getFullPath()));
    $this->view->metaContent = $metaContent;

    $this->view->itemDao = $itemDao;
    $this->view->json['item'] = $itemDao->toArray();
    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->disableView();

      // transform post results to a command array using the executable definition file
      $cmdOptions = array();
      $parametersList = array();
      $i = 0;
      foreach($metaContent->option as $option)
        {
        if(!isset($_POST['results'][$i]))
          {
          continue;
          }

        $result = $_POST['results'][$i];
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
          $parametersList[$i] = $option->name;
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
          else
            {
            $item = $this->Item->load($result);
            if($item == false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
              {
              throw new Zend_Exception('Unable to find item');
              }
            $cmdOptions[$i] = array('type' => 'input', 'item' => array($item));
            }
          }
        else
          {
          $parametersList[$i] = $option->name;
          $cmdOptions[$i] = array('type' => 'param', 'values' => array());
          if(strpos($result, ';') !== false)
            {
            $cmdOptions[$i]['values'] = explode(';', $result);
            }
          elseif(strpos($result, '-') !== false)
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
            $cmdOptions[$i]['tag'] = $option->tag;
            }
          }
        $i++;
        }


      $fire_time = false;
      $time_interval = false;
      $only_once = true;
      if(isset($_POST['date']))
        {
        $fire_time = $_POST['date'];
        if($_POST['interval'] != 0)
          {
          $only_once = false;
          $time_interval = $_POST['interval'];
          }
        }

      $this->ModuleComponent->Executable->initAndSchedule($itemDao, $cmdOptions, $parametersList, $fire_time, $time_interval, $only_once);
      }
    }

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

    $this->view->job = $jobDao;
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
          if($m->getElement() == 'parameter' && !in_array($m->getQualifier(), $parametersList))
            {
            $parametersList[$m->getQualifier()] = $m->getQualifier();
            }
          $item->metadataParameters[$m->getQualifier()] = $m->getValue();
          }

        if($item->getName() == 'log.txt')
          {
          $bitstreams = $reviesion->getBitstreams();
          if(count($bitstreams) == 1)
            {
            $log = file_get_contents($bitstreams[0]->getFullPath());
            }
          }
        else
          {
          $outputs[] = $item;
          }
        }
      }

    $this->view->outputs = $outputs;
    $this->view->log = $log;
    $this->view->inputs = $inputs;
    $this->view->executable = $executable;
    $this->view->parameters = $parametersList;
    }

}//end class
