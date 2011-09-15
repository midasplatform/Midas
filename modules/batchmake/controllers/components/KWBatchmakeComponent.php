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
?>
<?php
include_once BASE_PATH . '/library/KWUtils.php';
/**
 *  Batchmake_KWBatchmakeComponent
 *  provides utility methods needed to interact with Batchmake via Midas3.
 */
class Batchmake_KWBatchmakeComponent extends AppComponent
{ 
  //@TODO want to set config properties as instance variables rather than passing them around
  //probably make some static factory methods that take in a config set

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

  protected $alternateConfig = false;

  /** will allow an alterate config bundle to be passed in. */
  public function setAlternateConfig($alternate) 
    {
    $this->alternateConfig = $alternate;
    }


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
    if($this->alternateConfig) 
      {
      $applicationConfig = parse_ini_file($this->alternateConfig, false);
      }
    elseif(file_exists(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG))
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
    if(!KWUtils::isWindows() && $exist && ($options & MIDAS_BATCHMAKE_CHECK_IF_CHMODABLE))
      {
      $chmodable = $this->IsChmodable($file);
      $status .= $chmodable ? " / Chmodable" : " / NotChmodable";
      $ret = $ret && $chmodable;
      }
    return array($ret, $status); 
    }






  /**
   * @method isChmodable
   * Check if current PHP process has permission to change the mode 
   * of $fileOrDirectory.
   * @TODO from KWUtils, may need to be moved.
   * Note: If return true, the mode of the file will be MIDAS_BATCHMAKE_DEFAULT_MKDIR_MODE 
   *       On windows, return always True
   */
  function isChmodable($fileOrDirectory)
    {
    if(KWUtils::isWindows())
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
    //self::Debug("File permissions: [file: $fileOrDirectory, mode: ".decoct($current_perms)."]"); 
    //$current_perms = self::GetFilePermissions( $fileOrDirectory );
    $current_perms = KWUtils::DEFAULT_MKDIR_MODE;//MIDAS_BATCHMAKE_DEFAULT_MKDIR_MODE; 
    if($current_perms === false)
      {
      return false;
      }    
    
    if(is_writable($fileOrDirectory))
      {
      // Try to re-apply them 
      $return = chmod($fileOrDirectory, $current_perms);
      }
    else
      {
      $return = false;  
      }
    return $return;    
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
        $configStatus[] = array(MIDAS_BATCHMAKE_PROPERTY_KEY => $configProperty, MIDAS_BATCHMAKE_STATUS_KEY => MIDAS_BATCHMAKE_CONFIG_VALUE_MISSING, MIDAS_BATCHMAKE_TYPE_KEY => MIDAS_BATCHMAKE_STATUS_TYPE_ERROR);
        $total_config_correct = 0;
        }
      }

    // for now assuming will run via condor, so require all of the condor setup

    $appsPaths = $this->getApplicationsPaths();
    foreach($appsPaths as $app => $pathProperty)
      {
      $appPath = $configPropertiesParamVals[$pathProperty] ."/" . KWUtils::formatAppName($app);
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
    * helper method to return true if the config is correct, false otherwise
    * @return true if config correct, false otherwise 
    */
  public function isConfigCorrect()
    {
    $applicationConfig = $this->testconfig();
    return $applicationConfig[0] == 1;
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
    
    
    

    
    
  /**
   * @method findAndSymLinkDependentBatchmakeScripts
   * will look in the tmpDir for a batchmake script and symlink it to the
   * tmpDir, will then find any batchmake scripts that need to be included
   * other than a config script, and symlink them in from the scriptdir,
   * and for each of these additional scripts, will perform the same
   * operation (symlinking included batchmake scripts), 
   * will throw a Zend_Exception if any symlink fails or if a target file
   * doesn't exist.
   * @param $scriptDir the batchmake script dir
   * @param $tmpDir the temporary work dir 
   * @param $scriptName the original batchmake script
   * @param $processed a list of those scripts already processed
   * @return the array of scripts processed
   */
  public function findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $scriptName, $processed = array(), &$currentPath = array()) 
    {
    // check for cycles
    if(array_search($scriptName, $currentPath) !== false)
      {
      throw new Zend_Exception("Cycle found in the include graph of batchmake scripts.");  
      }
    // push this script onto the currentPath
    $currentPath[] = $scriptName;
    // don't process any already processed
    if(!array_key_exists($scriptName, $processed))
      {
      // symlink the top level scrip
      $scriptLink = $tmpDir . '/' . $scriptName;
      $scriptTarget = $scriptDir . '/' . $scriptName;
      if(!file_exists($scriptTarget) || !symlink($scriptTarget, $scriptLink))
        {
        throw new Zend_Exception($scriptTarget . ' could not be sym-linked to ' . $scriptLink);
        }
      // now consider this script to be processed
      $processed[$scriptName] = $scriptName;
      }
      
    // read through the script looking for includes
    $contents = file_get_contents($scriptDir . '/' . $scriptName);
    // looking for lines like
    // Include(PixelCounter.config.bms)
    // /i means case insensitive search
    $pattern = '/include\s*\(\s*(\S*)\s*\)/i';
    preg_match_all($pattern, $contents, $matches);
    // ensure that there actually are matches
    if($matches && count($matches) > 1)
      {
      // we just want the subpattern match, not the full match
      // the subpattern match is the name of the included file
      $subpatternMatches = $matches[1];      
      // now that we have the matches, we only want the ones that are not .config.bms
      foreach($subpatternMatches as $ind => $includeName)
        {
        // only want the includes that are not .config.bms scripts
        if(strpos($includeName, '.config.bms') === false)
          {
          // recursively process this script, updating the $processed list upon success
          // essentially performing depth first search in a graph
          // there could be a problem with a cycle in the include graph,
          // so pass along the currentPath
          $processed = $this->findAndSymLinkDependentBatchmakeScriptsWithCycleDetection($scriptDir, $tmpDir, $includeName, $processed, $currentPath);
          }
        }
      }
    // pop this script off of the current path
    array_pop($currentPath);
    // return the processed list
    return $processed;
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  /**
   * @method findAndSymLinkDependentBmms
   * will look in the $tmpDir for all batchmake scripts that are passed
   * in the array $bmScripts, for each of these, it will find all of the apps
   * included in them using the SetApp Batchmake command, and sym link the
   * corresponding bmm file to the tmpDir, these bmm files are expected to be
   * in the $binDir, will throw a Zend_Exception if any symlink fails or if a 
   * bmm file doesn't exist, or if one of the batchmake scripts doesn't exist.
   * @param $binDir the bin dir with the bmm files
   * @param $tmpDir the temporary work dir 
   * @param $bmScripts the array of Batchmake scripts in the $tmpDir to process
   * @return an array of [ bmmfile => bmScript where bmmfile first found ]
   */
  public function findAndSymLinkDependentBmms($binDir, $tmpDir, $bmScripts) 
    {
    // initialize the list of bmms that have been processed
    $processed = array();
    foreach($bmScripts as $bmScript)
      {
      $scriptPath = $tmpDir . '/' . $bmScript;
      if(!file_exists($scriptPath)) 
        {
        throw new Zend_Exception($scriptPath . ' could not be found');
        }
      $contents = file_get_contents($scriptPath);
      // /i means case insensitive search
      // read through the script looking for lines like
      // SetApp(pixelCounter @PixelCounter)
      $pattern = '/setapp\s*\(\s*\S*\s*@(\S*)\s*\)/i';
      preg_match_all($pattern, $contents, $matches);
      // ensure that there actually are matches
      if($matches && count($matches) > 1)
        {
        // we just want the subpattern match, not the full match
        // the subpattern match is the name of the included file 
        $subpatternMatches = $matches[1];      
        // now that we have the matches, get the app names to use for the bmm
        foreach($subpatternMatches as $ind => $appName)
          {
          if(!array_key_exists($appName, $processed))
            {
            $bmmTarget = $binDir . '/' . $appName . '.bmm';
            $bmmLink = $tmpDir . '/' . $appName . '.bmm';
            if(!file_exists($bmmTarget) || !symlink($bmmTarget, $bmmLink))
              {
              throw new Zend_Exception($bmmTarget . ' could not be sym-linked to ' . $bmmLink);
              }
            // track which bmScript we first saw this app in
            $processed[$appName] = $bmScript;
            }
          }
        }      
      }
    return $processed;
    }

    
    

    
    
 
    

    
    
        



    


    
    
  /**
   * @method compileBatchMakeScript will check that the passed in $batchmakescript
   * in the passed in $tmpDir will compile without errors.
   * @param string $appDir directory where binary applications are located
   * @param string $binDir directory where the BatchMake binary is located
   * @param string $tmpDir directory where the work for SSP should be done
   * @param string $bmScript name of the script, should be in $tmpDir 
   * @return type 
   */
  public function compileBatchMakeScript($appDir, $binDir, $tmpDir, $bmScript)
    {
    // Prepare command
    $params = array(
      '-ap', $appDir, 
      '-p', $tmpDir,
      '-c', $tmpDir.$bmScript,
      ); 
    $cmd = KWUtils::prepareExecCommand($binDir . '/'. MIDAS_BATCHMAKE_EXE, $params);
    if($cmd === false)
      {
      return false;
      } 
    
    // Run command
    KWUtils::exec($cmd, $output, $tmpDir, $returnVal);

    if($returnVal !== 0)
      {
      throw new Zend_Exception("Failed to run: [".$cmd."], output: [".implode(",", $output )."]");
      }
    
    // if BatchMake reports errors, throw an exception
    foreach($output as $ind => $val)
      {
      if(preg_match("/(\d+) error/", $val, $matches))
        {
        // number of errors is index 1, this is based on BatchMake's output
        // it will output the number of errors even if 0
        if($matches[1] == "0")
          {
          return true;
          }
        else
          {
          throw new Zend_Exception("Compiling script [".$bmScript."] yielded output: [".implode(",", $output )."]");
          }
        }
      }
    
    throw new Zend_Exception("Error in BatchMake script, the compile step didn't report errors, output: [".implode(",", $cmd_output )."]");
    }
    
    

  /**
   * @method generateCondorDag will create condor scripts and a condor dag
   * from the batchmake script $bmScript, in the directory $tmpDir.
   * @param type $appDir
   * @param type $tmpDir
   * @param type $binDir
   * @param type $bmScript 
   */
  public function generateCondorDag($appDir, $tmpDir, $binDir, $bmScript)
    {
    $dagName      = $bmScript.'.dagjob'; 
    
    // Prepare command
    $params = array(
      '-ap', $appDir, 
      '-p', $tmpDir,
      '--condor', $tmpDir.$bmScript, $tmpDir.$dagName,
      ); 
    
    $cmd = KWUtils::prepareExecCommand($binDir . '/'. MIDAS_BATCHMAKE_EXE, $params);
    
    // Run command
    KWUtils::exec($cmd, $output, $tmpDir, $returnVal);
     
    if($returnVal !== 0)
      {
      throw new Zend_Exception("Failed to run: [".$cmd."], output: [".implode(",", $cmd_output )."]");
      }
    return $dagName;
    }
    
  /**
   * @method condorSubmitDag will 
   * @param type $condorBinDir
   * @param type $tmpDir
   * @param type $dagScript 
   */  
  public function condorSubmitDag($condorBinDir, $tmpDir, $dagScript)
    {
    // Prepare command  
    $params = array($dagScript);
        
    $cmd = KWUtils::prepareExecCommand($condorBinDir . '/'. MIDAS_BATCHMAKE_CONDOR_SUBMIT_DAG, $params);

    // Run command 
    KWUtils::exec($cmd, $output, $tmpDir, $returnVal);
    
    if($returnVal !== 0)
      {
      throw new Zend_Exception("Failed to run: [".$cmd."], output: [".implode(",", $cmd_output )."]");
      }
    }
    
    
    
    
    
} // end class
?>
