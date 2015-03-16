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

define('LDAP_HOST_NAME_KEY', 'host_name');
define('LDAP_HOST_NAME_DEFAULT_VALUE', 'localhost');
define('LDAP_PORT_KEY', 'port');
define('LDAP_PORT_DEFAULT_VALUE', '389');
define('LDAP_BACKUP_SERVER_KEY', 'backup_server');
define('LDAP_BACKUP_SERVER_DEFAULT_VALUE', '');
define('LDAP_BIND_RDN_KEY', 'bind_rdn');
define('LDAP_BIND_RDN_DEFAULT_VALUE', 'cn=user,ou=people,dc=myorganization,dc=com');
define('LDAP_BIND_PASSWORD_KEY', 'bind_password');
define('LDAP_BIND_PASSWORD_DEFAULT_VALUE', '');
define('LDAP_BASE_DN_KEY', 'base_dn');
define('LDAP_BASE_DN_DEFAULT_VALUE', 'ou=people,dc=myorganization,dc=com');
define('LDAP_PROTOCOL_VERSION_KEY', 'protocol_version');
define('LDAP_PROTOCOL_VERSION_DEFAULT_VALUE', 3);
define('LDAP_SEARCH_TERM_KEY', 'search_term');
define('LDAP_SEARCH_TERM_DEFAULT_VALUE', 'uid');
define('LDAP_PROXY_BASE_DN_KEY', 'proxy_base_dn');
define('LDAP_PROXY_BASE_DN_DEFAULT_VALUE', '');
define('LDAP_PROXY_PASSWORD_KEY', 'proxy_password');
define('LDAP_PROXY_PASSWORD_DEFAULT_VALUE', '');
define('LDAP_USE_ACTIVE_DIRECTORY_KEY', 'use_active_directory');
define('LDAP_USE_ACTIVE_DIRECTORY_DEFAULT_VALUE', 0);
define('LDAP_AUTO_ADD_UNKNOWN_USER_KEY', 'auto_add_unknown_user');
define('LDAP_AUTO_ADD_UNKNOWN_USER_DEFAULT_VALUE', 1);
