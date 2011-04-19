<?php
class UtilityComponent extends AppComponent
{ 

  static public function getAllModules()
    {
    $modules=array();
    if (file_exists(BASE_PATH.'/modules/')&&$handle = opendir(BASE_PATH.'/modules/'))
      {
      while (false !== ($file = readdir($handle)))
        {
        if(file_exists(BASE_PATH.'/modules/'.$file.'/configs/module.ini'))
          {
          $config = new Zend_Config_Ini(BASE_PATH.'/modules/'.$file.'/configs/module.ini','global',true);
          $config->db=array();
          $handleDB = opendir(BASE_PATH.'/modules/'.$file.'/database');
          if(file_exists(BASE_PATH.'/modules/'.$file.'/database'))
            {
            while (false !== ($fileDB = readdir($handleDB)))
              {
              if(file_exists(BASE_PATH.'/modules/'.$file.'/database/'.$fileDB.'/'))
                {
                switch ($fileDB)
                  {
                  case 'mysql':$config->db->PDO_MYSQL=true; break;
                  case 'pgsql':$config->db->PDO_PGSQL=true;break;
                  case 'ibm':$config->db->PDO_IBM=true;break;
                  case 'oci':$config->db->PDO_OCI=true;break;
                  case 'sqlite':$config->db->PDO_SQLITE=true;break;
                  case 'cassandra':$config->db->CASSANDRA=true;break;
                  }
                }
              }
            }
          $modules[$file]=$config;
          }
        }
      closedir($handle);
      }

    return $modules;
    }
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
    if (count($output) == 0)
      {
      @exec('im-convert', $output, $returnvalue);
      }
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
    
    
  
    
   /** Function to run the sql script */
 static function run_mysql_from_file($sqlfile,$host,$username,$password,$dbname,$port)
    {
    $db = @mysql_connect("$host:$port", "$username", "$password");
    $select=@mysql_select_db($dbname,$db);
    if(!$db||!$select)
      {
      throw new Zend_Exception("Unable to connect.");
      }
    $requetes="";

    $sql=file($sqlfile); 
    foreach($sql as $l)
      {
      if (substr(trim($l),0,2)!="--")
        { 
        $requetes .= $l;
        }
      }

    $reqs = explode(";",$requetes);
    foreach($reqs as $req)
      {	// et on les éxécute
      if (!mysql_query($req,$db) && trim($req)!="")
        {
        throw new Zend_Exception("Unable to execute: ".$req );
        }
      }
    return true;
    }
      /** Function to run the sql script */
  static function run_pgsql_from_file($sqlfile,$host,$username,$password,$dbname,$port)
    {
    $pgdb = @pg_connect("host=$host port=$port dbname=$dbname user=$username password=$password");
    $file_content = file($sqlfile);
    $query = "";
    $linnum = 0;
    foreach ($file_content as $sql_line)
      {
      $tsl = trim($sql_line);
      if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#"))
        {
        $query .= $sql_line;
        if (preg_match("/;\s*$/", $sql_line))
          {
          $query = str_replace(";", "", "$query");
          $result = pg_query($query);
          if (!$result)
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
    
} // end class
?>