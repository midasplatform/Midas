<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Upgrade the dicomserver module to version 1.1.0. */
class Dicomserver_Upgrade_1_1_0 extends MIDASUpgrade
{
    /** @var string */
    public $moduleName = 'dicomserver';

    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $configPath = LOCAL_CONFIGS_PATH.DIRECTORY_SEPARATOR.$this->moduleName.'.local.ini';

        if (file_exists($configPath)) {
            $config = new Zend_Config_Ini($configPath, 'global');
            $settingModel->setConfig(MIDAS_DICOMSERVER_DCM2XML_COMMAND_KEY, $config->get('dcm2xml', MIDAS_DICOMSERVER_DCM2XML_COMMAND_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_STORESCP_COMMAND_KEY, $config->get('storescp', MIDAS_DICOMSERVER_STORESCP_COMMAND_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_STORESCP_PORT_KEY, $config->get('storescp_port', MIDAS_DICOMSERVER_STORESCP_PORT_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_KEY, $config->get('storescp_study_timeout', MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY, $config->get('receptiondir', MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_DESTINATION_FOLDER_KEY, $config->get('pydas_dest_folder', MIDAS_DICOMSERVER_DESTINATION_FOLDER_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_KEY, $config->get('dcmqrscp', MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_DCMQRSCP_PORT_KEY, $config->get('dcmqrscp_port', MIDAS_DICOMSERVER_DCMQRSCP_PORT_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_KEY, $config->get('dcmqridx', MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_SERVER_AE_TITLE_KEY, $config->get('server_ae_title', MIDAS_DICOMSERVER_SERVER_AE_TITLE_DEFAULT_VALUE), $this->moduleName);
            $settingModel->setConfig(MIDAS_DICOMSERVER_PEER_AES_KEY, $config->get('peer_aes', MIDAS_DICOMSERVER_PEER_AES_DEFAULT_VALUE), $this->moduleName);

            $config = new Zend_Config_Ini($configPath, null, true);
            unset($config->global->dcm2xml);
            unset($config->global->storescp);
            unset($config->global->storescp_port);
            unset($config->global->storescp_study_timeout);
            unset($config->global->receptiondir);
            unset($config->global->pydas_dest_folder);
            unset($config->global->dcmqrscp);
            unset($config->global->dcmqrscp_port);
            unset($config->global->dcmqridx);
            unset($config->global->server_ae_title);
            unset($config->global->peer_aes);

            $writer = new Zend_Config_Writer_Ini();
            $writer->setConfig($config);
            $writer->setFilename($configPath);
            $writer->write();
        } else {
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
}
