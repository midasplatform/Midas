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
   */
  function download($bitstream)
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
          list ($toss, $range) = explode('=', $httpRange);
          str_replace($range, '-', $range);
          $size = $fileSize - 1;
          $length = $fileSize - $range;
          header('HTTP/1.1 206 Partial Content');
          header('Content-Length: '.$length);
          header('Content-Range: bytes '.$range.$size.'/'.$fileSize);
          fseek($handle, $range);
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
          list($toss, $range) = explode('=', $httpRange);
          str_replace($range, '-', $range);
          $size = $fileSize - 1;
          $length = $fileSize - $range;
          header('HTTP/1.1 206 Partial Content');
          header('Content-Length: '.$length);
          header('Content-Range: bytes '.$range.$size.'/'.$fileSize);
          fseek($handle, $range);
          }
        }
      }
    set_time_limit(0);

    //kill the whole ob stack (Zend uses double nested output buffers)
    while(!$this->testingmode && ob_get_level() > 0)
      {
      ob_end_clean();
      }

    while(!feof($handle) && connection_status() == 0)
      {
      $buffer = fread($handle, $chunkSize);
      echo $buffer;
      }
    fclose($handle);

    if(!$this->testingmode) //don't exit if we are in testing mode
      {
      exit(connection_status() == 0 && !connection_aborted());
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
