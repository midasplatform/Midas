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

/** Upgrade the statistics module to version 1.1.0. */
class Statistics_Upgrade_1_1_0 extends MIDASUpgrade
{
    /** @var string */
    public $moduleName = 'statistics';

    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $configPath = LOCAL_CONFIGS_PATH.DIRECTORY_SEPARATOR.$this->moduleName.'.local.ini';

        if (file_exists($configPath)) {
            $config = new Zend_Config_Ini($configPath, 'global');
            $piwikUrl = isset($config->piwik->url) ? $config->piwik->url : STATISTICS_PIWIK_URL_DEFAULT_VALUE;
            $settingModel->setConfig(STATISTICS_PIWIK_URL_KEY, $piwikUrl, $this->moduleName);
            $piwikId = isset($config->piwik->id) ? $config->piwik->id : STATISTICS_PIWIK_SITE_ID_DEFAULT_VALUE;
            $settingModel->setConfig(STATISTICS_PIWIK_SITE_ID_KEY, $piwikId, $this->moduleName);
            $piwikApiKey = isset($config->piwik->apikey) ? $config->piwik->apikey : STATISTICS_PIWIK_API_KEY_DEFAULT_VALUE;
            $settingModel->setConfig(STATISTICS_PIWIK_API_KEY_KEY, $piwikApiKey, $this->moduleName);
            $ipInfoDbApiKey = isset($config->ipinfodb->apikey) ? $config->ipinfodb->apikey : STATISTICS_IP_INFO_DB_API_KEY_DEFAULT_VALUE;
            $settingModel->setConfig(STATISTICS_IP_INFO_DB_API_KEY_KEY, $ipInfoDbApiKey, $this->moduleName);
            $settingModel->setConfig(STATISTICS_SEND_DAILY_REPORTS_KEY, $config->get('report', STATISTICS_SEND_DAILY_REPORTS_DEFAULT_VALUE), $this->moduleName);

            $config = new Zend_Config_Ini($configPath, null, true);
            unset($config->global->piwik->url);
            unset($config->global->piwik->id);
            unset($config->global->piwik->pikey);
            unset($config->global->ipinfodb->apikey);
            unset($config->global->report);

            $writer = new Zend_Config_Writer_Ini();
            $writer->setConfig($config);
            $writer->setFilename($configPath);
            $writer->write();
        } else {
            $settingModel->setConfig(STATISTICS_PIWIK_URL_KEY, STATISTICS_PIWIK_URL_DEFAULT_VALUE, $this->moduleName);
            $settingModel->setConfig(STATISTICS_PIWIK_SITE_ID_KEY, STATISTICS_PIWIK_SITE_ID_DEFAULT_VALUE, $this->moduleName);
            $settingModel->setConfig(STATISTICS_PIWIK_API_KEY_KEY, STATISTICS_PIWIK_API_KEY_DEFAULT_VALUE, $this->moduleName);
            $settingModel->setConfig(STATISTICS_IP_INFO_DB_API_KEY_KEY, STATISTICS_IP_INFO_DB_API_KEY_DEFAULT_VALUE, $this->moduleName);
            $settingModel->setConfig(STATISTICS_SEND_DAILY_REPORTS_KEY, STATISTICS_SEND_DAILY_REPORTS_DEFAULT_VALUE, $this->moduleName);
        }
    }
}
