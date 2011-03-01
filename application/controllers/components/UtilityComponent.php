<?php
class UtilityComponent extends AppComponent
{ 
  
  /** create init file*/
  static public function createInitFile($path,$data)
    {
    if(!is_writable(dirname($path)))
      {
      throw new Zend_Exception("Unable to write in: ".dirname($path));
      }
    if(file_exists($path))
      {
      unset($path);      
      }
    
    if(!is_array($data)||empty($data))
      {
      throw new Zend_Exception("Error parameters");
      }
    $text="";
    
    foreach($data as $delimiter=>$d)
      {
      $text.= "[{$delimiter}]\n";
      foreach($d as $field=>$value)
        {
        $text.= "{$field}={$value}\n";
        }
      $text.="\n\n";
      }
    $fp = fopen ($path, "w");  
    fwrite($fp,$text);
    fclose ($fp);  
    return $text;
    }
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
  static function CheckPhpExtensions( $phpextensions )
    {
    $phpextension_missing = array(); 
    foreach ($phpextensions as $name => $param)
      {
      $is_loaded      = extension_loaded($name); 
      $is_func_exists = function_exists($name);
      if (!$is_loaded && !$is_func_exists)
        {
        $is_critical = $param[0];
        $message = "<b>$name</b>: Unable to find '$name' php extension/function. " ; 
        $message .= ($param[1] === false ? "Fix the problem and re-run the install script." : $param[1]);   
        if ($is_critical) { exit ($message); }
        $phpextension_missing[$name] = $message;  
        }
      }
    return $phpextension_missing; 
    }
    
    
      /**
   * Check if ImageMagick is available. 
   * Return an array of the form [Is_Ok, Message]
   * 
   * Where Is_Ok is a boolean indicating if ImageMagick is operational 
   * and where Message contains either:
   *    - if Is_ok == true, the version of ImageMagick
   *    - If Is_Ok == false, details regarding the problem
   */ 
  static function IsImageMagickWorking()
    {
    // if command is successfull $ret shouldn't be empty
    @exec('convert', $output, $returnvalue);
    if (count($output) > 0)
      {
      // version line should look like: "Version: ImageMagick 6.4.7 2008-12-04 Q16 http://www.imagemagick.org"
      list($version_line, $copyright_line) = $output; 
      
      // split version by spaces
      $version_chunks = explode(" ", $version_line); 
      
      // assume version is the third element
      $version = $version_chunks[2]; 
      
      // get major, minor and patch number
      list($major, $minor, $patch) = explode(".", $version); 
      
      if ($major < 6)
        {
        $text = "<b>ImageMagick</b> ($version) is found. Version (>=6.0) is required. Please install imagemagick from http://www.imagemagick.org";
        return array(false, $text); 
        }
      return array(true, "ImageMagick ($version) found"); 
      }
    if(!$returnvalue)
      {
      return array(true, "ImageMagick found"); 
      }
    $text = "<b>ImageMagick</b> (>=6.0) is not found. Please install imagemagick from http://www.imagemagick.org"; 
    return array(false, $text);
    }
  /** format filz size*/
  static public function formatSize($sizeInByte)
    {
    $dataNorme='B';
    if(Zend_Registry::get('configGlobal')->application->lang=='fr')
      {
      $dataNorme='o';
      }
    if (strlen($sizeInByte) <= 9 && strlen($sizeInByte) >= 7)
      { 
      $sizeInByte = number_format($sizeInByte / 1048576,1); 
      return "$sizeInByte M$dataNorme"; 
      } 
    elseif (strlen($sizeInByte) >= 10) 
      { 
      $sizeInByte = number_format($sizeInByte / 1073741824,1); 
      return "$sizeInByte G$dataNorme"; 
      } 
    else 
      { 
      $sizeInByte = number_format($sizeInByte / 1024,1); 
      return "$sizeInByte K$dataNorme"; 
      } 
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