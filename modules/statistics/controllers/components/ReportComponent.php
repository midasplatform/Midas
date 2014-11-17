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

/**
 * Generates and sends piwik statistics reports to admin users
 */
class Statistics_ReportComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'statistics';

    /** generate report */
    public function generate()
    {
        /** @var ErrorlogModel $errorLogModel */
        $errorLogModel = MidasLoader::loadModel('Errorlog');

        /** @var AssetstoreModel $assetStoreModel */
        $assetStoreModel = MidasLoader::loadModel('Assetstore');
        $reportContent = '';
        $reportContent .= '<b>Midas Report: '.Zend_Registry::get('configGlobal')->application->name.'</b>';
        $reportContent .= '<br/>http://'.$_SERVER['SERVER_NAME'];

        $reportContent .= '<br/><br/><b>Status</b>';
        $errors = $errorLogModel->getLog(
            date("Y-m-d H:i:s", strtotime('-1 day'.date('Y-m-j G:i:s'))),
            date("Y-m-d H:i:s"),
            'all',
            2
        );
        $reportContent .= "<br/>Yesterday Errors: ".count($errors);
        $assetStores = $assetStoreModel->getAll();
        foreach ($assetStores as $assetStore) {
            $totalSpace = UtilityComponent::diskTotalSpace($assetStore->getPath());
            $freeSpace = UtilityComponent::diskFreeSpace($assetStore->getPath());
            $reportContent .= '<br/>Assetstore '.$assetStore->getName();
            if ($totalSpace > 0) {
                $reportContent .= ', Free space: '.round(($freeSpace / $totalSpace) * 100, 2).'%';
            }
        }

        $reportContent .= '<br/><br/><b>Dashboard</b><br/>';
        $dashboard = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_DASHBOARD');
        ksort($dashboard);
        foreach ($dashboard as $module => $dasboard) {
            $reportContent .= '-'.ucfirst($module);
            $reportContent .= '<table>';
            foreach ($dasboard as $name => $status) {
                $reportContent .= '<tr>';
                $reportContent .= '  <td>'.$name.'</td>';
                if ($status) {
                    $reportContent .= '  <td>ok</td>';
                } else {
                    $reportContent .= '  <td>Error</td>';
                }
                if (isset($status[1])) {
                    $reportContent .= '  <td>'.$status[1].'</td>';
                }
                $reportContent .= '</tr>';
            }
            $reportContent .= '</table>';
        }

        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $piwikUrl = $settingModel->getValueByName(STATISTICS_PIWIK_URL_KEY, $this->moduleName);
        $piwikId = $settingModel->getValueByName(STATISTICS_PIWIK_SITE_ID_KEY, $this->moduleName);
        $piwikApiKey = $settingModel->getValueByName(STATISTICS_PIWIK_API_KEY_KEY, $this->moduleName);

        $content = file_get_contents(
            $piwikUrl.'/?module=API&format=json&method=VisitsSummary.get&period=day&date=yesterday&idSite='.$piwikId.'&token_auth='.$piwikApiKey
        );
        $piwik = json_decode($content);
        $reportContent .= '<br/><b>Statistics (yesterday)</b>';
        $reportContent .= '<br/>Number of visit: '.$piwik->nb_uniq_visitors;
        $reportContent .= '<br/>Number of actions: '.$piwik->nb_actions;
        $reportContent .= '<br/>Average time on the website: '.$piwik->avg_time_on_site;
        $this->report = $reportContent;

        return $reportContent;
    }

    /** send the report to admins */
    public function send()
    {
        $subject = 'Statistics Report';
        $body = $this->report;
        $userModel = MidasLoader::loadModel('User');
        $admins = $userModel->getAdmins();
        foreach ($admins as $admin) {
            $email = $admin->getEmail();
            Zend_Registry::get('notifier')->callback(
                'CALLBACK_CORE_SEND_MAIL_MESSAGE',
                array(
                    'to' => $email,
                    'subject' => $subject,
                    'html' => $body,
                    'event' => 'statistics_report',
                )
            );
        }
    }
}
