<?php

/** Configure controller for cleanup module */
class Cleanup_ConfigController extends Cleanup_AppController
{
  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Date');

  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    if(file_exists(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini'))
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini', true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();
    $formArray = $this->getFormAsArray($configForm);
    $formArray['olderThan']->setValue($applicationConfig['global']['days']);
    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini.old'))
          {
          unlink(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini.old');
          }
        if(file_exists(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini'))
          {
          rename(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini', BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini.old');
          }
        $loader = new MIDAS_ModelLoader();
        $jobModel = $loader->loadModel('Job', 'scheduler');
        $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
        $jobReport = false;
        foreach($jobs as $job)
          {
          if($job->getTask() == 'TASK_CLEANUP_PERFORM_CLEANUP')
            {
            $jobReport = $job;
            break;
            }
          }
        if($jobReport == false)
          {
          $job = new Scheduler_JobDao();
          $job->setTask('TASK_CLEANUP_PERFORM_CLEANUP');
          $job->setPriority('1');
          $job->setRunOnlyOnce('0');
          $job->setFireTime(date('Y-m-j', strtotime('+1 day'.date('Y-m-j G:i:s'))).' 1:00:00');
          $job->setTimeInterval(24 * 60 * 60);
          $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
          $job->setCreatorId($this->userSession->Dao->getKey());
          $job->setParams(JsonComponent::encode(array('tempDirectory' => $this->getTempDirectory(),
                                                      'days' => $this->_getParam('olderThan'))));
          $jobModel->save($job);
          }
        else
          {
          $jobReport->setParams(JsonComponent::encode(array('tempDirectory' => $this->getTempDirectory(),
                                                            'days' => $this->_getParam('olderThan'))));
          $jobModel->save($jobReport);
          }
        $applicationConfig['global']['days'] = $this->_getParam('olderThan');
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini', $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changes saved'));
        }
      }
    }

}//end class
