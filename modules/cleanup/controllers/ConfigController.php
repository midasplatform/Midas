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

/** Configure controller for cleanup module */
class Cleanup_ConfigController extends Cleanup_AppController
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
        $formArray['olderThan']->setValue($config->days);
        $this->view->configForm = $formArray;

        if ($this->_request->isPost()) {
            $this->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $submitConfig = $this->getParam('submitConfig');
            if (isset($submitConfig)) {
                $jobModel = MidasLoader::loadModel('Job', 'scheduler');
                $jobs = $jobModel->getJobsByTask('TASK_CLEANUP_PERFORM_CLEANUP');
                $jobReport = false;
                foreach ($jobs as $job) {
                    if ($job->getTask() == 'TASK_CLEANUP_PERFORM_CLEANUP') {
                        $jobReport = $job;
                        break;
                    }
                }
                if ($jobReport == false) {
                    $job = new Scheduler_JobDao();
                    $job->setTask('TASK_CLEANUP_PERFORM_CLEANUP');
                    $job->setPriority('1');
                    $job->setRunOnlyOnce('0');
                    $job->setFireTime(date('Y-m-j', strtotime('+1 day'.date('Y-m-j G:i:s'))).' 1:00:00');
                    $job->setTimeInterval(24 * 60 * 60);
                    $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
                    $job->setCreatorId($this->userSession->Dao->getKey());
                    $job->setParams(
                        JsonComponent::encode(
                            array('tempDirectory' => $this->getTempDirectory(), 'days' => $this->getParam('olderThan'))
                        )
                    );
                    $jobModel->save($job);
                } else {
                    $jobReport->setParams(
                        JsonComponent::encode(
                            array('tempDirectory' => $this->getTempDirectory(), 'days' => $this->getParam('olderThan'))
                        )
                    );
                    $jobModel->save($jobReport);
                }

                $config->days = $this->getParam('olderThan');

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename(LOCAL_CONFIGS_PATH.'/'.$this->moduleName.'.local.ini');
                $writer->write();
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
        }
    }
}
