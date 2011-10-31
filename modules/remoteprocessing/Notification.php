<?php
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
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_EXECUTABLE_RESULTS', 'processProcessingResults');
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_ADD_JOB', 'addJob');
    }//end init

  /** check if item contains an executable */
  public function isExecutable($params)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
    $item = $params['item'];
    $revision = $itemModel->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $b)
      {
      if(is_executable($b->getFullPath()))
        {
        return true;
        }
      }
    return false;
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
            <a href='".Zend_Registry::get('webroot')."/remoteprocessing/job/manage/?itemId=".$params['item']->getKey()."'><img alt='' src='".Zend_Registry::get('coreWebroot')."/public/images/icons/job.png'/> ".$this->t('Manage Processing Jobs')."</a>
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

      $html =   "<div class='sideElementLast'>
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
        if($i > 10)
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
      return $html;
      }
    return "";
    }

    /** Process results*/
  public function processProcessingResults($params)
    {
    $modulesConfig=Zend_Registry::get('configsModules');

    $modelLoad = new MIDAS_ModelLoader();
    $userModel = $modelLoad->loadModel('User');
    $folderModel = $modelLoad->loadModel('Folder');
    $itemModel = $modelLoad->loadModel('Item');
    $metadataModel = $modelLoad->loadModel('Metadata');
    $jobModel = $modelLoad->loadModel('Job', 'remoteprocessing');
    $job = $jobModel->load($params['job_id']);

    $userDao = $userModel->load($params['userKey']);

    $folder = $folderModel->load($params['ouputFolders'][0]);

    $componentLoader = new MIDAS_ComponentLoader();
    $uploadComponent = $componentLoader->loadComponent('Upload');

    foreach($params['output'] as $file)
      {
      $filepath = $params['pathResults'].'/'.$file;
      if(file_exists($filepath))
        {
        $tmpArray = array_reverse(explode('.', basename($filepath)));
        $item = $uploadComponent->createUploadedItem($userDao, basename($filepath), $filepath, $folder);
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
      $logFile = BASE_PATH.'/tmp/misc/'.uniqid();
      file_put_contents($logFile, $params['log']);
      $item = $uploadComponent->createUploadedItem($userDao, 'log.txt', $logFile, $folder);
      $jobModel->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_OUPUT);
      unlink($logFile);
      }
    }

  /** get Config Tabs */
  public function addJob($params)
    {
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

    $job->setParams(JsonComponent::encode($params['params']));
    $this->Remoteprocessing_Job->save($job);

    if(!empty($params['params']['input']))
      {
      foreach($params['params']['input'] as $itemId)
        {
        $item = $this->Item->load($itemId);
        if($item != false)
          {
          $this->Remoteprocessing_Job->addItemRelation($job, $item, MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT);
          }
        }
      }
    return;
    }

  } //end class
?>