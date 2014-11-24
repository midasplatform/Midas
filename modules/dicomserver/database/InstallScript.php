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

require_once BASE_PATH.'/modules/dicomserver/constant/module.php';

/** Install the dicomserver module. */
class Dicomserver_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'dicomserver';

    /** Post database install. */
    public function postInstall()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(MIDAS_DICOMSERVER_DCM2XML_COMMAND_KEY, MIDAS_DICOMSERVER_DCM2XML_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_STORESCP_COMMAND_KEY, MIDAS_DICOMSERVER_STORESCP_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_STORESCP_PORT_KEY, MIDAS_DICOMSERVER_STORESCP_PORT_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_KEY, MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY, MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_DESTINATION_FOLDER_KEY, MIDAS_DICOMSERVER_DESTINATION_FOLDER_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_KEY, MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_DCMQRSCP_PORT_KEY, MIDAS_DICOMSERVER_DCMQRSCP_PORT_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_KEY, MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_SERVER_AE_TITLE_KEY, MIDAS_DICOMSERVER_SERVER_AE_TITLE_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MIDAS_DICOMSERVER_PEER_AES_KEY, MIDAS_DICOMSERVER_PEER_AES_DEFAULT_VALUE, $this->moduleName);
    }
}
