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

    /** Process results*/
  public function processProcessingResults($params)
    {
    $modulesConfig=Zend_Registry::get('configsModules');

    $modelLoad = new MIDAS_ModelLoader();
    $userModel = $modelLoad->loadModel('User');
    $folderModel = $modelLoad->loadModel('Folder');
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
        $item = $uploadComponent->createUploadedItem($userDao, basename($filepath), $filepath, $folder);
        $jobModel->addItemRelation($job, $item);
        }
      }
    if(isset($params['log']) && !empty($params['log']))
      {
      $logFile = BASE_PATH.'/tmp/misc/'.uniqid();
      file_put_contents($logFile, $params['log']);
      $item = $uploadComponent->createUploadedItem($userDao, 'log.txt', $logFile, $folder);
      $jobModel->addItemRelation($job, $item);
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
          $this->Remoteprocessing_Job->addItemRelation($job, $item);
          }
        }
      }
    return;
    }

  } //end class
?>