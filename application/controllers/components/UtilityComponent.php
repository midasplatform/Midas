<?php
class UtilityComponent extends AppComponent
{ 
  /** PHP md5_file is very slow on large file. If md5 sum is on the system we use it. */
  static public function md5file($filename)
    {
    // If we have md5 sum
    if(Zend_Registry::get('configGlobal')->md5sum->path)
      {
      $result = exec(Zend_Registry::get('configGlobal')->md5sum->path.' '.$filename);
      $resultarray = explode(' ',$result);
      return $resultarray[0];
      }
    return md5_file($filename);
    }

  /** Safe delete function. Checks if the file can be deleted. */
  static public function safedelete($filename)
    {
    if(!file_exists($filename))
      {
      return false;  
      }
    unlink($filename); 
    }
    
} // end class
?>