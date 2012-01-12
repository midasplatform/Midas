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

/** notification manager*/
class Ldap_Notification extends MIDAS_Notification
  {
  public $_models=array('User');
  
  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDasboard');
    $this->addCallBack("CALLBACK_CORE_AUTHENTIFICATION", 'ldapLogin');
    }//end init  

    
  /** generate Dasboard information */
  public function getDasboard()
    {    
    $config = Zend_Registry::get('configsModules');
    $baseDn =  $config['ldap']->ldap->basedn;
    $hostname =  $config['ldap']->ldap->hostname;
    $proxybasedn = $config['ldap']->ldap->proxyBasedn;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    $backupServer = $config['ldap']->ldap->backup;
    $bindn = $config['ldap']->ldap->bindn;
    $bindpw = $config['ldap']->ldap->bindpw;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    
    $ldap = ldap_connect($hostname);
    
    $server = false;
    $backup = false;
    
     if(isset($ldap) && $ldap != '')
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
        $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
        if($ldapbind != false)
          {
          $backup = true;
          }
        }
      }
    
    $return = array();
    $return['LDAP Server'] = $server; 
    if(!empty($backup))
      {
      $return['LDAP Backup Server'] = $backup;
      }

    return $return;
    }//end _getDasboard
    
  /** login using ldap*/
  public function ldapLogin($params)
    {
    if(!isset($params['email']) || !isset($params['password']))
      {
      throw new Zend_Exception('Error parameters');
      }
      
    $email = $params['email'];
    $password = $params['password'];
    
    $config = Zend_Registry::get('configsModules');
    $baseDn =  $config['ldap']->ldap->basedn;
    $hostname =  $config['ldap']->ldap->hostname;
    $protocolVersion =  $config['ldap']->ldap->protocolVersion;
    $credential =  $password;
    $autoAddUnknownUser =  $config['ldap']->ldap->autoAddUnknownUser;
    $searchTerm =  $config['ldap']->ldap->search;
    $useActiveDirectory = $config['ldap']->ldap->useActiveDirectory;
    $proxybasedn = $config['ldap']->ldap->proxyBasedn;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    $backup = $config['ldap']->ldap->backup;
    $bindn = $config['ldap']->ldap->bindn;
    $bindpw = $config['ldap']->ldap->bindpw;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;
    $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
    
    if($searchTerm == 'uid')
      {
      $ldapsearch = 'uid='.substr($email,0,strpos($email,'@'));
      }
    else
      {
      $ldapsearch = $searchTerm.'='.$email;
      }
      
    $ldap = ldap_connect($hostname);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
    if($useActiveDirectory)
      {
      ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
      }
      

    if(isset($ldap) && $ldap != '')
      {
      if($proxybasedn != '')
        {
        $proxybind = ldap_bind($ldap, $proxybasedn, $proxyPassword);
        if(!$proxybind)
          {
          throw new Zend_Exception('Cannot bind proxy');
          }
        }
        
      $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
      if(!$ldapbind)
        {
        $ldap = ldap_connect($backup);
        $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
        }

      /* search for pid dn */
      $result = ldap_search($ldap, $baseDn, $ldapsearch, array("uid",'cn'));
      $someone = false;
      if($result != 0)
        {
        $entries = ldap_get_entries($ldap, $result);
        
        if($entries['count']!=0)
          {
          $principal = $entries[0]['dn'];
          }
        if(isset($principal))
          {
          /* bind as this user */
          if(@ldap_bind($ldap, $principal, $credential))
            {            
            // Try to find the user in the MIDAS database
            $someone = $this->User->getByEmail($email);
            // If the user doesn't exist we add it, but without email
            if(!$someone && $autoAddUnknownUser)
              {
              $user = array();
              @$givenname = $entries[0]['cn'][0];
              if(!isset($givenname))
                {
                throw new Zend_Exception('No givenname (cn) set in LDAP, cannot register user into MIDAS');
                }

              $names = explode(" ", $givenname);
              $firstname = ' ';
              if(count($names)>1)
                {
                $firstname = $names[0];
                $lastname = $names[1];
                for($i=2;$i<count($names);$i++)
                  {
                  $lastname .= " ".$names[$i];
                  }
                }
              else
                {
                $lastname = $names[0];
                }
              $someone = $this->User->createUser($email, $password , $firstname, $lastname);
              }
            else if($someone->getPassword() != md5($passwordPrefix.$password))
              {
              $someone->setPassword(md5($passwordPrefix.$password));              
              $this->User->save($someone);
              }
            }
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
  } //end class
?>