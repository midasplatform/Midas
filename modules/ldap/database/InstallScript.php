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

require_once BASE_PATH.'/modules/ldap/constant/module.php';

/** Install the ldap module. */
class Ldap_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'ldap';

    /** Post database install. */
    public function postInstall()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(LDAP_HOST_NAME_KEY, LDAP_HOST_NAME_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_PORT_KEY, LDAP_PORT_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_BACKUP_SERVER_KEY, LDAP_BACKUP_SERVER_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_BIND_RDN_KEY, LDAP_BIND_RDN_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_BIND_PASSWORD_KEY, LDAP_BIND_PASSWORD_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_BASE_DN_KEY, LDAP_BASE_DN_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_PROTOCOL_VERSION_KEY, LDAP_PROTOCOL_VERSION_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_SEARCH_TERM_KEY, LDAP_SEARCH_TERM_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_PROXY_BASE_DN_KEY, LDAP_PROXY_BASE_DN_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_PROXY_PASSWORD_KEY, LDAP_PROXY_PASSWORD_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_USE_ACTIVE_DIRECTORY_KEY, LDAP_USE_ACTIVE_DIRECTORY_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(LDAP_AUTO_ADD_UNKNOWN_USER_KEY, LDAP_AUTO_ADD_UNKNOWN_USER_DEFAULT_VALUE, $this->moduleName);
    }
}
