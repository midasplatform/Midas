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
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_EXECUTABLE_RESULTS', 'processProcessingResults');
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
    $html =  "<li class='processButton' style='margin-left:5px;' title='Process' rel='".Zend_Registry::get('webroot')."/remoteprocessing/index/selectaction'>
                <a href='#'><img id='processButtonImg' src='".Zend_Registry::get('webroot')."/modules/remoteprocessing/public/images/process-ok.png' alt='Start a process'/>
                <img id='processButtonLoadiing' style='margin-top:5px;display:none;' src='".Zend_Registry::get('webroot')."/core/public/images/icons/loading.gif' alt=''/>
                  Process
                </a>
              </li> ";
    return $html;
    }

  /** check if item contains an executable */
  public function isExecutable($params)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $item = $params['item'];
    $executableComponent = $componentLoader->loadComponent("Executable", "remoteprocessing");
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
      if($i == 0)
        {
        return "";
        }
      $html .=  "</ul>";
      $html .=  "</div>";

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

    /** Process results*/
  public function processProcessingResults($params)
    {
    $modulesConfig=Zend_Registry::get('configsModules');

    $modelLoad = new MIDAS_ModelLoader();
    $itempolicyuserModel = $modelLoad->loadModel('Itempolicyuser');
    $userModel = $modelLoad->loadModel('User');
    $folderModel = $modelLoad->loadModel('Folder');
    $itemModel = $modelLoad->loadModel('Item');
    $metadataModel = $modelLoad->loadModel('Metadata');
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $job = $jobModel->load($params['job_id']);

    $userDao = $userModel->load($params['userKey']);
    $creatorDao = $userModel->load($params['creator_id']);

    if(isset($params['ouputFolders'][0]))
      {
      $folder = $folderModel->load($params['ouputFolders'][0]);
      }
    else
      {
      $folder = $userDao->getPrivateFolder();
      }

    $componentLoader = new MIDAS_ComponentLoader();
    $uploadComponent = $componentLoader->loadComponent('Upload');
    $params['outputKeys'] = array();

    foreach($params['output'] as $file)
      {
      $filepath = $params['pathResults'].'/'.$file;
      if(file_exists($filepath))
        {
        $tmpArray = array_reverse(explode('.', basename($filepath)));
        $oldfilepath = $filepath;
        $filepath = str_replace(".".$tmpArray[1].".", ".", $filepath);
        rename($oldfilepath, $filepath);
        $item = $uploadComponent->createUploadedItem($userDao, basename($filepath), $filepath, $folder);
        $params['outputKeys'][$tmpArray[1]][] = $item->getKey();
        $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_OUPUT);
        // add parameter metadata
        if(is_numeric($tmpArray[1]) && isset($params['parametersList']) && isset($params['optionMatrix']))
          {
          $revision = $itemModel->getLastRevision($item);
          foreach($params['parametersList'] as $key => $name)
            {
            if(isset($params['optionMatrix'][$tmpArray[1]][$key]))
              {
              $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'parameter', $name);
              if(!$metadataDao)
                {
                $metadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'parameter', $name, '');
                }
              $metadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL,
                           'parameter', $name, $params['optionMatrix'][$tmpArray[1]][$key]);
              }
            }
          }
        }
      }
    if(isset($params['log']) && !empty($params['log']))
      {
      $jobComponenet = $componentLoader->loadComponent('Job', 'remoteprocessing');
      $xmlResults = $jobComponenet->computeLogs($job, $params['log'], $params);
      $logFile = $pathFile = $this->getTempDirectory().'/'.uniqid();
      file_put_contents($logFile, $xmlResults);
      $item = $uploadComponent->createUploadedItem($userDao, 'job-'.$params['job_id'].'_results.xml', $logFile, $folder);
      $itempolicyuserModel->createPolicy($creatorDao, $item, MIDAS_POLICY_READ);
      $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS);
      unlink($logFile);
      }
    }

  /** get Config Tabs */
  public function addJob($params)
    {
    // dynamically process the params
    if(isset($params['params']['cmdOptions']) && empty($params['script'])&& isset($params['params']['executable']))
      {
      $componentLoader = new MIDAS_ComponentLoader();
      $executableComponent = $componentLoader->loadComponent('Executable', 'remoteprocessing');
      $tmp = $executableComponent->processScheduledJobParameters($params);
      $params['params'] = $tmp['parameters'];
      $params['script'] = $tmp['script'];
      }

    if(!isset($params['script']) || empty($params['script']))
      {
      throw new Zend_Exception('Unable to find script');
      }
    if(!isset($params['os']) || empty($params['os']))
      {
      throw new Zend_Exception('Unable to find os');
      }
    if(!isset($params['condition']))
      {
      throw new Zend_Exception('Unable to find condition');
      }

    $job = new Remoteprocessing_JobDao();
    $job->setScript($params['script']);
    unset($params['script']);
    $job->setOs($params['os']);
    unset($params['os']);
    $job->setCondition($params['condition']);
    unset($params['condition']);

    if(isset($params['expiration']))
      {
      $job->setExpirationDate($params['expiration']);
      }
    else
      {
      $date = new Zend_Date();
      $date->add('5', Zend_Date::HOUR);
      $job->setExpirationDate($date->toString('c'));
      }

    if(isset($params['params']['creator_id']))
      {
      $job->setCreatorId($params['params']['creator_id']);
      }
    if(isset($params['params']['job_name']))
      {
      $job->setName($params['params']['job_name']);
      }

    $job->setParams(JsonComponent::encode($params['params']));
    $this->Remoteprocessing_Job->save($job);

    if(!empty($params['params']['input']))
      {
      foreach($params['params']['input'] as $itemId)
        {
        $item = $this->Item->load($itemId);
        if($item != false && $item->getKey() != $params['params']['executable'])
          {
          $this->Remoteprocessing_Job->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT);
          }
        elseif($item != false)
          {
          $this->Remoteprocessing_Job->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_EXECUTABLE);
          }
        }
      }
    return;
    }

  } //end class
?>