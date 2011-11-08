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
    $olderThan = $params['olderThan'];

    if(!file_exists($tempDir))
      {
      throw new Zend_Exception('Temp directory ('.$tempDir.') does not exist');
      }
    }
  } //end class
?>

