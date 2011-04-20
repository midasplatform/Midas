<?php
//App::import("Vendor",'Sanitize');
require_once BASE_PATH.'/modules/api/models/base/UserapiModelBase.php';

class Api_UserapiModel extends Api_UserapiModelBase
{
 // var $validate = array('applicationname' => array('rule'=>'/.+/', 'required'=>true));

  /** Get the application name of the key */
  function getApplicationName($keyid)
    {
    if(!is_numeric($keyid))
      {
      return false;
      }
    $applicationName = $this->query("SELECT application_name FROM midas_epersonapi WHERE epersonapi_id='$keyid'");
    if(count($applicationName)>0)
      {
      return $applicationName[0][0]["application_name"];
      }
    return false;
    }

  /** Get the creation date of the key */
  function getCreationDate($keyid)
    {
    if(!is_numeric($keyid))
      {
      return false;
      }
    $creation_date = $this->query("SELECT creation_date FROM midas_epersonapi WHERE epersonapi_id='$keyid'");
    if(count($creation_date)>0)
      {
      return $creation_date[0][0]["creation_date"];
      }
    return false;
    }

  /** Get the default token experiration time of the key */
  function getTokenExpirationTime($keyid)
    {
    if(!is_numeric($keyid))
      {
      return false;
      }
    $expirationtime = $this->query("SELECT token_expiration_time FROM midas_epersonapi WHERE epersonapi_id='$keyid'");
    if(count($expirationtime)>0)
      {
      return $expirationtime[0][0]["token_expiration_time"];
      }
    return false;
    }


  /** Get a userid from a  key */
  function getUserFromKey($keyid)
    {
    if(!is_numeric($keyid))
      {
      return false;
      }
    $expirationtime = $this->query("SELECT eperson_id FROM midas_epersonapi WHERE epersonapi_id='$keyid'");
    if(count($expirationtime)>0)
      {
      return $expirationtime[0][0]["eperson_id"];
      }
    return false;
    }

  // Generate a new UUID
  function generateUUID()
    {
    return uniqid() . md5(mt_rand());
    }

  // Retrieve UUID for the given id and resource type
  function getUUID($id, $type)
    {
    $query=$this->query("SELECT uuid FROM resource_uuid WHERE resource_type_id='$type' AND resource_id='$id'");
    if(empty($query))
      {
      return $this->assignUUID($id, $type);
      }
    else return $query[0][0]['uuid'];
    }

  // Retrieve resource info given a uuid
  function getResourceForUuid($uuid)
    {
    $resource = array();
    $query=$this->query("SELECT resource_type_id, resource_id FROM resource_uuid WHERE uuid='$uuid'");
    if(!empty($query))
      {
      $resource['type'] = $query[0][0]['resource_type_id'];
      $resource['id'] = $query[0][0]['resource_id'];
      }
    return $resource;
    }

  // Checks if a given resource has a uuid.  If not, creates uuid record and returns the generated uuid.
  // Returns empty string in an error condition (bad resource type/id)
  function assignUUID($id, $type)
    {
    $table;

    switch($type)
      {
      case MIDAS_RESOURCE_BITSTREAM:
        $table = 'bitstream';
        break;
      case MIDAS_RESOURCE_ITEM:
        $table = 'item';
        break;
      case MIDAS_RESOURCE_COLLECTION:
        $table = 'collection';
        break;
      case MIDAS_RESOURCE_COMMUNITY:
        $table = 'community';
        break;
      default:
        return '';
      }
      $id_column = $table."_id";
      //check if the resource with the given id exists
      $query = $this->query("SELECT $id_column FROM $table WHERE $id_column='$id'");
      if(empty($query))
        {
        return '';
        }
      else
        {
        $uuid = $this->generateUUID();
        $this->addUUID($id, $type, $uuid);
        return $uuid;
        }
    }

  function addUUID($id, $type, $uuid)
    {
    $this->query("INSERT INTO resource_uuid (resource_type_id, resource_id, uuid) VALUES ('$type', '$id', '$uuid')");
    }

   /** Create an API key from a login and password */
  function createKeyFromEmailPassword($appname,$email,$password)
    {
    if(!is_string($appname)||!is_string($email)||!is_string($password))
      {
      throw new Zend_Exception("Error parameter");
      }
      
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userModel=$this->ModelLoader->loadModel('User');

    // First check that the email and password are correct (ldap not supported for now)
    $userDao = $userModel->getByEmail($email);
    $passwordPrefix=Zend_Registry::get('configGlobal')->password->prefix;
    
    if($userDao == false || md5($passwordPrefix.$password) != $userDao->getPassword())
      {
      return false;
      }
      
    // Find if we already have an apikey
    $ret = $this->getByAppAndEmail($appname,$email);
    if($ret instanceof Api_UserapiDao)
      {
      return $ret->getApikey();
      }
    else
      {
      // Create the APIKey
      $tokenexperiationtime = '100';
      return $this->createKey($userDao,$appname,$tokenexperiationtime);
      }
    return false;
    } // end function createKeyFromEmailPassword
    
  /**
   * Get UserapiDao by
   * @param string $appname Application Name
   * @param string $email 
   * @return Api_UserapiDao 
   */
  function getByAppAndEmail($appname,$email)
    {
    if(!is_string($appname)||!is_string($email))
      {
      throw new Zend_Exception("Error parameter");
      }
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userModel=$this->ModelLoader->loadModel('User');
    $userDao = $userModel->getByEmail($email);
    if($userDao==false)
      {
      return false;
      }
    $row = $this->database->fetchRow($this->database->select()->where('application_name = ?', $appname)
                                                              ->where('user_id = ?', $userDao->getKey())); 
    $dao= $this->initDao('Userapi', $row,'api');
    return $dao;
    } // end getByApikey
    
  /**
   * Get UserapiDao by
   * @param string $appname Application Name
   * @param UserDao $userDao 
   * @return Api_UserapiDao 
   */
  function getByAppAndUser($appname,$userDao)
    {
    if(!is_string($appname)||!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Error parameter");
      }
    $row = $this->database->fetchRow($this->database->select()->where('application_name = ?', $appname)
                                                              ->where('user_id = ?', $userDao->getKey())); 
    $dao= $this->initDao('Userapi', $row,'api');
    return $dao;
    } // end getByAppAndUser

  
  /**
   * Return the tokendao
   * @param type $email
   * @param type $apikey
   * @param type $appname
   * @return Api_TokenDao 
   */
  function getToken($email,$apikey,$appname)
    {
    if(!is_string($appname)||!is_string($apikey)||!is_string($email))
      {
      throw new Zend_Exception("Error parameter");
      }
    // Check if we don't have already a token
    $this->ModelLoader = new MIDAS_ModelLoader();
    $userModel=$this->ModelLoader->loadModel('User');
    $userDao = $userModel->getByEmail($email);
    if(!$userDao)
      {
      return false;
      }
    $now = date("c");
    
    $sql=   $this->database->select()
                      ->setIntegrityCheck(false)
                      ->from(array('t' => 'api_token'))
                      ->join(array('u' => 'api_userapi'),
                         ' u.userapi_id= t.userapi_id',array() )
                      ->where('u.user_id = ?', $userDao->getKey())
                      ->where('u.application_name = ?', $appname)
                      ->where('t.expiration_date > ?', $now)
                      ->where('u.apikey > ?', $apikey) ;
    

    $row = $this->database->fetchRow($sql);
    $tokenDao= $this->initDao('Token', $row,'api');

    if(!empty($tokenDao))
      {
      return $tokenDao;
      }

    // We generate a token
    $keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $length = 40;

    // seed with microseconds
    function make_seed_recoverpass_token()
      {
      list($usec, $sec) = explode(' ', microtime());
      return (float) $sec + ((float) $usec * 100000);
      }
    srand(make_seed_recoverpass_token());

    $token = "";
    $max=strlen($keychars)-1;
    for ($i=0;$i<$length;$i++)
      {
      $token .= substr($keychars, rand(0, $max), 1);
      }

    // Find the api id
    
    $sql=   $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('u' => 'api_userapi'))
                  ->where('u.user_id = ?', $userDao->getKey())
                  ->where('u.application_name = ?', $appname)
                  ->where('u.apikey = ?', $apikey) ;

    $row = $this->database->fetchRow($sql);
    $userapiDao= $this->initDao('Userapi', $row,'api');  
    
    if(!$userapiDao)
      {
      return false;
      }

    $this->loadDaoClass('TokenDao','api');
    $tokenDao=new Api_TokenDao();
    $tokenDao->setUserapiId($userapiDao->getKey());
    $tokenDao->setToken($token);
    $tokenDao->setExpirationDate(date("c",time()+$userapiDao->getTokenExpirationTime()*60));

    $tokenModel=$this->ModelLoader->loadModel('Token','api');
        
    $tokenModel->save($tokenDao);
    
    // We do some cleanup of all the other keys that have expired
    $tokenModel->cleanExpired();

    return $tokenDao;
    } //get Token


  /** Return the userid from a token */
  function getUserapiFromToken($token)
    {
    if(!is_string($token))
      {
      throw new Zend_Exception("Error parameter");
      }
    $now = date("c");
    
    $sql=   $this->database->select()
                  ->setIntegrityCheck(false)
                  ->from(array('u' => 'api_userapi'))
                  ->join(array('t' => 'api_token'),
                     ' u.userapi_id= t.userapi_id',array() )
                  ->where('u.user_id = ?', $userDao->getKey())
                  ->where('t.expiration_date > ?', $now)
                  ->where('t.token > ?', $token) ;
    

    $row = $this->database->fetchRow($sql);
    return $this->initDao('Userapi', $row,'api');
    }

  /** Get the user's keys */
  function getUserKeys($userid)
    {
    $keyids = array();
    $ret = $this->query("SELECT epersonapi_id FROM midas_epersonapi WHERE eperson_id='$userid'");
    foreach($ret as $keyid)
      {
      $keyids[] = $keyid[0]['epersonapi_id'];
      }
    return $keyids;
    }


  /* Create a default web api key based on the user's email and password */
  function createDefaultKey($email, $password, $epersonid)
    {
    $query = $this->query("SELECT epersonapi_id FROM midas_epersonapi WHERE eperson_id='$epersonid' AND application_name='Default'");
    if(count($query)>0)
      {
      $this->query("DELETE FROM midas_epersonapi WHERE eperson_id='$epersonid' AND application_name='Default'");
      }
    $key = md5($email.$password.'Default');

    $now = date("Y-m-d H:i:s");
    $this->query("INSERT INTO midas_epersonapi (eperson_id,apikey,application_name,token_expiration_time,creation_date)
                  VALUES ('$epersonid','$key','Default','60','$now')");
    }

  /* Create default web api keys for all users */
  function createDefaultKeys()
    {
    App::import('Model','User');
    $User = new User();
    $userids = $User->getAll();

    foreach($userids as $userid)
      {
      $this->createDefaultKey($User->getEmail($userid), $User->getPassword($userid), $userid);
      }
    }

  /** Delete an API key */
  function deleteKey($apykeyid)
    {
    $this->query("DELETE FROM midas_epersonapi WHERE epersonapi_id='$apykeyid'");
    $this->query("DELETE FROM midas_apitoken WHERE epersonapi_id='$apykeyid'");
    return true;
    }

  function getCurrentSQLTime()
    {
    $nowstamp = $this->query("SELECT now() as currtime");
    return $nowstamp[0][0]['currtime'];
    }

  function getPathToRoot($uuid, $currList = array())
    {
    $resource = $this->getResourceForUuid($uuid);
    $currList[] = $uuid;

    switch($resource['type'])
      {
      case MIDAS_RESOURCE_BITSTREAM:
        App::Import('Model','Bitstream');
        $bitstream = new Bitstream();
        $parentId = $bitstream->getItemId($resource['id']);
        return $this->getPathToRoot($this->getUUID($parentId, MIDAS_RESOURCE_ITEM), $currList);
      case MIDAS_RESOURCE_ITEM:
        App::Import('Model','Item');
        $item = new Item();
        $parentId = $item->getOwningCollection($resource['id']);
        return $this->getPathToRoot($this->getUUID($parentId, MIDAS_RESOURCE_COLLECTION), $currList);
      case MIDAS_RESOURCE_COLLECTION:
        App::Import('Model','Collection');
        $collection = new Collection();
        $parentId = $collection->getMainParent($resource['id']);
        return $this->getPathToRoot($this->getUUID($parentId, MIDAS_RESOURCE_COMMUNITY), $currList);
      case MIDAS_RESOURCE_COMMUNITY:
        App::Import('Model','Community');
        $community = new Community();
        $parentId = $community->getParentCommunity($resource['id']);

        if($parentId == 0)
          {
          return $currList;
          }
        else
          {
          $parentUuid = $this->getUUID($parentId, MIDAS_RESOURCE_COMMUNITY);
          return $this->getPathToRoot($parentUuid, $currList);
          }
      default:
        return false;
      }
    }

  function convertPathToId($path)
    {
    $tokens = explode('/', $path);
    $type = MIDAS_RESOURCE_COMMUNITY;
    $id = 0;

    for($i = 0; $i < count($tokens); $i++)
      {
      $token = $tokens[$i];
      if($token == "")
        {
        // ignore slash as last character
        if($i == count($tokens) - 1) break;
        else continue;
        }

      switch($type)
        {
        case MIDAS_RESOURCE_COMMUNITY:
          if($id == 0)
            {
            $query = $this->query("SELECT community_id FROM community WHERE community.name='$token'");
            if(count($query) == 0)
              {
              return false;
              }
            $id = $query[0][0]["community_id"];
            $type = MIDAS_RESOURCE_COMMUNITY;
            }
          else
            {
            $query = $this->query("SELECT community_id FROM community WHERE community.name='$token' ".
              "AND community_id IN (SELECT child_comm_id FROM community2community WHERE parent_comm_id='$id')");

            if(count($query) > 0)
              {
              $id = $query[0][0]["community_id"];
              $type = MIDAS_RESOURCE_COMMUNITY;
              break;
              }

            $query = $this->query("SELECT collection_id FROM collection WHERE collection.name='$token' ".
              "AND collection_id IN (SELECT collection_id FROM community2collection WHERE community_id='$id')");

            if(count($query) > 0)
              {
              $id = $query[0][0]["collection_id"];
              $type = MIDAS_RESOURCE_COLLECTION;
              }
            else
              {
              return false;
              }
            }
          break;
        case MIDAS_RESOURCE_COLLECTION:

          $query = $this->query("SELECT item.item_id FROM item, metadatavalue WHERE metadatavalue.text_value='$token'".
              "AND item.item_id IN (SELECT item_id FROM collection2item WHERE collection_id='$id') ".
              "AND item.item_id=metadatavalue.item_id AND metadatavalue.metadata_field_id='64'");

          if(count($query) > 0)
            {
            $id = $query[0][0]["item_id"];
            $type = MIDAS_RESOURCE_ITEM;
            }
          else
            {
            return false;
            }
          break;
        case MIDAS_RESOURCE_ITEM:
          $query = $this->query("SELECT bitstream_id FROM bitstream WHERE bitstream.name='$token' ".
            "AND bitstream_id IN (SELECT bitstream_id FROM item2bitstream WHERE item_id='$id')");

          if(count($query) > 0)
            {
            $id = $query[0][0]["bitstream_id"];
            $type = MIDAS_RESOURCE_BITSTREAM;
            }
          else
            {
            return false;
            }
          break;
        case MIDAS_RESOURCE_BITSTREAM:
          // bad path, went beyond the bitstream level
          return false;
        default:
          break;
        }
      }

    return array('type'=>$type, 'id'=>$id, 'uuid'=>$this->getUUID($id, $type));
    }
}
?>
