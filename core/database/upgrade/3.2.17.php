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

/** Upgrade the core to version 3.2.17. */
class Upgrade_3_2_17 extends MIDASUpgrade
{
    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');

        $config = new Zend_Config_Ini(APPLICATION_CONFIG, 'global');
        $settingModel->setConfig('address_verification', $config->get('verifyemail', 0), 'mail');

        if ($config->get('smtpfromaddress')) {
            $fromAddress = $config->get('smtpfromaddress');
        } elseif (ini_get('sendmail_from')) {
            $fromAddress = ini_get('sendmail_from');
        } else {
            $fromAddress = 'no-reply@example.org'; // RFC2606
        }

        $settingModel->setConfig('from_address', $fromAddress, 'mail');

        if ($config->get('smtpserver')) {
            $components = parse_url($config->get('smtpserver'));

            if (isset($components['host'])) {
                $settingModel->setConfig('smtp_host', $components['host'], 'mail');
            }

            if (isset($components['port'])) {
                $settingModel->setConfig('smtp_port', $components['port'], 'mail');

                if ($components['port'] === 587) {
                    $settingModel->setConfig('smtp_use_ssl', 1, 'mail');
                }
            }

            if (isset($components['user'])) {
                $settingModel->setConfig('smtp_username', $components['user'], 'mail');
            }

            if (isset($components['pass'])) {
                $settingModel->setConfig('smtp_password', $components['pass'], 'mail');
            }
        }

        if ($config->get('smtpuser')) {
            $settingModel->setConfig('smtp_username', $config->get('smtpuser'), 'mail');
        }

        if ($config->get('smtppassword')) {
            $settingModel->setConfig('smtp_password', $config->get('smtppassword'), 'mail');
        }

        if ($settingModel->getValueByName('smtp_host', 'mail')) {
            $provider = 'smtp';
        } else {
            $provider = 'mail';
        }

        $settingModel->setConfig('provider', $provider, 'mail');

        /** @var UtilityComponent $utilityComponent */
        $utilityComponent = MidasLoader::loadComponent('Utility');
        $utilityComponent->installModule('mail');

        $config = new Zend_Config_Ini(APPLICATION_CONFIG, null, true);
        unset($config->global->smtpfromaddress);
        unset($config->global->smtpserver);
        unset($config->global->smtpuser);
        unset($config->global->smtppassword);
        unset($config->global->verifyemail);

        $writer = new Zend_Config_Writer_Ini();
        $writer->setConfig($config);
        $writer->setFilename(APPLICATION_CONFIG);
        $writer->write();
    }
}
