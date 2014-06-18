<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

$src = false;

for($i = 1; $i < $_SERVER['argc']; $i++)
  {
  switch($_SERVER['argv'][$i])
    {
    case "--src":
      $i++;
      $src = $_SERVER['argv'][$i];
      break;
    default:
      break;
    }
  }

if($src === false)
  {
  echo 'ERROR: you must provide a --src argument';
  exit;
  }

$files = _getMatchingFilesRecursive($src);

foreach($files as $file)
  {
  $fh = fopen($file, 'r');
  $i = 0;

  while(($line = fgets($fh)) !== false)
    {
    $i++;
    if(preg_match('/ [\n\r]*$/', $line))
      {
      echo "ERROR: Trailing whitespace: $file ($i)\n";
      }
    }

  fclose($fh);
  }

function _getMatchingFilesRecursive($src, $dir = '')
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
          $files = array_merge($files, _getMatchingFilesRecursive($src.'/'.$file, $dir.'/'.$file));
          }
        else
          {
          $pathParts = pathinfo($file);
          if(array_key_exists('extension', $pathParts))
            {
            if($pathParts['extension'] == 'php')
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
