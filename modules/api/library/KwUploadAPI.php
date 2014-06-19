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

class KwUploadAPI
{
  //const PARAM_NAME_UPLOAD_TOKEN = 'uploadUniqueIdentifier';
  const PARAM_NAME_UPLOAD_TOKEN = 'uploadtoken';

  var $tmp_directory       = '';
  var $testing_enable      = false;

  var $log_file = '';
  var $log_type = 0;

  /** Create the object */
  function __construct($apiSetup)
    {
    //$this->checkApiSetup($apiSetup);
    $this->tmp_directory  = $apiSetup['tmp_directory'];
    $this->testing_enable = $apiSetup['testing'];
    }

  /** check if the $apiSetup provided is valid */
  function checkApiSetup($apiSetup)
    {
    // Not Implemented
    }

  /** Set the temporary directory */
  function setTempDirectory($tmp_directory)
    {
    $this->tmp_directory = $tmp_directory;
    }

  /**
   * Generate an upload token that will act as the authentication token for the upload.
   * This token is the filename of a guaranteed unique file which will be placed under the
   * directory specified by the dirname parameter, which should be used to ensure that
   * the user can only write into a certain logical space.
   */
  function generateToken($args, $dirname = '')
    {
    if(!array_key_exists('filename', $args))
      {
      throw new Exception('Parameter filename is not defined', MIDAS_INVALID_PARAMETER);
      }
    $dir = $dirname == '' ? '' : '/'.$dirname;
    $dir = $this->tmp_directory.$dir;

    if(!file_exists($dir))
      {
      mkdir($dir, 0700, true);
      }
    // create a unique temporary file in the dirname directory
    $unique_identifier = basename(tempnam($dir, $args['filename']));
    if($dirname != '')
      {
      $unique_identifier = $dirname.'/'.$unique_identifier;
      }

    if(empty($unique_identifier))
      {
      throw new Exception('Failed to generate upload token', MIDAS_UPLOAD_TOKEN_GENERATION_FAILED);
      }
    return array('token' => $unique_identifier);
    }

  /** Handle the upload */
  function process($args)
    {
    $uploadOffset = (float)0; // bytes received

    //check parameters
    if (!array_key_exists('filename', $args))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Parameter filename is not defined", $this->log_type, $this->log_file);
      throw new Exception('Parameter filename is not defined', MIDAS_INVALID_PARAMETER);
      }

    $filename = $args['filename']; // XXXX.ISP

    if (!array_key_exists(self::PARAM_NAME_UPLOAD_TOKEN, $args))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Parameter ".self::PARAM_NAME_UPLOAD_TOKEN." is not defined", $this->log_type, $this->log_file);
      throw new Exception('Parameter '.self::PARAM_NAME_UPLOAD_TOKEN.' is not defined', MIDAS_INVALID_PARAMETER);
      }
    $uploadToken = $args[self::PARAM_NAME_UPLOAD_TOKEN]; //XXX123.TMP

    if (!array_key_exists('length', $args))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Parameter length is not defined", $this->log_type, $this->log_file);
      throw new Exception('Parameter length is not defined', MIDAS_INVALID_PARAMETER);
      }
    $length = (float)($args['length']);

    if($this->testing_enable && array_key_exists('localinput', $args))
      {
      $localinput = array_key_exists('localinput', $args) ? $args['localinput'] : false;
      }

    //check if the temporary file exists
    $pathTemporaryFilename = $this->tmp_directory.'/'.$uploadToken;
    if(!file_exists($pathTemporaryFilename))
      {
      error_log(__FILE__.':'.__FUNCTION__.':'.__LINE__.' - '.'Invalid upload token', $this->log_type, $this->log_file);
      throw new Exception('Invalid upload token', MIDAS_INVALID_UPLOAD_TOKEN);
      }
    else
      {
      $uploadOffset = filesize($pathTemporaryFilename);
      }

    // can't do streaming checksum if we have a partial file already.
    $streamChecksum = $uploadOffset == 0;

    ignore_user_abort(TRUE);

    $inputfile = 'php://input'; // Stream (Client -> Server) Mode: Read, Binary
    if ($this->testing_enable && array_key_exists('localinput', $args))
      {
      $inputfile = $localinput; // Stream (LocalServerFile -> Server) Mode: Read, Binary
      }

     $in = fopen($inputfile, 'rb');    // Stream (LocalServerFile -> Server) Mode: Read, Binary
     if($in === FALSE )
        {
        error_log(__FILE__.':'.__FUNCTION__.':'.__LINE__.' - '."Failed to open source:$inputfile", $this->log_type, $this->log_file);
        throw new Exception("Failed to open [$inputfile] source", MIDAS_SOURCE_OPEN_FAILED);
        }

    // open target output
    $out = fopen($pathTemporaryFilename, 'ab'); // Stream (Server -> TempFile) Mode: Append, Binary
    if ($out === false)
        {
        error_log(__FILE__.':'.__FUNCTION__.':'.__LINE__.' - '."Failed to open output file:$pathTemporaryFilename", $this->log_type, $this->log_file);
        throw new Exception("Failed to open output file [$pathTemporaryFilename]", MIDAS_OUTPUT_OPEN_FAILED);
        }

    if($streamChecksum)
      {
      $hashctx = hash_init('md5');
      }

    // read from input and write into file
    $bufSize = 5242880;
    $bufSize = $length < $bufSize ? $length : $bufSize;
    while(connection_status() == CONNECTION_NORMAL && $uploadOffset < $length && ($buf = fread($in, $bufSize)))
      {
      $uploadOffset += strlen($buf);
      fwrite($out, $buf);
      if($length - $uploadOffset < $bufSize)
        {
        $bufSize = $length - $uploadOffset;
        }
      if($streamChecksum)
        {
        hash_update($hashctx, $buf);
        }
      }
    fclose($in);
    fclose($out);

    if($uploadOffset < $length)
      {
      error_log(__FILE__.':'.__FUNCTION__.':'.__LINE__.' - '."Failed to upload file - {$uploadOffset}/{$length} bytes transferred", $this->log_type, $this->log_file);
      throw new Exception("Failed to upload file - {$uploadOffset}/{$length} bytes transferred", MIDAS_UPLOAD_FAILED);
      }

    $data['filename'] = $filename;
    $data['path']     = $pathTemporaryFilename;
    $data['size']     = $uploadOffset;
    $data['md5']      = $streamChecksum ? hash_final($hashctx) : '';

    return $data;
    }

  /** Get the amount of data already uploaded */
  function getOffset($args)
    {
    //check parameters
    if (!array_key_exists(self::PARAM_NAME_UPLOAD_TOKEN, $args))
      {
      throw new Exception('Parameter '.self::PARAM_NAME_UPLOAD_TOKEN.' is not defined', MIDAS_INVALID_PARAMETER);
      }
    $uploadToken = $args[self::PARAM_NAME_UPLOAD_TOKEN];

    $data = array();
    $data['offset'] = filesize($this->tmp_directory."/$uploadToken");

    return $data;
    }
}
