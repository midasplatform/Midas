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

  /**
   * Helper method to extract tokens from request URI's in path form,
   * e.g. download/folder/123/folder_name, starting after the action name.
   * Returns the token as a list.
   */
  public static function extractPathParams()
    {
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $allTokens = preg_split('@/@', $request->getPathInfo(), null, PREG_SPLIT_NO_EMPTY);

    $tokens = array();
    $i = 0;
    if($request->getModuleName() != 'default')
      {
      $i++;
      }
    if($request->getControllerName() != 'index')
      {
      $i++;
      }
    if($request->getActionName() != 'index')
      {
      $i++;
      }
    $max = count($allTokens);
    for(; $i < $max; $i++)
      {
      $tokens[] = $allTokens[$i];
      }
    return $tokens;
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
          $config->db->PDO_PGSQL = true;
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
                  case 'mysql':
                    $config->db->PDO_MYSQL = true;
                    break;
                  case 'pgsql':
                    $config->db->PDO_PGSQL = true;
                    break;
                  case 'ibm':
                    $config->db->PDO_IBM = true;
                    break;
                  case 'oci':
                    $config->db->PDO_OCI = true;
                    break;
                  case 'sqlite':
                    $config->db->PDO_SQLITE = true;
                    break;
                  case 'cassandra':
                    $config->db->CASSANDRA = true;
                    break;
                  case 'mongo':
                    $config->db->MONGO = true;
                    break;
                  default:
                    break;
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
  public static function sliceName($name, $nchar)
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
  public static function createInitFile($path, $data)
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
      throw new Zend_Exception("Error in parameter: data, it should be a non-empty array");
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
  public static function md5file($filename)
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
   * Get size in bytes of the file. This also supports files over 2GB in Windows,
   * which is not supported by PHP's filesize()
   * @param path Path of the file to check
   */
  public static function fileSize($path)
    {
    if(strpos(strtolower(PHP_OS), 'win') === 0)
      {
      $filesystem = new COM('Scripting.FileSystemObject');
      $file = $filesystem->GetFile($path);
      return $file->Size();
      }
    else
      {
      return filesize($path);
      }
    }

  /**
   * Format file size. Rounds to 1 decimal place and makes sure
   * to use 3 or less digits before the decimal place.
   */
  public static function formatSize($sizeInBytes, $separator = ',')
    {
    $suffix = 'B';
    if(Zend_Registry::get('configGlobal')->application->lang == 'fr')
      {
      $suffix = 'o';
      }
    if($sizeInBytes >= 1073741824000)
      {
      $sizeInBytes = number_format($sizeInBytes / 1099511627776, 1, '.', $separator);
      return $sizeInBytes.' T'.$suffix;
      }
    else if($sizeInBytes >= 1048576000)
      {
      $sizeInBytes = number_format($sizeInBytes / 1073741824, 1, '.', $separator);
      return $sizeInBytes.' G'.$suffix;
      }
    else if($sizeInBytes >= 1024000)
      {
      $sizeInBytes = number_format($sizeInBytes / 1048576, 1, '.', $separator);
      return $sizeInBytes.' M'.$suffix;
      }
    else
      {
      $sizeInBytes = number_format($sizeInBytes / 1024, 1, '.', $separator);
      return $sizeInBytes.' K'.$suffix;
      }
    }

  /** Safe delete function. Checks if the file can be deleted. */
  public static function safedelete($filename)
    {
    if(!file_exists($filename))
      {
      return false;
      }
    unlink($filename);
    }

  /** Function to run a SQL script */
  static function run_sql_from_file($db, $sqlfile)
    {
    try
      {
      $db->getConnection();
      }
    catch(Zend_Exception $exception)
      {
      throw new Zend_Exception("Unable to connect.");
      }

    $sql = '';
    $lines = file($sqlfile);
    foreach($lines as $line)
      {
      if(trim($line) != '' && substr(trim($line), 0, 2) != '--' && substr($line, 0, 1) != '#')
        {
        $sql .= $line;
        }
      }
    $queries = explode(';', $sql);
    foreach($queries as $query)
      {
      try
        {
        $db->query($query);
        }
      catch(Zend_Exception $exception)
        {
        if(trim($query) != '')
          {
          throw new Zend_Exception("Unable to connect.");
          }
        }
      }
    return true;
    }

  /** Get the data directory */
  public static function getDataDirectory($subdir = '')
    {
    $settingModel = MidasLoader::loadModel('Setting');
    try
      {
      $dataDirectory = $settingModel->getValueByName('data_directory');
      }
    catch(Exception $e)
      {
      $dataDirectory = null;
      }
    if(!isset($dataDirectory) || empty($dataDirectory))
      {
      if(getenv('midas_data_path') !== false)
        {
        $dataDirectory = getenv('midas_data_path');
        }
      else
        {
        $dataDirectory = BASE_PATH.'/data';
        }
      }
    if($subdir == '')
      {
      $dataDirectory .= '/';
      }
    else
      {
      $dataDirectory .= '/'.$subdir.'/';
      }
    return $dataDirectory;
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
    $settingModel = MidasLoader::loadModel('Setting');
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
      if(getenv('midas_temp_path') !== false)
        {
        $tempDirectory = getenv('midas_temp_path');
        }
      else
        {
        $tempDirectory = BASE_PATH.'/tmp';
        }
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
      $configDatabase = Zend_Registry::get('configDatabase');
      if(empty($configDatabase->database->params->driver_options))
        {
        $driverOptions = array();
        }
      else
        {
        $driverOptions = $configDatabase->database->params->driver_options->toArray();
        }
      $params = array(
        'dbname' => $configDatabase->database->params->dbname,
        'username' => $configDatabase->database->params->username,
        'password' => $configDatabase->database->params->password,
        'driver_options' => $driverOptions);
      if(empty($configDatabase->database->params->unix_socket))
        {
        $params['host'] = $configDatabase->database->params->host;
        $params['port'] = $configDatabase->database->params->port;
        }
      else
        {
        $params['unix_socket'] = $configDatabase->database->params->unix_socket;
        }
      $db = Zend_Db::factory($configDatabase->database->adapter, $params);

      switch($configDatabase->database->adapter)
        {
        case 'PDO_MYSQL':
          if(file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$version.'.sql'))
            {
            $this->run_sql_from_file($db, BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$version.'.sql');
            }
          break;
        case 'PDO_PGSQL':
          if(file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/pgsql/'.$version.'.sql'))
            {
            $this->run_sql_from_file($db, BASE_PATH.'/modules/'.$moduleName.'/database/pgsql/'.$version.'.sql');
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

  /**
   * Will remove all "unsafe" html tags from the text provided.
   * @param text The text to filter
   * @return The text stripped of all unsafe tags
   */
  public static function filterHtmlTags($text)
    {
    $allowedTags = array('a', 'b', 'br', 'i', 'p', 'strong', 'table', 'thead',
      'tbody', 'th', 'tr', 'td', 'ul', 'ol', 'li', 'style', 'div', 'span');
    $allowedAttributes = array('href', 'class', 'style', 'type', 'target');
    $stripTags = new Zend_Filter_StripTags($allowedTags, $allowedAttributes);
    return $stripTags->filter($text);
    }

  /**
   * Convert a body of text from markdown to html.
   * @param text The text to markdown
   * @return The markdown rendered as HTML
   */
  public static function markDown($text)
    {
    require_once BASE_PATH.'/library/Markdown/markdown.php';
    return Markdown($text);
    }

  /**
   * INTERNAL FUNCTION
   * This is used to suppress warnings from being written to the output and the
   * error log. Users should not call this function; see beginIgnoreWarnings().
   */
  static function ignoreErrorHandler($errno, $errstr, $errfile, $errline)
    {
    return true;
    }

  /**
   * Normally, PHP warnings are echoed by our default error handler.  If you expect them to happen
   * from, for instance, an underlying library, but want to eat them instead of echoing them, wrap
   * the offending lines in beginIgnoreWarnings() and endIgnoreWarnings()
   */
  public static function beginIgnoreWarnings()
    {
    set_error_handler('UtilityComponent::ignoreErrorHandler'); //must not print and log warnings
    }

  /**
   * See documentation of UtilityComponent::beginIgnoreWarnings().
   * Calling this restores the normal warning handler.
   */
  public static function endIgnoreWarnings()
    {
    restore_error_handler();
    }

  /** Recursively delete a directory on disk */
  public static function rrmdir($dir)
    {
    if(!file_exists($dir))
      {
      return;
      }
    if(is_dir($dir))
      {
      $objects = scandir($dir);
      }

    foreach($objects as $object)
      {
      if($object != '.' && $object != '..')
        {
        if(filetype($dir.'/'.$object) == 'dir')
          {
          self::rrmdir($dir.'/'.$object);
          }
        else
          {
          unlink($dir.'/'.$object);
          }
        }
      }
    reset($objects);
    rmdir($dir);
    }

  /**
   * Send an email.  This wraps the mail() function and adds our default headers and puts the
   * text into a template.
   */
  public static function sendEmail($email, $subject, $text)
    {
    $headers = "From: Midas\nReply-To: no-reply\nX-Mailer: PHP/".phpversion()."\nMIME-Version: 1.0\nContent-type: text/html; charset = UTF-8";
    $text .= '<br/><br/>--<br/>This is an auto-generated message from the Midas system. Please do not reply to this email.';

    if(Zend_Registry::get('configGlobal')->environment == 'testing' || mail($email, $subject, $text, $headers))
      {
      self::getLogger()->info('Sent email to '.$email.' with subject '.$subject);
      }
    else
      {
      self::getLogger()->crit('Error sending email to '.$email.' with subject '.$subject);
      }
    }

  /**
   * Get the hostname for this instance
   */
  public static function getServerURL()
    {
    if(Zend_Registry::get('configGlobal')->environment == 'testing')
      {
      return 'http://localhost';
      }
    $currentPort = "";
    $prefix = "http://";

    if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
      {
      $currentPort = ":".$_SERVER['SERVER_PORT'];
      }
    if($_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])))
      {
      $prefix = "https://";
      }
    return $prefix.$_SERVER['SERVER_NAME'].$currentPort;
    }

  /**
   * Generate a string of random characters. Seeds RNG within the function using microtime.
   * @param $length The length of the random string
   * @param $alphabet (Optional) The alphabet string; if none provided, uses [a-zA-z0-9]
   */
  public static function generateRandomString($length, $alphabet = null)
    {
    if(!is_string($alphabet) || empty($alphabet))
      {
      $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      }

    // Seed RNG with microtime (for lack of something more difficult to guess)
    list($usec, $sec) = explode(' ', microtime());
    srand((float) $sec + ((float) $usec * 100000));

    $salt = '';
    $max = strlen($alphabet) - 1;
    for($i = 0; $i < $length; $i++)
      {
      $salt .= substr($alphabet, rand(0, $max), 1);
      }
    return $salt;
    }

  /**
   * Allows the current PHP process to use unlimited memory
   */
  public static function disableMemoryLimit()
    {
    ini_set('memory_limit', '-1');
    }

  /**
   * Test whether the specified port is listening on the specified host.
   * Return true if the connection is accepted, false otherwise.
   * @param port The port to test (integer)
   * @param [host] The hostname; default is localhost
   */
  public static function isPortListening($port, $host = 'localhost')
    {
    UtilityComponent::beginIgnoreWarnings();
    $conn = fsockopen($host, $port);
    UtilityComponent::endIgnoreWarnings();

    if(is_resource($conn))
      {
      fclose($conn);
      return true;
      }
    return false;
    }
  
  /** Limits the maximum execution time. */
  public static function setTimeLimit($seconds)
    {
    UtilityComponent::beginIgnoreWarnings();
    set_time_limit($seconds);
    UtilityComponent::endIgnoreWarnings();
    }
  }
