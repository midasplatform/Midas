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

/** notification manager */
class Ldap_Notification extends MIDAS_Notification
  {
  public $_models = array('User');
  public $_moduleModels = array('User');
  public $moduleName = 'ldap';

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    $this->addCallBack('CALLBACK_CORE_AUTHENTICATION', 'ldapLogin');
    $this->addCallBack('CALLBACK_CORE_CHECK_USER_EXISTS', 'userExists');
    $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
    }//end init


  /** generate admin Dashboard information */
  public function getDashboard()
    {
    $config = Zend_Registry::get('configsModules');
    $baseDn = $config['ldap']->ldap->basedn;
    $hostname = $config['ldap']->ldap->hostname;
    $port = (int)$config['ldap']->ldap->port;
    $proxybasedn = $config['ldap']->ldap->proxyBasedn;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    $protocolVersion = $config['ldap']->ldap->protocolVersion;
    $backupServer = $config['ldap']->ldap->backup;
    $bindn = $config['ldap']->ldap->bindn;
    $bindpw = $config['ldap']->ldap->bindpw;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;

    $ldap = ldap_connect($hostname, $port);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);

    $server = false;
    $backup = false;

     if(isset($ldap) && $ldap !== false)
      {
      if($proxybasedn != '')
        {
        $proxybind = ldap_bind($ldap, $proxybasedn, $proxyPassword);
        }

      $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
      if($ldapbind != false)
        {
        $server = true;
        }

      if(!empty($backupServer))
        {
        $ldap = ldap_connect($backupServer);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
        $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
        if($ldapbind != false)
          {
          $backup = true;
          }
        }
      }

    $return = array();
    $return['LDAP Server'] = array($server);
    if(!empty($backup))
      {
      $return['LDAP Backup Server'] = array($backup);
      }

    return $return;
    }//end _getDasboard

  /**
   * Look up whether the user exists in the ldap_user table
   * @return true or false
   */
  public function userExists($params)
    {
    $someone = $this->Ldap_User->getLdapUser($params['entry']);
    if($someone)
      {
      return true;
      }
    return false;
    }

  /** login using ldap instead of the normal mechanism */
  public function ldapLogin($params)
    {
    if(!isset($params['email']) || !isset($params['password']))
      {
      throw new Zend_Exception('Required parameter "email" or "password" missing');
      }

    $email = $params['email'];
    $password = $params['password'];

    $config = Zend_Registry::get('configsModules');
    $baseDn = $config['ldap']->ldap->basedn;
    $hostname = $config['ldap']->ldap->hostname;
    $port = (int)$config['ldap']->ldap->port;
    $protocolVersion = $config['ldap']->ldap->protocolVersion;
    $autoAddUnknownUser = $config['ldap']->ldap->autoAddUnknownUser;
    $searchTerm =  $config['ldap']->ldap->search;
    $useActiveDirectory = $config['ldap']->ldap->useActiveDirectory;
    $proxybasedn = $config['ldap']->ldap->proxyBasedn;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    $backup = $config['ldap']->ldap->backup;
    $bindn = $config['ldap']->ldap->bindn;
    $bindpw = $config['ldap']->ldap->bindpw;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    $passwordPrefix = Zend_Registry::get('configGlobal')->password->prefix;

    if($searchTerm == 'uid')
      {
      $atCharPos = strpos($email, '@');
      if($atCharPos === false)
        {
        $ldapsearch = 'uid='.$email;
        }
      else
        {
        $ldapsearch = 'uid='.substr($email, 0, $atCharPos);
        }
      }
    else
      {
      $ldapsearch = $searchTerm.'='.$email;
      }

    $ldap = ldap_connect($hostname, $port);

    if($ldap !== false)
      {
      ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
      if($useActiveDirectory == 'true')
        {
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        }
      if($proxybasedn != '')
        {
        $proxybind = ldap_bind($ldap, $proxybasedn, $proxyPassword);
        if(!$proxybind)
          {
          throw new Zend_Exception('Cannot bind proxy');
          }
        }

      $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
      if(!$ldapbind && $backup)
        {
        $ldap = ldap_connect($backup);
        $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
        }

      // do an ldap search for the specified user
      $result = ldap_search($ldap, $baseDn, $ldapsearch, array('uid', 'cn', 'mail'));
      $someone = false;
      if($result != 0)
        {
        $entries = ldap_get_entries($ldap, $result);

        if($entries['count'] != 0)
          {
          $principal = $entries[0]['dn'];
          }
        if(isset($principal))
          {
          // Bind as this user
          set_error_handler('Ldap_Notification::eatWarnings'); //must not print and log warnings
          if(@ldap_bind($ldap, $principal, $password))
            {
            // Try to find the user in the MIDAS database
            $someone = $this->Ldap_User->getLdapUser($email);
            if($someone)
              {
              // convert to core user dao
              $someone = $someone->getUser();
              }
            else if($autoAddUnknownUser)
              {
              // If the user doesn't exist we add it
              $user = array();
              $givenname = $entries[0]['cn'][0];
              if(!isset($givenname))
                {
                throw new Zend_Exception('No common name (cn) set in LDAP, cannot register user into Midas');
                }

              if($searchTerm == 'mail')
                {
                $ldapEmail = $email;
                }
              else
                {
                @$ldapEmail = $entries[0]['mail'][0]; //use ldap email listing for their actual email
                if(!isset($ldapEmail))
                  {
                  $ldapEmail = $email;
                  }
                }

              $names = explode(' ', $givenname);
              $firstname = ' ';
              if(count($names) > 1)
                {
                $firstname = $names[0];
                $lastname = $names[1];
                for($i = 2; $i < count($names); $i++)
                  {
                  $lastname .= ' '.$names[$i];
                  }
                }
              else
                {
                $lastname = $names[0];
                }
              $someone = $this->Ldap_User->createLdapUser($ldapEmail, $email, $password, $firstname, $lastname);
              $someone = $someone->getUser(); // convert to core user dao
              }
            }
          restore_error_handler();
          }
        ldap_free_result($result);
        }
      else
        {
        throw new Zend_Exception('Error occured searching the LDAP: '.ldap_error($ldap));
        }
      ldap_close($ldap);
      return $someone;
      }
    else
      {
      throw new Zend_Exception('Could not connect to LDAP at '.$hostname);
      }
    }//end ldaplogin

  /**
   * If a user is deleted, we must delete any corresponding ldap_user entries
   */
  public function handleUserDeleted($params)
    {
    $this->Ldap_User->deleteByUser($params['userDao']);
    }

  /**
   * This is used to suppress warnings from being written to the output and the
   * error log.  When searching, we don't want warnings to appear for invalid searches.
   */
  static function eatWarnings($errno, $errstr, $errfile, $errline)
    {
    return true;
    }
  } //end class
?>
