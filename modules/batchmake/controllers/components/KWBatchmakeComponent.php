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


  protected $configPropertiesRequirements = array(TMP_DIR_PROPERTY => CHECK_IF_CHMODABLE_RW,
  BIN_DIR_PROPERTY => CHECK_IF_READABLE, 
  SCRIPT_DIR_PROPERTY => CHECK_IF_READABLE, 
  APP_DIR_PROPERTY => CHECK_IF_READABLE, 
  DATA_DIR_PROPERTY => CHECK_IF_CHMODABLE_RW,
  CONDOR_BIN_DIR_PROPERTY => CHECK_IF_READABLE);

  protected $applicationsPaths = array(CONDOR_STATUS => CONDOR_BIN_DIR_PROPERTY,
  CONDOR_QUEUE => CONDOR_BIN_DIR_PROPERTY,
  CONDOR_SUBMIT => CONDOR_BIN_DIR_PROPERTY,
  CONDOR_SUBMIT_DAG => CONDOR_BIN_DIR_PROPERTY,
  BATCHMAKE_EXE => BIN_DIR_PROPERTY);




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
    if(file_exists(BATCHMAKE_MODULE_LOCAL_CONFIG))
      {
      $applicationConfig = parse_ini_file(BATCHMAKE_MODULE_LOCAL_CONFIG, true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BATCHMAKE_MODULE_CONFIG);
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
    $status =  ($exist ? InternationalizationComponent::translate(EXIST_STRING) : InternationalizationComponent::translate(NOT_FOUND_ON_CURRENT_SYSTEM_STRING));
    $ret = $exist; 
    
    if($exist && ($options & CHECK_IF_READABLE))
      {
      $readable = is_readable($file);
      $status .= $readable ? " / Readable" : " / NotReadable";
      $ret = $ret && $readable;
      }
      
    if($exist && ($options & CHECK_IF_WRITABLE))
      {
      $writable = is_writable($file);
      $status .= $writable ? " / Writable" : " / NotWritable";
      $ret = $ret && $writable;
      }
    if($exist && ($options & CHECK_IF_EXECUTABLE))
      {
      $executable = is_executable($file);
      $status .= $executable ? " / Executable" : " / NotExecutable";
      $ret = $ret && $executable;
      }
    if(!$this->IsWindows() && $exist && ($options & CHECK_IF_CHMODABLE))
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
   * Note: If return true, the mode of the file will be DEFAULT_MKDIR_MODE 
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
      self::Error(InternationalizationComponent::translate(FILE_OR_DIRECTORY_DOESNT_EXIST_STRING).' ['.$fileOrDirectory.']');
      return false; 
      }

    // Get permissions of the file
    // TODO On CIFS filesytem, even if the function GetFilePermissions call clearstatcache(), the value returned can be wrong 
    //$current_perms = self::GetFilePermissions( $fileOrDirectory );
    $current_perms = DEFAULT_MKDIR_MODE; 
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


    
} // end class
?>
