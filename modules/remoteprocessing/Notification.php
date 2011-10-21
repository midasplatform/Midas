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
    }//end init

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