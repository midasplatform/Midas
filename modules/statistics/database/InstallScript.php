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

require_once BASE_PATH.'/modules/statistics/constant/module.php';

/** Install the statistics module. */
class Statistics_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'statistics';

    /** Post database install. */
    public function postInstall()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(STATISTICS_PIWIK_URL_KEY, STATISTICS_PIWIK_URL_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(STATISTICS_PIWIK_SITE_ID_KEY, STATISTICS_PIWIK_SITE_ID_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(STATISTICS_PIWIK_API_KEY_KEY, STATISTICS_PIWIK_API_KEY_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(STATISTICS_IP_INFO_DB_API_KEY_KEY, STATISTICS_IP_INFO_DB_API_KEY_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(STATISTICS_SEND_DAILY_REPORTS_KEY, STATISTICS_SEND_DAILY_REPORTS_DEFAULT_VALUE, $this->moduleName);
    }
}
