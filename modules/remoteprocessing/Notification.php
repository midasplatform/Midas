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
/** notification manager*/
require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

class Remoteprocessing_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'remoteprocessing';
  public $_moduleComponents=array('Api');
  public $_moduleModels=array('Job');
  public $_models=array('Item');
  public $_moduleDaos=array('Job');

  /** init notification process*/
  public function init()
    {
    $this->enableWebAPI($this->moduleName);
    $this->addTask("TASK_REMOTEPROCESSING_ADD_JOB", 'addJob', "");
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_IS_EXECUTABLE', 'isExecutable');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getActionMenu');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_INFO', 'getItemInfo');
    $this->addCallBack('CALLBACK_CORE_LAYOUT_TOPBUTTONS', 'getButton');
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getFooter');
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_HEADER', 'getHeader');
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_PROCESS_RESULTS', 'processProcessingResults');
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_ADD_JOB', 'addJob');
    }//end init

  /** get layout header */
  public function getHeader()
    {
    return '<link type="text/css" rel="stylesheet" href="'.Zend_Registry::get('webroot').'/modules/remoteprocessing/public/css/layout/remoteprocessing.css" />';
    }
  /** get layout footer */
  public function getFooter()
    {
    return '<script type="text/javascript" src="'.Zend_Registry::get('webroot').'/modules/remoteprocessing/public/js/layout/remoteprocessing.js"></script>';
    }

  /** add a process button */
  public function getButton($params)
    {
    $modulesConfig=Zend_Registry::get('configsModules');
    if($modulesConfig[$this->moduleName]->showbutton)
      {
      $html =  "<li class='processButton' style='margin-left:5px;' title='Process' rel='".Zend_Registry::get('webroot')."/remoteprocessing/index/selectaction'>
                  <a href='#'><img id='processButtonImg' src='".Zend_Registry::get('webroot')."/modules/remoteprocessing/public/images/process-ok.png' alt='Start a process'/>
                  <img id='processButtonLoadiing' style='margin-top:5px;display:none;' src='".Zend_Registry::get('webroot')."/core/public/images/icons/loading.gif' alt=''/>
                    Process
                  </a>
                </li> ";
      return $html;
      }
    }

  /** check if item contains an executable */
  public function isExecutable($params)
    {
    $item = $params['item'];
    $executableComponent = MidasLoader::loadComponent("Executable", "remoteprocessing");
    return $executableComponent->getExecutable($item) !== false;
    }

  /** get action menu*/
  public function getActionMenu($params)
    {
    if($this->isExecutable($params) && $params['isModerator'] && $params['item'] instanceof ItemDao)
      {
      $html =  "<li>
            <a href='".Zend_Registry::get('webroot')."/remoteprocessing/executable/define/?itemId=".$params['item']->getKey()."'><img alt='' src='".Zend_Registry::get('coreWebroot')."/public/images/icons/xml.png'/> ".$this->t('Define Executable')."</a>
          </li>
          <li>
            <a href='".Zend_Registry::get('webroot')."/remoteprocessing/job/init/?itemId=".$params['item']->getKey()."'><img alt='' src='".Zend_Registry::get('coreWebroot')."/public/images/icons/job.png'/> ".$this->t('Create a Job')."</a>
          </li> ";
      return $html;
      }
    return "";
    }

  /** get action menu*/
  public function getItemInfo($params)
    {
    if($params['item'] instanceof ItemDao)
      {
      $jobs = $this->Remoteprocessing_Job->getRelatedJob($params['item']);

      $items = array();
      foreach($jobs as $job)
        {
        $items = array_merge($items, $job->getItems());
        }

      Zend_Loader::loadClass('UtilityComponent', BASE_PATH . '/core/controllers/components');
      $component = new UtilityComponent();

      $html =   "<div class='sideElement'>
                    <h1>".$this->t('Related Items')."</h1>
                      <ul>";
      $itemIds = array();
      $i = 0;
      $nameArrayCurrent = explode('.', $params['item']->getName());
      foreach($items as $item)
        {
        $nameArrayItem = explode('.', $item->getName());
        // remove doublons
        if(in_array($item->getKey(), $itemIds))
          {
          continue;
          }
        $itemIds[] = $item->getKey();

        //policy check
        if(!$this->Item->policyCheck($item, $this->userSession->Dao))
          {
          continue;
          }
         // don't show current item
        if($params['item']->getKey() == $item->getKey())
          {
          continue;
          }
        // don't show related results
        if($nameArrayCurrent[0] == $nameArrayItem[0] && end($nameArrayItem) == end($nameArrayCurrent))
          {
          continue;
          }

        $html .=   "<li>";
        $html .=    "<a  element='".$item->getKey()."' href='".Zend_Registry::get('webroot')."/item/".$item->getKey()."'>".$component->slicename($item->getName(),25)."</a>";
        $html .=    "</li>";
        if($i > 7)
          {
          $html .=   "<li>...</li>";
          break;
          }
        $i++;
        }

      $html .=  "</ul>";
      $html .=  "</div>";
      if($i == 0)
        {
        $html =   "";
        }

      $html .= "<div class='sideElementLast'>
                  <h1>".$this->t('Related Jobs')."</h1>
                    <ul>";
      $i = 0;
      foreach($jobs as $job)
        {
        $name = $job->getName();
        if(empty($name))
          {
          $name = $job->getCreationDate();
          }
        $html .=   "<li>";
        $html .=    "<a  element='".$job->getKey()."' href='".Zend_Registry::get('webroot')."/remoteprocessing/job/view/?jobId=".$job->getKey()."'>".$name."</a>";
        $html .=    "</li>";
        if($i > 3)
          {

          $html .=   "<li>...</li>";
          break;
          }
        $i++;
        }
      $html .=  "</ul>";
      $html .=  "</div>";
      return $html;
      }
    return "";
    }

  /** Process results. The result are usually sent by a remote machine. See api component.*/
  public function processProcessingResults($params)
    {
    $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');
    $userModel = MidasLoader::loadModel('User');
    $folderModel = MidasLoader::loadModel('Folder');
    $itemModel = MidasLoader::loadModel('Item');
    $metadataModel = MidasLoader::loadModel('Metadata');
    $jobModel = MidasLoader::loadModel('Job', 'remoteprocessing');
    $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
    $job = $params['job'];

    $userDao = $userModel->load($params['userKey']);
    $creatorDao = $userModel->load($params['creatorId']);

    $folder = false;
    $privateFolder = $userDao->getPrivateFolder();
    if(isset($params['outputFolder']))
      {
      $folder = $folderModel->load($params['outputFolder']);
      }

    if(!$folder)
      {
      $folder = $privateFolder;
      }

    $folderpolicyuserModel->createPolicy($userDao, $folder, MIDAS_POLICY_WRITE);

    $uploadComponent = MidasLoader::loadComponent('Upload');
    $params['outputKeys'] = array();

    foreach($params['tasks'] as $keyTask => $task)
      {
      foreach($task['inputFiles'] as $keyFile => $file)
        {
        $filepath = $params['pathResults'].'/'.$file['fileName'];
        if(file_exists($filepath))
          {
          $tmpArray = array_reverse(explode('.', basename($filepath)));
          $oldfilepath = $filepath;
          $filepath = str_replace(".".$tmpArray[1].".", ".", $filepath);
          rename($oldfilepath, $filepath);
          $item = false;
          if(!empty($file['uuid']))
            {
            $item = $itemModel->getByUuid($file['uuid']);
            }
          if($item == false)
            {
            $item = $uploadComponent->createUploadedItem($userDao, basename($filepath), $filepath, $folder, null, '', true);
            $itempolicyuserModel->createPolicy($creatorDao, $item, MIDAS_POLICY_WRITE);
            }
          $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT);
          $params['tasks'][$keyTask]['inputFiles'][$keyFile]['uuid'] = $item->getUuid();
          $task['inputFiles'][$keyFile]['uuid'] = $item->getUuid();
          }
        }
      foreach($task['outputFiles'] as $keyFile => $file)
        {
        $filepath = $params['pathResults'].'/'.$file['fileName'];
        if(file_exists($filepath))
          {
          $item = false;
          if(!empty($file['uuid']))
            {
            $item = $itemModel->getByUuid($file['uuid']);
            }
          if($item == false)
            {
            $item = $uploadComponent->createUploadedItem($userDao, basename($filepath), $filepath, $folder, null, '', true);
            $itempolicyuserModel->createPolicy($creatorDao, $item, MIDAS_POLICY_WRITE);
            }
          $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_OUPUT);
          $params['tasks'][$keyTask]['outputFiles'][$keyFile]['uuid'] = $item->getUuid();

          $revision = $itemModel->getLastRevision($item);
          foreach($task['inputFiles'] as $key => $input)
            {
            $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'fileInput', $input['name']);
            if(!$metadataDao)
              {
              $metadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'fileInput', $input['name'], '');
              }
            $metadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL,
                        'fileInput', $input['name'], $input['uuid']);
            }

          foreach($task['inputParam'] as $key => $input)
            {
            $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'parameter', $input['name']);
            if(!$metadataDao)
              {
              $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'parameter', $input['name'], '');
              }
            $metadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL,
                        'parameter', $input['name'], $input['value']);
            }
          }
        Zend_Registry::get('notifier')->callback("CALLBACK_REMOTEPROCESSING_POSTPROCESS_TASKOUTPUTFILE", array('file' => $file,  'item' => $item, 'task' => $task));
        }
      }
    $jobComponent = MidasLoader::loadComponent('Job', 'remoteprocessing');

    unlink($params['pathResults'].'/job.xml');
    file_put_contents($params['pathResults'].'/job.xml', $jobComponent->createFormatedXmlFromArray($params));
    $item = $uploadComponent->createUploadedItem($userDao, 'job-'.$params['job']->getKey().'_results.xml', $params['pathResults'].'/job.xml', $privateFolder);
    $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS);
    }

  /** Add a job. This is probably the main method of the module. It will create the job workflow. */
  public function addJob($params)
    {
    $jobComponent = MidasLoader::loadComponent('Job', 'remoteprocessing');
    $jobComponent->processJobParameters($params);
    }
  } //end class
?>
