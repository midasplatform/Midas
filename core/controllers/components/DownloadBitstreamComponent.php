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

/** Component that will download a bitstream to the client */
class DownloadBitstreamComponent extends AppComponent
  {

  public $testingmode;

  /** Constructor */
  function __construct()
    {
    }

  /**
   * Calling this will stream the file to the client.
   * The parameter is a bitstream dao.
   * Optional second parameter is the download offset in bytes.
   */
  function download($bitstream, $offset = 0)
    {
    $mimetype = $bitstream->getMimetype();
    $path = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
    $name = $bitstream->getName();
    if(!file_exists($path))
      {
      throw new Zend_Exception('Unable to find file on the disk');
      }
    $chunkSize = 1024 * 8;
    $buffer = '';
    $fileSize = filesize($path);
    $handle = fopen($path, 'rb');
    if($handle === false)
      {
      throw new Zend_Exception('Unable to open the file');
      }
    if(!$this->testingmode) //don't send any headers in testing mode since it will break it
      {
      $modified = gmdate('D, d M Y H:i:s').' GMT';
      $contentType = $mimetype;

      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Last-Modified: '.$modified);
      // if pdf set the content-type acordingly
      if(!isset($contentType) && pathinfo($name, PATHINFO_EXTENSION) == 'pdf')
        {
        $contentType = 'application/pdf';
        $enableContentDisposition = false;
        }
      if(!isset($contentType))
        {
        $contentType = 'application/octet-stream';
        }

      // Hack for .vsp files (for OSA)
      if(!isset($contentType) && strlen($name) > 4 && substr($name, strlen($name) - 4, 4) == '.vsp')
        {
        $contentType = 'application/isp';
        }

      $agent = env('HTTP_USER_AGENT');
      if(preg_match('%Opera(/| )([0-9].[0-9]{1,2})%', $agent) || preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent))
        {
        header('Content-Type: '.$contentType);
        header('Content-Disposition: attachment; filename="'.$name.'";');
        header('Expires: 0');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private', false);
        header('Pragma: private');
        $httpRange = env('HTTP_RANGE');
        if(isset($httpRange))
          {
          // HTTP range is of the form "bytes=n-" where n is the offset
          list(, $range) = explode('=', $httpRange);
          $firstByte = strstr($range, '-', true);
          $lastByte = $fileSize - 1;
          $length = $fileSize - $firstByte;
          header('HTTP/1.1 206 Partial Content');
          header('Content-Length: '.$length);
          header('Content-Range: bytes '.$firstByte.'-'.$lastByte.'/'.$fileSize);
          fseek($handle, $firstByte);
          }
        else
          {
          header('Content-Length: '.$fileSize);
          }
        }
      else
        {
        header('Accept-Ranges: bytes');
        header('Expires: 0');
        header('Content-Type: '.$contentType);
        header('Content-Length: '.$fileSize);
        if(!isset($enableContentDisposition) || $enableContentDisposition == true)
          {
          header('Content-Disposition: attachment; filename="'.$name.'";');
          }
        if(isset($httpRange))
          {
          list(, $range) = explode('=', $httpRange);
          $firstByte = strstr($range, '-', true);
          $lastByte = $fileSize - 1;
          $length = $fileSize - $firstByte;
          header('HTTP/1.1 206 Partial Content');
          header('Content-Length: '.$length);
          header('Content-Range: bytes '.$firstByte.'-'.$lastByte.'/'.$fileSize);
          fseek($handle, $firstByte);
          }
        }
      }
    set_time_limit(0);

    //kill the whole ob stack (Zend uses double nested output buffers)
    while(!$this->testingmode && ob_get_level() > 0)
      {
      ob_end_clean();
      }

    if(is_numeric($offset) && $offset > 0 && $offset <= $fileSize)
      {
      fseek($handle, $offset);
      }

    while(!feof($handle) && connection_status() == 0)
      {
      echo fread($handle, $chunkSize);
      }
    fclose($handle);

    if(!$this->testingmode) //don't exit if we are in testing mode
      {
      exit();
      }
    }
  } //end class

/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode).  Also exposes some additional custom
 * environment information.
 *
 * @param  string $key Environment variable name.
 * @return string Environment variable setting.
 * @link http://book.cakephp.org/view/1130/env
 */
function env($key)
  {
  if($key == 'HTTPS')
    {
    if(isset($_SERVER['HTTPS']))
      {
      return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
      }
    return (strpos(env('SCRIPT_URI'), 'https://') === 0);
    }

  if($key == 'SCRIPT_NAME')
    {
    if(env('CGI_MODE') && isset($_ENV['SCRIPT_URL']))
      {
      $key = 'SCRIPT_URL';
      }
    }

  $val = null;
  if(isset($_SERVER[$key]))
    {
    $val = $_SERVER[$key];
    }
  elseif(isset($_ENV[$key]))
    {
    $val = $_ENV[$key];
    }
  elseif(getenv($key) !== false)
    {
    $val = getenv($key);
    }

  if($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR'))
    {
    $addr = env('HTTP_PC_REMOTE_ADDR');
    if($addr !== null)
      {
      $val = $addr;
      }
    }

  if($val !== null)
    {
    return $val;
    }

  switch($key)
    {
    case 'SCRIPT_FILENAME':
      if(defined('SERVER_IIS') && SERVER_IIS === true)
        {
        return str_replace('\\\\', '\\', env('PATH_TRANSLATED'));
        }
      break;
    case 'DOCUMENT_ROOT':
      $name = env('SCRIPT_NAME');
      $filename = env('SCRIPT_FILENAME');
      $offset = 0;
      if(!strpos($name, '.php'))
        {
        $offset = 4;
        }
      return substr($filename, 0, strlen($filename) - (strlen($name) + $offset));
      break;
    case 'PHP_SELF':
      return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
      break;
    case 'CGI_MODE':
      return (PHP_SAPI === 'cgi');
      break;
    case 'HTTP_BASE':
      $host = env('HTTP_HOST');
      if(substr_count($host, '.') !== 1)
        {
        return preg_replace('/^([^.])*/i', null, env('HTTP_HOST'));
        }
      return '.'.$host;
      break;
    default:
      break;
    }
  return null;
  }
