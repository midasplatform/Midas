<?php
/** notification manager*/
class Ldap_Notification extends MIDAS_Notification
  {
  public $_models=array('User');
  
  /** init notification process*/
  public function init($type, $params)
    {
    switch ($type)
      {
      case MIDAS_NOTIFY_LOGIN:
        $this->ldapLogin($params);
        break;

      default:
        break;
      }
    }//end init
    
  /** login using ldap*/
  private function ldapLogin($params)
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
    $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
    
    $ldapsearch = $searchTerm.'='.$email;

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

      /* search for pid dn */
      $result = ldap_search($ldap, $baseDn, $ldapsearch, array('dn','cn'));
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