<?php
/** notification manager*/
class Cleanup_Notification extends MIDAS_Notification
  {
  public $moduleName = 'cleanup';

  /** init notification process */
  public function init()
    {
    $this->addTask('TASK_CLEANUP_PERFORM_CLEANUP', 'performCleanup', 'Perform directory cleanup');
    } //end init

  /** Removes old files and empty directories within the tmp dir */
  public function performCleanup($params)
    {
    $tempDir = $params['tempDirectory'];
    $days = $params['days']; //days since last modified before we delete the file
    $log = 'Beginning directory cleanup of '.$tempDir."\n";

    if(!is_numeric($days) || $days < 1)
      {
      $days = 1;
      }

    if(!is_dir($tempDir))
      {
      throw new Zend_Exception('Temp directory ('.$tempDir.') does not exist or is not a directory');
      }

    $cutoff = strtotime('-'.$days.' days');
    $handle = opendir($tempDir);
    while(false !== ($name = readdir($handle)))
      {
      if($name != '.' && $name != '..')
        {
        $path = $tempDir.'/'.$name;
        if(is_dir($path) && is_numeric($name))
          {
          $this->_cleanupRecursive($path, $cutoff, $log);
          }
        }
      }
    closedir($handle);
    return $log;
    }

  /** Recursive implementation of cleanup dir */
  private function _cleanupRecursive($dir, $cutoff, &$log)
    {
    $handle = opendir($dir);
    if($handle)
      {
      $dirEmpty = true;
      while(false !== ($file = readdir($handle)))
        {
        if($file != '.' && $file != '..')
          {
          $dirEmpty = false;
          $file = $dir.'/'.$file;
          if(is_dir($file))
            {
            $this->_cleanupRecursive($file, $cutoff, $log);
            }
          else
            {
            if(filemtime($file) < $cutoff)
              {
              $log .= 'Deleting outdated partial file '.$file."\n";
              unlink($file);
              }
            }
          }
        }
      closedir($handle);
      if($dirEmpty)
        {
        $log .= 'Deleting empty directory '.$dir."\n";
        rmdir($dir);
        }
      }
    }
  } //end class
?>

