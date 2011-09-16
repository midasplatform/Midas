<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/**
 *  Batchmake_KWBatchmakeComponent
 *  provides utility methods needed to interact with Batchmake via Midas3.
 */
class Batchmake_KWBatchmakeComponent extends AppComponent
{ 


  protected $configPropertiesRequirements = array(MIDAS_BATCHMAKE_TMP_DIR_PROPERTY => MIDAS_BATCHMAKE_CHECK_IF_CHMODABLE_RW,
  MIDAS_BATCHMAKE_BIN_DIR_PROPERTY => MIDAS_BATCHMAKE_CHECK_IF_READABLE, 
  MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY => MIDAS_BATCHMAKE_CHECK_IF_READABLE, 
  MIDAS_BATCHMAKE_APP_DIR_PROPERTY => MIDAS_BATCHMAKE_CHECK_IF_READABLE, 
  MIDAS_BATCHMAKE_DATA_DIR_PROPERTY => MIDAS_BATCHMAKE_CHECK_IF_CHMODABLE_RW,
  MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY => MIDAS_BATCHMAKE_CHECK_IF_READABLE);

  protected $applicationsPaths = array(MIDAS_BATCHMAKE_CONDOR_STATUS => MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY,
  MIDAS_BATCHMAKE_CONDOR_QUEUE => MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY,
  MIDAS_BATCHMAKE_CONDOR_SUBMIT => MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY,
  MIDAS_BATCHMAKE_CONDOR_SUBMIT_DAG => MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY,
  MIDAS_BATCHMAKE_EXE => MIDAS_BATCHMAKE_BIN_DIR_PROPERTY);




  /**
   * @method getConfigPropertiesRequirements()
   * accessor method for set of config properties and their requirements.
   */
  public function getConfigPropertiesRequirements()
    {
    return $this->configPropertiesRequirements;
    }

  /**
   * @method getApplicationsPaths()
   * accessor method for set of application paths needed by batchmake module.
   */
  public function getApplicationsPaths()
    {
    return $this->applicationsPaths;
    }


  /**
   * @method loadApplicationConfig()
   * written in the hope of being reusable
   */
  function loadApplicationConfig()
    {    
    if(file_exists(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG))
      {
      $applicationConfig = parse_ini_file(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG, false);
      }
    else
      {
      $applicationConfig = parse_ini_file(MIDAS_BATCHMAKE_MODULE_CONFIG);
      }
    return $applicationConfig;
    }



   
  /**
   * @method checkFileFlag()
   * checks whether the file at the passed in path has the passed in options.
   */
  public function checkFileFlag($file, $options = 0x0)
    {
    $exist    = file_exists($file);
    Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
    $status =  ($exist ? InternationalizationComponent::translate(MIDAS_BATCHMAKE_EXIST_STRING) : InternationalizationComponent::translate(MIDAS_BATCHMAKE_NOT_FOUND_ON_CURRENT_SYSTEM_STRING));
    $ret = $exist; 
    
    if($exist && ($options & MIDAS_BATCHMAKE_CHECK_IF_READABLE))
      {
      $readable = is_readable($file);
      $status .= $readable ? " / Readable" : " / NotReadable";
      $ret = $ret && $readable;
      }
      
    if($exist && ($options & MIDAS_BATCHMAKE_CHECK_IF_WRITABLE))
      {
      $writable = is_writable($file);
      $status .= $writable ? " / Writable" : " / NotWritable";
      $ret = $ret && $writable;
      }
    if($exist && ($options & MIDAS_BATCHMAKE_CHECK_IF_EXECUTABLE))
      {
      $executable = is_executable($file);
      $status .= $executable ? " / Executable" : " / NotExecutable";
      $ret = $ret && $executable;
      }
    if(!$this->IsWindows() && $exist && ($options & MIDAS_BATCHMAKE_CHECK_IF_CHMODABLE))
      {
      $chmodable = $this->IsChmodable($file);
      $status .= $chmodable ? " / Chmodable" : " / NotChmodable";
      $ret = $ret && $chmodable;
      }
    return array($ret, $status); 
    }



  /** 
   * @method isWindows()
   * from KWUtils, may need to be moved.
   * @return True if the current platform is windows
   */
  function isWindows()
    {
    return (strtolower(substr(PHP_OS, 0, 3)) == "win"); 
    }
    
  /** 
   * @method isLinux()
   * from KWUtils, may need to be moved.
   * @return True if the current platform is Linux
   */
  function isLinux()
    {
    return (strtolower(substr(PHP_OS, 0, 5)) == "linux"); 
    }

  /** 
   * @method formatAppName
   * from KWUtils, may need to be moved.
   * Format the application name according to the platform.
   */
  function formatAppName($app_name)
    {
    if(substr(PHP_OS, 0, 3) == "WIN")
      { 
      $app_name = self::AppendStringIfNot($app_name, ".exe"); 
      }
    return $app_name; 
    }

  /**
   * @method isChmodable
   * Check if current PHP process has permission to change the mode 
   * of $fileOrDirectory.
   * from KWUtils, may need to be moved.
   * Note: If return true, the mode of the file will be MIDAS_BATCHMAKE_DEFAULT_MKDIR_MODE 
   *       On windows, return always True
   */
  function isChmodable($fileOrDirectory)
    {
    if($this->isWindows())
      {
      return true;
      }
    
    if(!file_exists($fileOrDirectory))
      {
      Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
      self::Error(InternationalizationComponent::translate(MIDAS_BATCHMAKE_FILE_OR_DIRECTORY_DOESNT_EXIST_STRING).' ['.$fileOrDirectory.']');
      return false; 
      }

    // Get permissions of the file
    // TODO On CIFS filesytem, even if the function GetFilePermissions call clearstatcache(), the value returned can be wrong 
    //$current_perms = self::GetFilePermissions( $fileOrDirectory );
    $current_perms = MIDAS_BATCHMAKE_DEFAULT_MKDIR_MODE; 
    //self::Debug("File permissions: [file: $fileOrDirectory, mode: ".decoct($current_perms)."]"); 
    if($current_perms === false)
      {
      return false;
      }    
    
    // Try to re-apply them 
    if(!@chmod($fileOrDirectory, $current_perms))
      {
      //self::Debug("Failed to change mode: [file: $fileOrDirectory] to [mode: ".decoct($current_perms)."]");
      return false;
      }
    return true; 
    }

  /**
   * @method getApplicationConfigProperties
   * will load the configuration property values for this module, and filter
   * out only those properties that are in the 'batchmake.' config namespace,
   * removing the 'batchmake.' from the key name.
   * @return array of batchmake module specific config properties 
   */
  public function getApplicationConfigProperties()
    {
    $configPropertiesParamVals = array();
    $applicationConfig = $this->loadApplicationConfig();
    
    $modulePropertyNamespace = MIDAS_BATCHMAKE_MODULE . '.';
    foreach($applicationConfig as $configProperty => $configPropertyVal)
      {
      $ind = strpos($configProperty, $modulePropertyNamespace);
      if($ind !== false && $ind  == 0)
        {
        $reducedKey = substr($configProperty, strpos($configProperty, '.') + 1);      
        $configPropertiesParamVals[$reducedKey] = $configPropertyVal;      
        }
      }
      
    return $configPropertiesParamVals;
    }
    
    
    
  /**
   * @method testconfig() 
   * @param $configPropertiesParamVals a set of parameter values to test
   * performs validation on $configPropertiesParamVals, or if that is empty,
   * on the current saved configuration.
   */
  public function testconfig($configPropertiesParamVals = NULL)
    {  
    //default to correct config
    $total_config_correct = 1;
    $configStatus = array();

    // if the passed in config is empty, load it from the config file
    if(empty($configPropertiesParamVals))
      {
      $configPropertiesParamVals = $this->getApplicationConfigProperties();
      }

    $configPropertiesRequirements = $this->getConfigPropertiesRequirements();
    foreach($configPropertiesRequirements as $configProperty => $configPropertyRequirement)
      {
      $configPropertyVal = $configPropertiesParamVals[$configProperty];
      if($configPropertyVal)
        {
        // if the property exists, check its configuration      
        list($result, $status) = $this->checkFileFlag($configPropertyVal, $configPropertyRequirement);
        $configStatus[] = array(MIDAS_BATCHMAKE_PROPERTY_KEY => $configProperty, MIDAS_BATCHMAKE_STATUS_KEY => $status, MIDAS_BATCHMAKE_TYPE_KEY => $result ? MIDAS_BATCHMAKE_STATUS_TYPE_INFO : MIDAS_BATCHMAKE_STATUS_TYPE_ERROR);
        // the property is in error, therefore so is the global config
        if(!$result)
          {
          $total_config_correct = 0;
          }
        }
      else
        {
        // property doesn't exist, both the property and global config are in error
        $configStatus[] = array(MIDAS_BATCHMAKE_PROPERTY_KEY => $configProperty, MIDAS_BATCHMAKE_STATUS_KEY => CONFIG_VALUE_MISSING, MIDAS_BATCHMAKE_TYPE_KEY => MIDAS_BATCHMAKE_STATUS_TYPE_ERROR);
        $total_config_correct = 0;
        }
      }

    // for now assuming will run via condor, so require all of the condor setup

    $appsPaths = $this->getApplicationsPaths();
    foreach($appsPaths as $app => $pathProperty)
      {
      $appPath = $configPropertiesParamVals[$pathProperty] ."/" . $this->formatAppName($app);
      list($result, $status) = $this->checkFileFlag($appPath, MIDAS_BATCHMAKE_CHECK_IF_EXECUTABLE);
      Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
      
      $applicationString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_APPLICATION_STRING);
      $configStatus[] = array(MIDAS_BATCHMAKE_PROPERTY_KEY => $applicationString . ' ' .$appPath, MIDAS_BATCHMAKE_STATUS_KEY => $status, MIDAS_BATCHMAKE_TYPE_KEY => $result ? MIDAS_BATCHMAKE_STATUS_TYPE_INFO : MIDAS_BATCHMAKE_STATUS_TYPE_ERROR);
      // the property is in error, therefore so is the global config
      if(!$result)
        {
        $total_config_correct = 0;
        }
      }

    // Process web server user information

    // TODO what should be done if there are warnings??
    $processUser  = posix_getpwuid(posix_geteuid());
    $processGroup = posix_getgrgid(posix_geteuid());

    $phpProcessString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_PHP_PROCESS_STRING);
    $phpProcessUserString = $phpProcessString . ' ' . InternationalizationComponent::translate(MIDAS_BATCHMAKE_PHP_PROCESS_USER_STRING);
    $phpProcessNameString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_PHP_PROCESS_NAME_STRING);
    $phpProcessGroupString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_PHP_PROCESS_GROUP_STRING);
    $phpProcessHomeString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_PHP_PROCESS_HOME_STRING);
    $phpProcessShellString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_PHP_PROCESS_SHELL_STRING);
    $unknownString = InternationalizationComponent::translate(MIDAS_BATCHMAKE_UNKNOWN_STRING);

    $phpProcessUserNameString = $phpProcessUserString . '[' . $phpProcessNameString . ']';
    $phpProcessUserGroupString = $phpProcessUserString . '[' . $phpProcessGroupString . ']';
    $phpProcessUserHomeString = $phpProcessUserString . '[' . $phpProcessHomeString . ']';
    $phpProcessUserShellString = $phpProcessUserString . '[' . $phpProcessShellString . ']';

    $processProperties = array($phpProcessUserNameString => !empty($processUser[MIDAS_BATCHMAKE_PHP_PROCESS_NAME_STRING]) ? $processUser[MIDAS_BATCHMAKE_PHP_PROCESS_NAME_STRING] : "",
    $phpProcessUserGroupString => !empty($processGroup[MIDAS_BATCHMAKE_PHP_PROCESS_NAME_STRING]) ? $processGroup[MIDAS_BATCHMAKE_PHP_PROCESS_NAME_STRING] : "",
    $phpProcessUserHomeString => !empty($processUser[MIDAS_BATCHMAKE_DIR_KEY]) ? $processUser[MIDAS_BATCHMAKE_DIR_KEY] : "",
    $phpProcessUserShellString => !empty($processUser[MIDAS_BATCHMAKE_PHP_PROCESS_SHELL_STRING]) ? $processUser[MIDAS_BATCHMAKE_PHP_PROCESS_SHELL_STRING] : "");

    foreach($processProperties as $property => $value)
      {
      $status   = !empty($value);
      $configStatus[]   = array(MIDAS_BATCHMAKE_PROPERTY_KEY => $property, 
      MIDAS_BATCHMAKE_STATUS_KEY => $status ? $value : $unknownString, 
      MIDAS_BATCHMAKE_TYPE_KEY => $status ? MIDAS_BATCHMAKE_STATUS_TYPE_INFO : MIDAS_BATCHMAKE_STATUS_TYPE_WARNING);
      }
   
    return array($total_config_correct, $configStatus);

    }

  /**
    * @method isConfigCorrect
    * helper method to return 1 if the config is correct, 0 otherwise
    * @return 1 if config correct, 0 otherwise 
    */
  public function isConfigCorrect()
    {
    $applicationConfig = $this->testconfig();
    return $applicationConfig[0];
    }
      
   
  /**
   * @method getBatchmakeScripts
   * will create a list of Batchmake scripts that exist in the MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY
   * with a .bms extension.
   * @return array of batchmake scripts
   */
  public function getBatchmakeScripts()
    {
    $config = $this->getApplicationConfigProperties();
    $scriptDir = $config[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $globPattern = $scriptDir . '/*' . MIDAS_BATCHMAKE_BATCHMAKE_EXTENSION;
    $scripts = glob($globPattern);
    $scriptNames = array();
    foreach($scripts as $scriptPath)
      {
      $parts = explode('/', $scriptPath);
      $scriptNames[] = $parts[count($parts) - 1];
      }
    return $scriptNames;
    }
    
    
/*        
    // Glob through the tasks directory for xml files
    $tasks = array();
    foreach(KwUtils::GlobRec($this->batchmake_task_directory."/","*.xml") as $filename)
      {
      $taskname = basename($filename, ".xml");
      
      $ret = KwBatchmakeModule::ValidateTaskname( $taskname );  
      $tasks[]  = array( "name" => $taskname, "error" => !$ret);
      }
    
    

    
      /** Recursive glob  * /
  static function GlobRec($path, $match = false)
    {
    // TODO Possible improvement - See http://us2.php.net/manual/en/function.glob.php#87221
    if (!function_exists('fnmatch'))
      {
      if (!$match)
        {
        return glob($path);
        }
      return glob($path.$match);
      }
    $dir = @opendir($path);
    if ($dir == false)
      {
      return array ();
      }
    while ($File = readdir($dir))
      {
      if ($File != "." && $File != "..")
        { 
        if (!$match || fnmatch($match, $File))
          {
          $result[] = $path.$File;
          }
        }
      }
    closedir($dir);
    if ( empty($result))
      {
      return array ();
      }
    return $result;
    }
    */
    
    
    
    
    
} // end class
?>
