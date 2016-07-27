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

/** Upgrade the ldap module to version 1.1.0. */
class Ldap_Upgrade_1_1_0 extends MIDASUpgrade
{
    /** @var string */
    public $moduleName = 'ldap';

    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $configPath = LOCAL_CONFIGS_PATH.DIRECTORY_SEPARATOR.$this->moduleName.'.local.ini';

        if (file_exists($configPath)) {
            $config = new Zend_Config_Ini($configPath, 'global');
            $hostName = isset($config->ldap->hostname) ? $config->ldap->hostname : LDAP_HOST_NAME_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_HOST_NAME_KEY, $hostName, $this->moduleName);
            $port = isset($config->ldap->port) ? $config->ldap->port : LDAP_PORT_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_PORT_KEY, $port, $this->moduleName);
            $backupServer = isset($config->ldap->backup) ? $config->ldap->backup : LDAP_BACKUP_SERVER_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_BACKUP_SERVER_KEY, $backupServer, $this->moduleName);
            $bindRdn = isset($config->ldap->bindn) ? $config->ldap->bindn : LDAP_BIND_RDN_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_BIND_RDN_KEY, $bindRdn, $this->moduleName);
            $bindPassword = isset($config->ldap->bindpw) ? $config->ldap->bindpw : LDAP_BIND_PASSWORD_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_BIND_PASSWORD_KEY, $bindPassword, $this->moduleName);
            $baseDn = isset($config->ldap->basedn) ? $config->ldap->basedn : LDAP_BASE_DN_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_BASE_DN_KEY, $baseDn, $this->moduleName);
            $protocolVersion = isset($config->ldap->protocolVersion) ? $config->ldap->protocolVersion : LDAP_PROTOCOL_VERSION_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_PROTOCOL_VERSION_KEY, $protocolVersion, $this->moduleName);
            $searchTerm = isset($config->ldap->search) ? $config->ldap->search : LDAP_SEARCH_TERM_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_SEARCH_TERM_KEY, $searchTerm, $this->moduleName);
            $proxyBaseDn = isset($config->ldap->proxyBasedn) ? $config->ldap->proxyBasedn : LDAP_PROXY_BASE_DN_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_PROXY_BASE_DN_KEY, $proxyBaseDn, $this->moduleName);
            $proxyPassword = isset($config->ldap->proxyPassword) ? $config->ldap->proxyPassword : LDAP_PROXY_PASSWORD_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_PROXY_PASSWORD_KEY, $proxyPassword, $this->moduleName);
            $useActiveDirectory = isset($config->ldap->useActiveDirectory) ? $config->ldap->useActiveDirectory : LDAP_USE_ACTIVE_DIRECTORY_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_USE_ACTIVE_DIRECTORY_KEY, $useActiveDirectory, $this->moduleName);
            $autoAddUnknownUser = isset($config->ldap->autoAddUnknownUser) ? $config->ldap->autoAddUnknownUser : LDAP_AUTO_ADD_UNKNOWN_USER_DEFAULT_VALUE;
            $settingModel->setConfig(LDAP_AUTO_ADD_UNKNOWN_USER_KEY, $autoAddUnknownUser, $this->moduleName);

            $config = new Zend_Config_Ini($configPath, null, true);
            unset($config->global->ldap->hostname);
            unset($config->global->ldap->port);
            unset($config->global->ldap->backup);
            unset($config->global->ldap->bindn);
            unset($config->global->ldap->bindpw);
            unset($config->global->ldap->basedn);
            unset($config->global->ldap->protocolVersion);
            unset($config->global->ldap->search);
            unset($config->global->ldap->proxyBasedn);
            unset($config->global->ldap->proxyPassword);
            unset($config->global->ldap->useActiveDirectory);
            unset($config->global->ldap->autoAddUnknownUser);

            $writer = new Zend_Config_Writer_Ini();
            $writer->setConfig($config);
            $writer->setFilename($configPath);
            $writer->write();
        } else {
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
}
