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

/** Admin controller for the statistics module. */
class Statistics_AdminController extends Statistics_AppController
{
    /** @var array */
    public $_models = array('Setting');

    /** @var array */
    public $_moduleComponents = array('Admin');

    /** Index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->pageTitle = 'Statistics Module Configuration';
        $form = new Statistics_Form_Admin();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($form->isValid($data)) {
                $values = $form->getValues();

                foreach ($values as $key => $value) {
                    if ($key !== 'csrf' && !is_null($value)) {
                        $this->Setting->setConfig($key, $value, $this->moduleName);
                    }
                }

                $this->ModuleComponent->Admin->schedulePerformGeolocationJob(
                    $values[STATISTICS_IP_INFO_DB_API_KEY_KEY],
                    $this->userSession->Dao
                );

                $this->ModuleComponent->Admin->scheduleOrCancelSendReportJob(
                    $values[STATISTICS_SEND_DAILY_REPORTS_KEY] == 1,
                    $this->userSession->Dao
                );
            }

            $form->populate($data);
        } else {
            $elements = $form->getElements();

            foreach ($elements as $element) {
                $name = $element->getName();

                if ($name !== 'csrf' && $name !== 'submit') {
                    $value = $this->Setting->getValueByName($name, $this->moduleName);

                    if (!is_null($value)) {
                        $form->setDefault($name, $value);
                    }
                }
            }
        }

        $this->view->form = $form;
        session_start();
    }
}
