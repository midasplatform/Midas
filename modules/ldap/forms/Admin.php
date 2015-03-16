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

/** Admin form for the ldap module. */
class Ldap_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('ldap_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('pZyCgKUmRGcsxVdNEtJLrAhW');
        $csrf->setDecorators(array('ViewHelper'));

        $hostName = new Zend_Form_Element_Text(LDAP_HOST_NAME_KEY);
        $hostName->setLabel('LDAP Server Name');
        $hostName->setRequired(true);
        $hostName->addValidator('NotEmpty', true);
        $hostName->addValidator('Hostname', true, array('allow' => Zend_Validate_Hostname::ALLOW_ALL, 'tld' => false));

        $port = new Zend_Form_Element_Text(LDAP_PORT_KEY);
        $port->setLabel('LDAP Server Port');
        $port->setRequired(true);
        $port->addValidator('NotEmpty', true);
        $port->addValidator('Digits', true);
        $port->addValidator('Between', true, array('min' => 1, 'max' => 65535));
        $port->setAttrib('maxlength', 5);

        $backupServer = new Zend_Form_Element_Text(LDAP_BACKUP_SERVER_KEY);
        $backupServer->setLabel('Backup Server Name');
        $backupServer->addValidator('NotEmpty', true);
        $backupServer->addValidator('Hostname', true, array('allow' => Zend_Validate_Hostname::ALLOW_ALL, 'tld' => false));

        $bindRdn = new Zend_Form_Element_Text(LDAP_BIND_RDN_KEY);
        $bindRdn->setLabel('Bind DN');
        $bindRdn->addValidator('NotEmpty', true);

        $bindPassword = new Zend_Form_Element_Password(LDAP_BIND_PASSWORD_KEY);
        $bindPassword->setLabel('Bind Password');
        $bindPassword->addValidator('NotEmpty', true);

        $baseDn = new Zend_Form_Element_Text(LDAP_BASE_DN_KEY);
        $baseDn->setLabel('Base DN');
        $baseDn->setRequired(true);
        $baseDn->addValidator('NotEmpty', true);

        $protocolVersion = new Zend_Form_Element_Text(LDAP_PROTOCOL_VERSION_KEY);
        $protocolVersion->setLabel('LDAP Protocol Version');
        $protocolVersion->setRequired(true);
        $protocolVersion->addValidator('NotEmpty', true);
        $protocolVersion->addValidator('Digits', true);
        $protocolVersion->addValidator('GreaterThan', true, array('min' => 1));
        $protocolVersion->setAttrib('maxlength', 1);

        $searchTerm = new Zend_Form_Element_Text(LDAP_SEARCH_TERM_KEY);
        $searchTerm->setLabel('Search Term');
        $searchTerm->setRequired(true);
        $searchTerm->addValidator('NotEmpty', true);

        $proxyBaseDn = new Zend_Form_Element_Text(LDAP_PROXY_BASE_DN_KEY);
        $proxyBaseDn->setLabel('Proxy Base DN');
        $proxyBaseDn->addValidator('NotEmpty', true);

        $proxyPassword = new Zend_Form_Element_Password(LDAP_PROXY_PASSWORD_KEY);
        $proxyPassword->setLabel('Proxy Password');
        $proxyPassword->addValidator('NotEmpty', true);

        $useActiveDirectory = new Zend_Form_Element_Checkbox(LDAP_USE_ACTIVE_DIRECTORY_KEY);
        $useActiveDirectory->setLabel('Use Active Directory');

        $autoAddUnknownUser = new Zend_Form_Element_Checkbox(LDAP_AUTO_ADD_UNKNOWN_USER_KEY);
        $autoAddUnknownUser->setLabel('Automatically Add Unknown Users');

        $this->addDisplayGroup(array($hostName, $port, $backupServer, $bindRdn, $bindPassword, $baseDn, $protocolVersion, $searchTerm, $proxyBaseDn, $proxyPassword, $useActiveDirectory, $autoAddUnknownUser), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $hostName, $port, $backupServer, $bindRdn, $bindPassword, $baseDn, $protocolVersion, $searchTerm, $proxyBaseDn, $proxyPassword, $useActiveDirectory, $autoAddUnknownUser, $submit));
    }
}
