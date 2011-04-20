<?php
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date$
Version:   $Revision$

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php

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
  
   /** */
  function generateToken($args)
    {
    //check parameter
    if (!array_key_exists('filename', $args))
      {
      throw new Exception('Parameter filename is not defined', -150);
      }
  
    // create a unique temporary file
    $unique_identifier = basename(tempnam($this->tmp_directory, $args['filename']));
    
    if ( empty($unique_identifier) )
      {
      throw new Exception('Failed to generate upload token', -140);
      }
    $data['token'] = $unique_identifier; 
    return $data; 
    }
    
  /** Handle the upload */
  function process($args)
    {
    $uploadOffset = (float)0; // bytes received
  
    //check parameters
    if (!array_key_exists('filename', $args))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Parameter filename is not defined", $this->log_type, $this->log_file);
      throw new Exception('Parameter filename is not defined', -150);
      }
  
    $filename = $args['filename']; // XXXX.ISP
    
    if (!array_key_exists(self::PARAM_NAME_UPLOAD_TOKEN, $args))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Parameter ".self::PARAM_NAME_UPLOAD_TOKEN." is not defined", $this->log_type, $this->log_file);
      throw new Exception('Parameter '.self::PARAM_NAME_UPLOAD_TOKEN.' is not defined', -150);
      }
    $uploadToken = $args[self::PARAM_NAME_UPLOAD_TOKEN]; //XXX123.TMP
    
    if (!array_key_exists('length', $args))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Parameter length is not defined", $this->log_type, $this->log_file);
      throw new Exception('Parameter length is not defined', -150);
      }
    $length = (float)($args['length']);
    
    if ($this->testing_enable && array_key_exists('localinput', $args))
      {
      $localinput = array_key_exists('localinput', $args) ? $args['localinput'] : false; 
      }
    
    //check if the temporary file exists
    $pathTemporaryFilename = $this->tmp_directory."/".$uploadToken;
    if (!file_exists($pathTemporaryFilename))
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Invalid upload token", $this->log_type, $this->log_file);
      throw new Exception("Invalid upload token", -141);
      }
    else
      {
      $uploadOffset = filesize($pathTemporaryFilename);
      //$controller->log("$filename exists - uploadOffset:$uploadOffset", LOG_DEBUG);
      }

    set_time_limit(0); // Timeout of the PHP script set to Infinite
    ignore_user_abort(TRUE);

    $inputfile = "php://input"; // Stream (Client -> Server) Mode: Read, Binary
    if ($this->testing_enable && array_key_exists('localinput', $args))
      {
      $inputfile = $localinput; // Stream (LocalServerFile -> Server) Mode: Read, Binary    
      }

     $in = fopen($inputfile, "rb");    // Stream (LocalServerFile -> Server) Mode: Read, Binary
     if ($in === FALSE )
        {
        error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Failed to open source:$inputfile", $this->log_type, $this->log_file);
        throw new Exception("Failed to open [$inputfile] source", -142);
        }

    // open target output
    $out = fopen($pathTemporaryFilename, "ab"); // Stream (Server -> TempFile) Mode: Append, Binary
    if ($out === FALSE )
        {
        error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Failed to open output file:$pathTemporaryFilename", $this->log_type, $this->log_file);
        throw new Exception("Failed to open output file [$pathTemporaryFilename]", -143);
        }

    $bufSize = 10485760;
    $bufSize = ($length < $bufSize)?$length:$bufSize;

    // read from input and write into file
    while (connection_status() == CONNECTION_NORMAL && $uploadOffset < $length && ($buf = fread($in, $bufSize)))
      {
      $uploadOffset += strlen($buf);
      //$this->log("uploadOffset: $uploadOffset", LOG_DEBUG);
      fwrite($out, $buf);
      if ($length-$uploadOffset < $bufSize)
        {
        $bufSize = $length-$uploadOffset;
        }
      }
    fclose($in);
    fclose($out);

    if ($uploadOffset < $length)
      {
      error_log(__FILE__.":".__FUNCTION__.":".__LINE__." - "."Failed to upload file - {$uploadOffset}/{$length} bytes transferred", $this->log_type, $this->log_file);
      throw new Exception("Failed to upload file - {$uploadOffset}/{$length} bytes transferred", -105);
      }

    $data['filename'] = $filename; 
    $data['path']     = $pathTemporaryFilename;
    $data['size']     = $uploadOffset;

    return $data; 
    }
      
  /** Get the amount of data already uploaded */
  function getOffset($args)
    {
    //check parameters
    if (!array_key_exists(self::PARAM_NAME_UPLOAD_TOKEN, $args))
      {
      throw new Exception('Parameter '.self::PARAM_NAME_UPLOAD_TOKEN.' is not defined', -150);
      }
    $uploadToken = $args[self::PARAM_NAME_UPLOAD_TOKEN]; //XXX123.TMP
    
    $offset = filesize($this->tmp_directory."/$uploadToken");  
    
    $data['offset'] = $offset; 
    
    return $data; 
    }
}


?>
