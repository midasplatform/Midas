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

/** Utility componenet */
class UtilityComponent extends AppComponent
{

  /**
   * The main function for converting to an XML document.
   * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
   *
   * @param array $data
   * @param string $rootNodeName - what you want the root node to be - defaultsto data.
   * @param SimpleXMLElement $xml - should only be used recursively
   * @return string XML
   */
  public function toXml($data, $rootNodeName = 'data', $xml = null)
    {
    // turn off compatibility mode as simple xml throws a wobbly if you don't.
    if(ini_get('zend.ze1_compatibility_mode') == 1)
      {
      ini_set('zend.ze1_compatibility_mode', 0);
      }

    if($xml == null)
      {
      $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><".$rootNodeName." />");
      }

    // loop through the data passed in.
    foreach($data as $key => $value)
      {
      // no numeric keys in our xml please!
      if(is_numeric($key))
        {
        // make string key...
        $key = "unknownNode_". (string) $key;
        }

      // replace anything not alpha numeric
      $key = preg_replace('/[^a-z]/i', '', $key);

      // if there is another array found recrusively call this function
      if(is_array($value))
        {
        $node = $xml->addChild($key);
        // recrusive call.
        $this->toXml($value, $rootNodeName, $node);
        }
      else
        {
        // add single node.
        $value = htmlentities($value);
        $xml->addChild($key, $value);
        }
      }
    // pass back as string. or simple xml object if you want!
    return $xml->asXML();
    }
  /** Get all the modules */
  public function getAllModules()
    {
    $modules = array();
    if(file_exists(BASE_PATH.'/modules/') && opendir(BASE_PATH.'/modules/'))
      {
      $array = $this->_initModulesConfig(BASE_PATH.'/modules/');
      $modules = array_merge($modules, $array);
      }

    if(file_exists(BASE_PATH.'/privateModules/') && opendir(BASE_PATH.'/privateModules/'))
      {
      $array = $this->_initModulesConfig(BASE_PATH.'/privateModules/');
      $modules = array_merge($modules, $array);
      }

    return $modules;
    }

  /** find modules configuration in a folder */
  private function _initModulesConfig($dir)
    {
    $handle = opendir($dir);
    $modules = array();
    while(false !== ($file = readdir($handle)))
      {
      if(file_exists($dir.$file.'/configs/module.ini'))
        {
        $config = new Zend_Config_Ini($dir.$file.'/configs/module.ini', 'global', true);
        $config->db = array();
        if(!file_exists($dir.$file.'/database'))
          {
          $config->db->PDO_MYSQL = true;
          $config->db->PDO_IBM = true;
          $config->db->PDO_OCI = true;
          $config->db->PDO_SQLITE = true;
          $config->db->CASSANDRA = true;
          $config->db->MONGO = true;
          }
        else
          {
          $handleDB = opendir($dir.$file.'/database');
          if(file_exists($dir.$file.'/database'))
            {
            while(false !== ($fileDB = readdir($handleDB)))
              {
              if(file_exists($dir.$file.'/database/'.$fileDB.'/'))
                {
                switch($fileDB)
                  {
                  case 'mysql' : $config->db->PDO_MYSQL = true; break;
                  case 'pgsql' : $config->db->PDO_PGSQL = true;break;
                  case 'ibm' : $config->db->PDO_IBM = true;break;
                  case 'oci' : $config->db->PDO_OCI = true;break;
                  case 'sqlite' : $config->db->PDO_SQLITE = true;break;
                  case 'cassandra' : $config->db->CASSANDRA = true;break;
                  case 'mongo' : $config->db->MONGO = true;break;
                  default : break;
                  }
                }
              }
            }
          }
        $modules[$file] = $config;
        }
      }
    closedir($handle);
    return $modules;
    }

  /** format long names*/
  static public function sliceName($name, $nchar)
    {
    if(strlen($name) > $nchar)
      {
      $toremove = (strlen($name)) - $nchar;
      if($toremove < 8)
        {
        return $name;
        }
      $name = substr($name, 0, 5).'...'.substr($name, 8 + $toremove);
      return $name;
      }
    return $name;
    }

  /** create init file*/
  static public function createInitFile($path, $data)
    {
    if(!is_writable(dirname($path)))
      {
      throw new Zend_Exception("Unable to write in: ".dirname($path));
      }
    if(file_exists($path))
      {
      unlink($path);
      }

    if(!is_array($data) || empty($data))
      {
      throw new Zend_Exception("Error parameters");
      }
    $text = "";

    foreach($data as $delimiter => $d)
      {
      $text .= "[".$delimiter."]\n";
      foreach($d as $field => $value)
        {
        if($value == 'true' || $value == 'false')
          {
          $text .= $field."=".$value."\n";
          }
        else
          {
          $text .= $field."=\"".str_replace('"', "'", $value)."\"\n";
          }
        }
      $text .= "\n\n";
      }
    $fp = fopen($path, "w");
    fwrite($fp, $text);
    fclose($fp);
    return $text;
    }
  /** PHP md5_file is very slow on large file. If md5 sum is on the system we use it. */
  static public function md5file($filename)
    {
    // If we have md5 sum
    if(Zend_Registry::get('configGlobal')->md5sum->path)
      {
      $result = exec(Zend_Registry::get('configGlobal')->md5sum->path.' '.$filename);
      $resultarray = explode(' ', $result);
      return $resultarray[0];
      }
    return md5_file($filename);
    }


  /**
   * Check if the php function/extension are available
   *
   * $phpextensions should have the following format:
   *   array(
   *     "ExtensionOrFunctionName" => array( EXT_CRITICAL , $message or EXT_DEFAULT_MSG ),
   *   );
   *
   * The unavailable funtion/extension are returned (array of string)
   */
  static function checkPhpExtensions($phpextensions)
    {
    $phpextension_missing = array();
    foreach($phpextensions as $name => $param)
      {
      $is_loaded      = extension_loaded($name);
      $is_func_exists = function_exists($name);
      if(!$is_loaded && !$is_func_exists)
        {
        $is_critical = $param[0];
        $message = "<b>".$name."</b>: Unable to find '".$name."' php extension/function. ";
        $message .= ($param[1] === false ? "Fix the problem and re-run the install script." : $param[1]);
        if($is_critical)
          {
          throw  new Zend_Exception($message);
          }
        $phpextension_missing[$name] = $message;
        }
      }
    return $phpextension_missing;
    }


  /**
   * Check ifImageMagick is available.
   * Return an array of the form [Is_Ok, Message]
   *
   * Where Is_Ok is a boolean indicating ifImageMagick is operational
   * and where Message contains either:
   *    - ifIs_ok == true, the version of ImageMagick
   *    - If Is_Ok == false, details regarding the problem
   */
  static function isImageMagickWorking()
    {
    // ifcommand is successfull $ret shouldn't be empty
    exec('convert', $output, $returnvalue);
    if(count($output) == 0)
      {
      exec('im-convert', $output, $returnvalue);
      }
    if(count($output) > 0)
      {
      // version line should look like: "Version: ImageMagick 6.4.7 2008-12-04 Q16 http://www.imagemagick.org"
      list($version_line, $copyright_line) = $output;

      // split version by spaces
      $version_chunks = explode(" ", $version_line);

      // assume version is the third element
      $version = $version_chunks[2];

      // get major, minor and patch number
      list($major, $minor, $patch) = explode(".", $version);

      if($major < 6)
        {
        $text = "<b>ImageMagick</b> (".$version.") is found. Version (>=6.0) is required. Please install imagemagick from http://www.imagemagick.org";
        return array(false, $text);
        }
      return array(true, "ImageMagick (".$version.") found");
      }
    $text = "<b>ImageMagick</b> (>=6.0) is not found. Please install imagemagick from http://www.imagemagick.org";
    return array(false, $text);
    }
  /** format file size*/
  static public function formatSize($sizeInByte)
    {
    $dataNorme = 'B';
    if(Zend_Registry::get('configGlobal')->application->lang == 'fr')
      {
      $dataNorme = 'o';
      }
    if(strlen($sizeInByte) <= 9 && strlen($sizeInByte) >= 7)
      {
      $sizeInByte = number_format($sizeInByte / 1048576, 1);
      return $sizeInByte." M".$dataNorme;
      }
    elseif(strlen($sizeInByte) >= 10)
      {
      $sizeInByte = number_format($sizeInByte / 1073741824, 1);
      return $sizeInByte." G".$dataNorme;
      }
    else
      {
      $sizeInByte = number_format($sizeInByte / 1024, 1);
      return $sizeInByte." K".$dataNorme;
      }
    }

  /** Safe delete function. Checks ifthe file can be deleted. */
  static public function safedelete($filename)
    {
    if(!file_exists($filename))
      {
      return false;
      }
    unlink($filename);
    }

  /** Function to run the sql script */
  static function run_mysql_from_file($sqlfile, $host, $username, $password, $dbname, $port)
    {
    $db = mysql_connect($host.":".$port, $username, $password);
    $select = mysql_select_db($dbname, $db);
    if(!$db || !$select)
      {
      throw new Zend_Exception("Unable to connect.");
      }
    $requetes = "";

    $sql = file($sqlfile);
    foreach($sql as $l)
      {
      if(substr(trim($l), 0, 2) != "--")
        {
        $requetes .= $l;
        }
      }

    $reqs = explode(";", $requetes);
    foreach($reqs as $req)
      {// And they are executed
      if(!mysql_query($req, $db) && trim($req) != "")
        {
        throw new Zend_Exception("Unable to execute: ".$req );
        }
      }
    return true;
    }

  /** Function to run the sql script */
  static function run_pgsql_from_file($sqlfile, $host, $username, $password, $dbname, $port)
    {
    $pgdb = pg_connect("host = ".$host." port = ".$port." dbname = ".$dbname." user = ".$username." password = ".$password);
    $file_content = file($sqlfile);
    $query = "";
    $linnum = 0;
    foreach($file_content as $sql_line)
      {
      $tsl = trim($sql_line);
      if(($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#"))
        {
        $query .= $sql_line;
        if(preg_match("/;\s*$/", $sql_line))
          {
          $query = str_replace(";", "", "$query");
          $result = pg_query($query);
          if(!$result)
            {
            echo "Error line:".$linnum."<br>";
            return pg_last_error();
            }
          $query = "";
          }
        }
      $linnum++;
      } // end for each line
    return true;
    }

  /**
   * @method public getTempDirectory()
   * @param $subdir
   * get the midas temporary directory, appending the param $subdir, which
   * defaults to "misc"
   * @return string
   */
  public static function getTempDirectory($subdir = "misc")
    {
    $modelLoader = new MIDAS_ModelLoader();
    $settingModel = $modelLoader->loadModel('Setting');
    try
      {
      $tempDirectory = $settingModel->getValueByName('temp_directory');
      }
    catch(Exception $e)
      {
      // if the setting model hasn't been installed, or there is no
      // value in the settings table for this, provide a default
      $tempDirectory = null;
      }
    if(!isset($tempDirectory) || empty($tempDirectory))
      {
      $tempDirectory = BASE_PATH.'/tmp';
      }
    return $tempDirectory .'/'.$subdir.'/';
    }

  /**
   * @method public getCacheDirectory()
   * get the midas cache directory
   * @return string
   */
  public static function getCacheDirectory()
    {
    return self::getTempDirectory('cache');
    }


  /** install a module */
  public function installModule($moduleName)
    {
    // TODO, The module installation process needs some improvment.
    $allModules = $this->getAllModules();
    $version = $allModules[$moduleName]->version;

    $installScript = BASE_PATH.'/modules/'.$moduleName.'/database/InstallScript.php';
    $installScriptExists = file_exists($installScript);
    if($installScriptExists)
      {
      require_once BASE_PATH.'/core/models/MIDASModuleInstallScript.php';
      require_once $installScript;

      $classname = ucfirst($moduleName).'_InstallScript';
      if(!class_exists($classname, false))
        {
        throw new Zend_Exception('Could not find class "'.$classname.'" in file "'.$filename.'"');
        }

      $class = new $classname();
      $class->preInstall();
      }

    try
      {
      switch(Zend_Registry::get('configDatabase')->database->adapter)
        {
        case 'PDO_MYSQL':
          if(file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$version.'.sql'))
            {
            $this->run_mysql_from_file(BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$version.'.sql',
                                       Zend_Registry::get('configDatabase')->database->params->host,
                                       Zend_Registry::get('configDatabase')->database->params->username,
                                       Zend_Registry::get('configDatabase')->database->params->password,
                                       Zend_Registry::get('configDatabase')->database->params->dbname,
                                       Zend_Registry::get('configDatabase')->database->params->port);
            }
          break;
        case 'PDO_PGSQL':
          if(file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/pgsql/'.$version.'.sql'))
            {
            $this->run_pgsql_from_file(BASE_PATH.'/modules/'.$moduleName.'/database/pgsql/'.$version.'.sql',
                                       Zend_Registry::get('configDatabase')->database->params->host,
                                       Zend_Registry::get('configDatabase')->database->params->username,
                                       Zend_Registry::get('configDatabase')->database->params->password,
                                       Zend_Registry::get('configDatabase')->database->params->dbname,
                                       Zend_Registry::get('configDatabase')->database->params->port);
            }
          break;
        default:
          break;
        }
      }
    catch(Zend_Exception $exc)
      {
      $this->getLogger()->warn($exc->getMessage());
      }

    if($installScriptExists)
      {
      $class->postInstall();
      }

    require_once dirname(__FILE__).'/UpgradeComponent.php';
    $upgrade = new UpgradeComponent();
    $db = Zend_Registry::get('dbAdapter');
    $dbtype = Zend_Registry::get('configDatabase')->database->adapter;
    $upgrade->initUpgrade($moduleName, $db, $dbtype);
    $upgrade->upgrade($version);
    }
} // end class
