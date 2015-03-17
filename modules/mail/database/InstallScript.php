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

require_once BASE_PATH.'/modules/mail/constant/module.php';

/** Install the mail module. */
class Mail_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'mail';

    /** Post database install. */
    public function postInstall()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(MAIL_PROVIDER_KEY, MAIL_PROVIDER_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_FROM_ADDRESS_KEY, MAIL_FROM_ADDRESS_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_ADDRESS_VERIFICATION_KEY, MAIL_ADDRESS_VERIFICATION_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SEND_GRID_USERNAME_KEY, MAIL_SEND_GRID_USERNAME_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SEND_GRID_PASSWORD_KEY, MAIL_SEND_GRID_PASSWORD_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SMTP_HOST_KEY, MAIL_SMTP_HOST_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SMTP_PORT_KEY, MAIL_SMTP_PORT_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SMTP_USE_SSL_KEY, MAIL_SMTP_USE_SSL_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SMTP_USERNAME_KEY, MAIL_SMTP_USERNAME_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(MAIL_SMTP_PASSWORD_KEY, MAIL_SMTP_PASSWORD_DEFAULT_VALUE, $this->moduleName);
    }
}
