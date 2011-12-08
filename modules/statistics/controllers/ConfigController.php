<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
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

/** Statistics module configure controller */
class Statistics_ConfigController extends Statistics_AppController
{
  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Date');

  /** index action*/
  function indexAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception('You should be an administrator');
      }

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
    $formArray['piwikurl']->setValue($applicationConfig['global']['piwik.url']);
    $formArray['piwikapikey']->setValue($applicationConfig['global']['piwik.apikey']);
    $formArray['piwikid']->setValue($applicationConfig['global']['piwik.id']);
    $formArray['ipinfodbapikey']->setValue($applicationConfig['global']['ipinfodb.apikey']);
    $formArray['report']->setValue($applicationConfig['global']['report']);

    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
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
        $applicationConfig['global']['piwik.url'] = $this->_getParam('piwikurl');
        $applicationConfig['global']['report'] = $this->_getParam('report');
        $loader = new MIDAS_ModelLoader();
        $jobModel = $loader->loadModel('Job', 'scheduler');
        $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_SEND_REPORT');
        $jobReport = false;
        foreach($jobs as $job)
          {
          if($job->getTask() == 'TASK_STATISTICS_SEND_REPORT')
            {
            $jobReport = $job;
            break;
            }
          }
        if($applicationConfig['global']['report'] == 1)
          {
          if($jobReport == false)
            {
            $job = new Scheduler_JobDao();
            $job->setTask('TASK_STATISTICS_SEND_REPORT');
            $job->setPriority('1');
            $job->setRunOnlyOnce(false);
            $job->setFireTime(date('Y-m-j', strtotime('+1 day'.date('Y-m-j G:i:s'))).' 1:00:00');
            $job->setTimeInterval(24 * 60 * 60);
            $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
            $job->setParams(JsonComponent::encode(array()));
            $jobModel->save($job);
            }
          }
        else
          {
          if($jobReport != false)
            {
            $jobModel->delete($jobReport);
            }
          }
        $applicationConfig['global']['piwik.id'] = $this->_getParam('piwikid');
        $applicationConfig['global']['piwik.apikey'] = $this->_getParam('piwikapikey');
        $applicationConfig['global']['ipinfodb.apikey'] = $this->_getParam('ipinfodbapikey');
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/'.$this->moduleName.'.local.ini', $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changes saved'));
        }
      }
    }

}//end class
