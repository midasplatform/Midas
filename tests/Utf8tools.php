<?php 
/** tools for detecting non utf8 files and tranforming non utf8 files to utf8. */
class Utf8tools
  {
  protected $excludedDirs = array("data", "library", "tmp", ".git", "log");
  protected $excludedExts = array("jpg", "png", "gif", "swf", "jar", "ico", "psd", "swc", "keystore");
  protected $excludedFiles = array(".htaccess");


  /**
   * return true if the string is UTF8 encoded.
   */
  protected function isUtf8($str)
    { 
    $len = strlen($str); 
    for($i = 0; $i < $len; $i++)
      { 
      $c = ord($str[$i]); 
      if ($c > 128)
        { 
        if (($c > 247)) return false; 
        elseif ($c > 239) $bytes = 4; 
        elseif ($c > 223) $bytes = 3; 
        elseif ($c > 191) $bytes = 2; 
        else return false; 
        if (($i + $bytes) > $len) return false; 
        while ($bytes > 1)
          { 
          $i++; 
          $b = ord($str[$i]); 
          if ($b < 128 || $b > 191) return false; 
          $bytes--; 
          } 
        } 
      } 
      return true; 
    } // end of check_utf8 


  /**
   * gets a list of all files rooted at the src, excluding
   * certain subdirs, extensions, and filenames.
   */
  function getMatchingFilesRecursive($src, $dir = '')
    {
    $files = array();
    if(!is_dir($src))
      {
      $files[] = $src;
      }
    else
      {
      $root = opendir($src);
      if($root)
        {
        while($file = readdir($root))
          {
          // We ignore the current and parent directory links
          if($file == '.' || $file == '..')
            {
            continue;
            }
          $fullPath = $src.'/'.$file;
          $relPath = substr($fullPath, strlen($src) - strlen($dir) + 1);
          if(is_dir($src.'/'.$file))
            {
            if(array_search($file, $this->excludedDirs) !== false)
              {
              continue;
              }
            $files = array_merge($files, $this->getMatchingFilesRecursive($src.'/'.$file, $dir.'/'.$file));
            }
          else
            {
            if(array_search($file, $this->excludedFiles) !== false)
              {
              continue;
              }
            $pathParts = pathinfo($file);
            if(array_key_exists('extension', $pathParts))
              {
              if(array_search($pathParts['extension'], $this->excludedExts) === false)
                {
                $files[] = $src.'/'.$file;
                }
              }
            }
          }
        }
      }
    return $files;
    }

  /**
   * create a listing of files, should be called from the MIDAS BASE DIR, checks
   * them for non-utf8 encoded files, and if createUtf8Version is true,
   * will create another file in the same dir alongside any non-utf8 file
   * that is utf8 encoded and has the same name as the non-utf8 file, with
   * an extension of .utf8 .
   */
  public function listNonUtf8Files($createUtf8Version = false)
    {
    $allFiles =  $this->getMatchingFilesRecursive('.');
    echo "The following files have non UTF-8 characters:\n\n";
    foreach($allFiles as $file)
      {
      $filecontents = file_get_contents($file);
      if(!$this->isUtf8($filecontents))
        {
        echo "$file \n";
        if($createUtf8Version)
          {
          $utf8Version = mb_convert_encoding($filecontents, "UTF-8");
          $outfilepath = $file . '.utf8';
          file_put_contents($outfilepath, $utf8Version);
          }
        }
      }
    }



  }

// don't create utf8 versions by default
$create = false;
if(sizeof($argv) > 1)
  {
  if($argv[1] !== 'create')
    {
    echo "Usage (should be from MIDAS BASE DIR):\n\nphp Utf8tools.php [create]\n\noptional argument create says to create utf8 versions on non utf8 encoded files\n";
    exit();
    }
  else
    {
    $create = true;
    }
  }
$utf8 = new  Utf8tools();
$utf8->listNonUtf8Files($create);
?>
