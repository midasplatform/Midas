<?php
/** notification manager*/
require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

class Remoteprocessing_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'remoteprocessing';
  public $_moduleComponents=array('Api');
  public $_moduleModels=array('Job');
  public $_moduleDaos=array('Job');

  /** init notification process*/
  public function init()
    {
    $this->enableWebAPI($this->moduleName);
    $this->addTask("TASK_REMOTEPROCESSING_ADD_JOB", 'addJob', "");
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_IS_EXECUTABLE', 'isExecutable');
    $this->addCallBack('CALLBACK_REMOTEPROCESSING_EXECUTABLE_RESULTS', 'processProcessingResults');

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
    $communityKey = $modulesConfig['zeiss']->community->results;
    $modelLoad = new MIDAS_ModelLoader();
    $communityModel = $modelLoad->loadModel('Community');
    $userModel = $modelLoad->loadModel('User');

    $userDao = $userModel->load($params['userKey']);

    $communityDao = $communityModel->load($communityKey);
    $folder = $communityDao->getPublicFolder();

    $componentLoader = new MIDAS_ComponentLoader();
    $uploadComponent = $componentLoader->loadComponent('Upload');

    foreach($params['output'] as $file)
      {
      $filepath = $params['pathResults'].'/'.$file;
      if(file_exists($filepath))
        {
        $item = $uploadComponent->createUploadedItem($userDao, basename($filepath), $filepath, $folder);
        }
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
    return;
    }

  } //end class
?>