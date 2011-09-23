<?php
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date$
Version:   $Revision$

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php
/**
 * globally useful utility functions.
 */
class KWUtils
{

  CONST DEFAULT_MKDIR_MODE = 0775;

  /**
   * @method mkDir
   * @TODO what to do with errors in a way that is consistent with error reporting
   * Will create the directory $dir and set the filemode so that the newly
   * created dir is writable by the current user.
   * @return true on success, false otherwise
   */
  public static function mkDir($dir, $mode = self::DEFAULT_MKDIR_MODE)
    {
    if(!file_exists($dir) && !mkdir($dir, $mode, true))
      {
      return false;
      }
    // change file mode
    // even though we are swallowing the error messages, we return false
    // if the operation can't be completed
    if(!is_writeable($dir) || @!chmod($dir, $mode))
      {
      return false;
      }
    return true;
    }

  /**
   * @method createSubDirectories recursively create subdirectories starting at
   * baseDirectory, sequentially creating each of the directories in the
   * subDirectories array, according to the passed in mode.
   * @param $baseDirectory the first directory to create
   * @param $subDirectories an array of directories that will be created in a
   * recursive fashion, each one appending to the last as a deeper subdirectory
   * of baseDirectory
   * @param the mode to create the new directories
   */
  public static function createSubDirectories($baseDirectory, $subDirectories, $mode = self::DEFAULT_MKDIR_MODE)
    {
    if(!file_exists($baseDirectory) )
      {
      throw new Zend_Exception($baseDirectory . ' does not exist');
      }
    $relpath = '';
    foreach($subDirectories as $directory)
      {
      $relpath .= $directory . "/";

      if(!KwUtils::mkDir($baseDirectory . $relpath, $mode))
        {
        throw new Zend_Exception($baseDirectory . $relpath . ' could not be created');
        }
      }
    return $baseDirectory . $relpath;
    }

  /**
   * @method isWindows()
   * @return True if the current platform is windows
   */
  public static function isWindows()
    {
    return (strtolower(substr(PHP_OS, 0, 3)) == "win");
    }


  /**
   * @method escapeCommand
   * will escape a command respecting the format of the current platform
   * @param $command, the command to be escaped
   * @return the $command, $escaped for the current platform
   * @TODO, how to test this?
   */
  public static function escapeCommand($command )
    {
    // if windows platform, add extra double-quote
    // See http://www.mail-archive.com/internals@lists.php.net/msg29874.html
    if(KWUtils::isWindows() )
      {
      $command = '"'.$command.'"';
      }

    return $command;
    }

  /**
   * @method appendStringIfNot will append the string $ext to
   * $subject if it is not already a suffix of $subject
   * @param $subject, the string to be appended to
   * @param $ext, the extension to check for and append
   * @return $subject, will end with the suffix $ext
   */
  public static function appendStringIfNot($subject, $ext)
    {
    if(!(substr($subject, strlen($subject) - strlen($ext)) === $ext)  )
      {
      $subject .= $ext;
      }
    return $subject;
    }

  /**
   * @method exec
   * will execute a command, respecting the format of the current platform.
   * @param $command to be executed, with all arguments, and formatted correctly
   * @param $output, a reference to put the output of the command
   * @param $chdir, the dir to change to for execution, if any
   * @param $return_val, a reference to put the return value of the command
   *  the temporary work dir
   */
  public static function exec($command, &$output = null, $chdir = "", &$return_val = null)
    {
    if(!empty($chdir) && is_dir($chdir))
      {
      if(!chdir($chdir))
        {
        throw new Zend_Exception("Failed to change directory: [".$chdir."]");
        }
      }
    // on Linux need to add redirection to handle stderr
    $redirect_error = KWUtils::isLinux() ? " 2>&1" : "";
    exec(KWUtils::escapeCommand($command) . $redirect_error, $output, $return_val);
    }


  /**
   * @method isLinux()
   * @return True if the current platform is Linux
   */
  public static function isLinux()
    {
    return (strtolower(substr(PHP_OS, 0, 5)) == "linux");
    }



  /**
   * @method prepareExecCommand
   * will prepare an executable application and params for command line
   * execution, including escaping and quoting arguments.
   * @param $app_name, the application to be executed
   * @param $params, an array of arguments to the application
   * @return the full command line command, escaped and quoted, will throw a
   * Zend_Exception if the app is not in the path and not executable
   */
  public static function prepareExecCommand($app_name, $params = array())
    {
    // Check if application is executable, if not, see if you can find it
    // in the path
    if(!KWUtils::isExecutable($app_name, false))
      {
      $app_name = KWUtils::findApp($app_name, true);
      }

    // escape parameters
    $escapedParams = array();
    foreach($params as $param)
      {
      $escapedParams[] = escapeshellarg($param);
      }

    // glue together app_name and params using spaces
    return escapeshellarg($app_name)." ".implode(" ", $escapedParams);
    }


  /**
   * @method isExecutable will return true if the app can be found and is
   * executable, can optionally look in the path.
   * @param string $app_name, the app to check
   * @param boolean $check_in_path, if true, will search in path for app
   * @return True if app_name is found and executable, False otherwise
   */
  public static function isExecutable($app_name, $check_in_path = false)
    {
    if(!is_executable($app_name ))
      {
      if($check_in_path)
        {
        try
          {
          if(KWUtils::findApp($app_name, true))
            {
            return true;
            }
          }
        catch(Zend_Exception $ze)
          {
          return false;
          }
        }
      return false;
      }
    return true;
    }

  /**
   * @method findApp will return the absolute path of an application
   * @param $app_name, the name of the application
   * @param $check_execution_flag, whether to include in the check that the
   * application is executable
   * @return the path to the application, throws a Zend_Exception if the app
   * can't be found, or if $check_execution_flag  is set and the app is not
   * executable.
   */
  public static function findApp($app_name, $check_execution_flag )
    {
    $PHP_PATH_SEPARATOR = ":";
    // split path
    $path_list = explode($PHP_PATH_SEPARATOR, getenv("PATH"));

    // loop through paths
    foreach($path_list as $path)
      {
      $status = false;
      $path_to_app = KWUtils::appendStringIfNot($path, DIRECTORY_SEPARATOR).$app_name;
      if($check_execution_flag)
        {
        if(is_executable($path_to_app))
          {
          $status = true;
          break;
          }
        }
      else
        {
        if(file_exists($path_to_app))
          {
          $status = true;
          break;
          }
        }
      }
    if(!$status)
      {
      throw new Zend_Exception("Failed to locate the application: [".$app_name."] [check_execution_flag:".$check_execution_flag."]");
      }
    return $path_to_app;
    }




  /**
   * @method formatAppName
   * Format the application name according to the platform.
   */
  public static function formatAppName($app_name)
    {
    if(substr(PHP_OS, 0, 3) == "WIN")
      {
      $app_name = KWUtils::appendStringIfNot($app_name, ".exe");
      }
    return $app_name;
    }


}
