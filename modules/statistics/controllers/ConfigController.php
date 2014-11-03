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

/** Statistics module configure controller */
class Statistics_ConfigController extends Statistics_AppController
{
    public $_moduleForms = array('Config');
    public $_components = array('Utility', 'Date');

    /** index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $options = array('allowModifications' => true);
        if (file_exists(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini')) {
            $config = new Zend_Config_Ini(
                LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini',
                'global',
                $options
            );
        } else {
            $config = new Zend_Config_Ini(
                BASE_PATH.'/modules/'.$this->moduleName.'/configs/module.ini',
                'global',
                $options
            );
        }

        $configForm = $this->ModuleForm->Config->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);
        $formArray['ipinfodbapikey']->setValue($config->ipinfodb->apikey);
        $formArray['piwikapikey']->setValue($config->piwik->apikey);
        $formArray['piwikid']->setValue($config->piwik->id);
        $formArray['piwikurl']->setValue($config->piwik->url);
        $formArray['report']->setValue($config->report);
        $this->view->configForm = $formArray;

        if ($this->_request->isPost()) {
            $this->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $submitConfig = $this->getParam('submitConfig');
            if (isset($submitConfig)) {
                $jobModel = MidasLoader::loadModel('Job', 'scheduler');
                $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_SEND_REPORT');
                $jobReport = false;
                foreach ($jobs as $job) {
                    $jobReport = $job;
                    break;
                }
                if ($config->report == 1) {
                    if ($jobReport == false) {
                        $job = new Scheduler_JobDao();
                        $job->setTask('TASK_STATISTICS_SEND_REPORT');
                        $job->setPriority('1');
                        $job->setRunOnlyOnce(false);
                        $job->setFireTime(date('Y-m-d', strtotime('+1 day'.date('Y-m-d H:i:s'))).' 01:00:00');
                        $job->setTimeInterval(24 * 60 * 60);
                        $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                        $job->setParams(JsonComponent::encode(array()));
                        $jobModel->save($job);
                    }
                } else {
                    if ($jobReport != false) {
                        $jobModel->delete($jobReport);
                    }
                }

                // Register offline geolocation task
                $jobs = $jobModel->getJobsByTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
                $jobLocation = false;
                foreach ($jobs as $job) {
                    $jobLocation = $job;
                    break;
                }
                if ($jobLocation == false) {
                    $job = new Scheduler_JobDao();
                    $job->setTask('TASK_STATISTICS_PERFORM_GEOLOCATION');
                    $job->setPriority(1);
                    $job->setRunOnlyOnce(0);
                    $job->setFireTime(date('Y-m-d', strtotime('+1 day'.date('Y-m-d H:i:s'))).' 01:00:00');
                    $job->setTimeInterval(1 * 60 * 60);
                    $jobLocation = $job;
                }
                $jobLocation->setParams(JsonComponent::encode(array('apikey' => $this->getParam('ipinfodbapikey'))));
                $jobLocation->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                $jobModel->save($jobLocation);

                $config->piwik->apikey = $this->getParam('piwikapikey');
                $config->piwik->id = $this->getParam('piwikid');
                $config->piwik->url = $this->getParam('piwikurl');
                $config->ipinfodb->apikey = $this->getParam('ipinfodbapikey');
                $config->report = $this->getParam('report');

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
                $writer->write();
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
        }
    }
}
