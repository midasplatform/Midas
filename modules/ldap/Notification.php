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
 * Notification manager for the ldap module
 *
 * @property Ldap_UserModel $Ldap_User
 */
class Ldap_Notification extends MIDAS_Notification
{
    public $_models = array('Setting', 'User');
    public $_moduleModels = array('User');
    public $moduleName = 'ldap';

    /** init notification process */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
        $this->addCallBack('CALLBACK_CORE_AUTHENTICATION', 'ldapLogin');
        $this->addCallBack('CALLBACK_CORE_CHECK_USER_EXISTS', 'userExists');
        $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
        $this->addCallBack('CALLBACK_CORE_RESET_PASSWORD', 'handleResetPassword');
        $this->addCallBack('CALLBACK_CORE_ALLOW_PASSWORD_CHANGE', 'allowPasswordChange');
        $this->addCallBack('CALLBACK_CORE_USER_PROFILE_FIELDS', 'getLdapLoginField');
        $this->addCallBack('CALLBACK_CORE_USER_SETTINGS_CHANGED', 'userSettingsChanged');
    }

    /**
     * Add an LDAP login field to the user profile form
     */
    public function getLdapLoginField($params)
    {
        if (!$this->userSession->Dao || !$this->userSession->Dao->isAdmin()) {
            return null;
        }
        $user = $params['user'];

        $field = array(
            'label' => 'LDAP Login',
            'name' => 'ldapLogin',
            'type' => 'text',
            'position' => 'top',
            'value' => '',
        );
        $ldapUser = $this->Ldap_User->getByUser($user);
        if ($ldapUser) {
            $field['value'] = $ldapUser->getLogin();
        }

        return $field;
    }

    /**
     * Handle the LDAP login field from the user settings form.  If it is set to the empty string,
     * deletes any existing ldap_user for the user. Otherwise will update or create an ldap_user record
     * with the new value. The user will then use that on subsequent logins.
     *
     * @param fields The HTTP fields from the settings form
     * @param user The user dao being changed
     */
    public function userSettingsChanged($params)
    {
        $user = $params['user'];
        $fields = $params['fields'];

        if (!array_key_exists('ldapLogin', $fields)) {
            throw new Zend_Exception('LDAP Login parameter was not passed');
        }
        $ldapLogin = $fields['ldapLogin'];

        $ldapUser = $this->Ldap_User->getByUser($user);
        if ($ldapUser) {
            if (empty($ldapLogin)) {
                $this->Ldap_User->delete($ldapUser);
            } else {
                $ldapUser->setLogin($ldapLogin);
            }
        } elseif (!empty($ldapLogin)) {
            $ldapUserDao = MidasLoader::newDao('UserDao', 'ldap');
            $ldapUserDao->setUserId($user->getKey());
            $ldapUserDao->setLogin($ldapLogin);
            $this->Ldap_User->save($ldapUserDao);

            $user->setSalt('x'); // set an invalid salt so normal authentication won't work
            $this->User->save($user);
        }
    }

    /** generate admin Dashboard information */
    public function getDashboard()
    {
        $hostName = $this->Setting->getValueByName(LDAP_HOST_NAME_KEY, $this->moduleName);
        $port = (int) $this->Setting->getValueByName(LDAP_PORT_KEY, $this->moduleName);
        $proxyBaseDn = $this->Setting->getValueByName(LDAP_PROXY_BASE_DN_KEY, $this->moduleName);
        $protocolVersion = $this->Setting->getValueByName(LDAP_PROTOCOL_VERSION_KEY, $this->moduleName);
        $backupServer = $this->Setting->getValueByName(LDAP_BACKUP_SERVER_KEY, $this->moduleName);
        $bindRdn = $this->Setting->getValueByName(LDAP_BIND_RDN_KEY, $this->moduleName);
        $bindPassword = $this->Setting->getValueByName(LDAP_BIND_PASSWORD_KEY, $this->moduleName);
        $proxyPassword = $this->Setting->getValueByName(LDAP_PROXY_PASSWORD_KEY, $this->moduleName);

        $ldap = ldap_connect($hostName, $port);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);

        $server = false;
        $backup = false;

        if (isset($ldap) && $ldap !== false) {
            if ($proxyBaseDn != '') {
                ldap_bind($ldap, $proxyBaseDn, $proxyPassword);
            }

            $ldapBind = ldap_bind($ldap, $bindRdn, $bindPassword);
            if ($ldapBind != false) {
                $server = true;
            }

            if (!empty($backupServer)) {
                $ldap = ldap_connect($backupServer);
                ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
                $ldapBind = ldap_bind($ldap, $bindRdn, $bindPassword);
                if ($ldapBind != false) {
                    $backup = true;
                }
            }
        }

        $return = array();
        $return['LDAP Server'] = array($server);
        if (!empty($backup)) {
            $return['LDAP Backup Server'] = array($backup);
        }

        return $return;
    }

    /**
     * Look up whether the user exists in the ldap_user table
     *
     * @return true or false
     */
    public function userExists($params)
    {
        $someone = $this->Ldap_User->getLdapUser($params['entry']);
        if ($someone) {
            return true;
        }

        return false;
    }

    /** login using ldap instead of the normal mechanism */
    public function ldapLogin($params)
    {
        if (!isset($params['email']) || !isset($params['password'])) {
            throw new Zend_Exception('Required parameter "email" or "password" missing');
        }

        $email = $params['email'];
        $password = $params['password'];

        $hostName = $this->Setting->getValueByName(LDAP_HOST_NAME_KEY, $this->moduleName);
        $port = (int) $this->Setting->getValueByName(LDAP_PORT_KEY, $this->moduleName);
        $proxyBaseDn = $this->Setting->getValueByName(LDAP_PROXY_BASE_DN_KEY, $this->moduleName);
        $protocolVersion = $this->Setting->getValueByName(LDAP_PROTOCOL_VERSION_KEY, $this->moduleName);
        $backupServer = $this->Setting->getValueByName(LDAP_BACKUP_SERVER_KEY, $this->moduleName);
        $bindRdn = $this->Setting->getValueByName(LDAP_BIND_RDN_KEY, $this->moduleName);
        $bindPassword = $this->Setting->getValueByName(LDAP_BIND_PASSWORD_KEY, $this->moduleName);
        $proxyPassword = $this->Setting->getValueByName(LDAP_PROXY_PASSWORD_KEY, $this->moduleName);
        $baseDn = $this->Setting->getValueByName(LDAP_BASE_DN_KEY, $this->moduleName);
        $autoAddUnknownUser = $this->Setting->getValueByName(LDAP_AUTO_ADD_UNKNOWN_USER_KEY, $this->moduleName);
        $searchTerm = $this->Setting->getValueByName(LDAP_SEARCH_TERM_KEY, $this->moduleName);
        $useActiveDirectory = $this->Setting->getValueByName(LDAP_USE_ACTIVE_DIRECTORY_KEY, $this->moduleName);

        if ($searchTerm == 'uid') {
            $atCharPos = strpos($email, '@');
            if ($atCharPos === false) {
                $ldapSearch = 'uid='.$email;
            } else {
                $ldapSearch = 'uid='.substr($email, 0, $atCharPos);
            }
        } else {
            $ldapSearch = $searchTerm.'='.$email;
        }

        $ldap = ldap_connect($hostName, $port);

        if ($ldap !== false) {
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
            if ($useActiveDirectory) {
                ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            }
            if ($proxyBaseDn != '') {
                $proxyBind = ldap_bind($ldap, $proxyBaseDn, $proxyPassword);
                if (!$proxyBind) {
                    throw new Zend_Exception('Cannot bind proxy');
                }
            }

            $ldapBind = ldap_bind($ldap, $bindRdn, $bindPassword);
            if (!$ldapBind && $backupServer) {
                $ldap = ldap_connect($backupServer);
                ldap_bind($ldap, $bindRdn, $bindPassword);
            }

            // do an ldap search for the specified user
            $result = ldap_search($ldap, $baseDn, $ldapSearch, array('uid', 'cn', 'mail'));
            $someone = false;
            if ($result != 0) {
                $entries = ldap_get_entries($ldap, $result);

                if ($entries['count'] != 0) {
                    $principal = $entries[0]['dn'];
                }
                if (isset($principal)) {
                    // Bind as this user
                    set_error_handler('Ldap_Notification::eatWarnings'); // must not print and log warnings
                    if (@ldap_bind($ldap, $principal, $password)) {
                        // Try to find the user in the MIDAS database
                        $someone = $this->Ldap_User->getLdapUser($email);
                        if ($someone) {
                            // convert to core user dao
                            $someone = $someone->getUser();
                        } elseif ($autoAddUnknownUser) {
                            // If the user doesn't exist we add it
                            $givenName = $entries[0]['cn'][0];
                            if (!isset($givenName)) {
                                throw new Zend_Exception(
                                    'No common name (cn) set in LDAP, cannot register user into Midas'
                                );
                            }

                            if ($searchTerm == 'mail') {
                                $ldapEmail = $email;
                            } else {
                                @$ldapEmail = $entries[0]['mail'][0]; // use ldap email listing for their actual email
                                if (!isset($ldapEmail)) {
                                    $ldapEmail = $email;
                                }
                            }

                            $names = explode(' ', $givenName);
                            $firstName = ' ';
                            $namesCount = count($names);
                            if ($namesCount > 1) {
                                $firstName = $names[0];
                                $lastName = $names[1];
                                for ($i = 2; $i < $namesCount; $i++) {
                                    $lastName .= ' '.$names[$i];
                                }
                            } else {
                                $lastName = $names[0];
                            }
                            $someone = $this->Ldap_User->createLdapUser(
                                $ldapEmail,
                                $email,
                                $password,
                                $firstName,
                                $lastName
                            );
                            $someone = $someone->getUser(); // convert to core user dao
                        }
                    }
                    restore_error_handler();
                }
                ldap_free_result($result);
            } else {
                throw new Zend_Exception('Error occured searching the LDAP: '.ldap_error($ldap));
            }
            ldap_close($ldap);

            return $someone;
        } else {
            throw new Zend_Exception('Could not connect to LDAP at '.$hostName);
        }
    }

    /**
     * If a user is deleted, we must delete any corresponding ldap_user entries
     */
    public function handleUserDeleted($params)
    {
        $this->Ldap_User->deleteByUser($params['userDao']);
    }

    /**
     * If a user requests a password reset and they are an ldap user, we have to
     * send them an alternate email telling them how they should actually reset
     * their password.
     */
    public function handleResetPassword($params)
    {
        $ldapUser = $this->Ldap_User->getByUser($params['user']);
        if ($ldapUser !== false) {
            $hostName = $this->Setting->getValueByName(LDAP_HOST_NAME_KEY, $this->moduleName);
            $email = $params['user']->getEmail();
            $subject = "Password Request";
            $body = "You have requested a new password for Midas Platform.<br/><br/>";
            $body .= "We could not fulfill this request because your user account is managed by an external LDAP server.<br/><br/>";
            $body .= "Please contact the administrator of the LDAP server at <b>".$hostName."</b> to have your password changed.";
			$result = Zend_Registry::get('notifier')->callback(
				'CALLBACK_CORE_SEND_MAIL_MESSAGE',
				array(
					'to' => $email,
					'subject' => $subject,
					'html' => $body,
					'event' => 'ldap_reset_password',
				)
			);
            if ($result) {
                return array('status' => true, 'message' => 'Password request sent.');
            }
        }
        return array('status' => false, 'message' => 'Could not send password request.');
    }

    /**
     * We must disable password changes for ldap users
     */
    public function allowPasswordChange($params)
    {
        $user = $params['user'];
        if ($this->Ldap_User->getByUser($user) !== false) {
            return array('allow' => false);
        }

        return array('allow' => true);
    }

    /**
     * This is used to suppress warnings from being written to the output and the
     * error log.  When searching, we don't want warnings to appear for invalid searches.
     */
    public static function eatWarnings($errno, $errstr, $errfile, $errline)
    {
        return true;
    }
}
