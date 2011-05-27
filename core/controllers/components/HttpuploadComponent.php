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

/** Httpupload (java tool*/
class HttpuploadComponent extends AppComponent
  {
  /** process*/
  function process_http_upload($params)
    {
    $uploadOffset = 0; // bytes received

    //check parameters
    if(!isset($params['filename']) || empty($params['filename']))
      {
      $this->getLogger()->crit("[ERROR]'filename' parameter is not set");
      echo "[ERROR]'filename' parameter is not set";
      return;
      }
    $filename = $params['filename'];
    $this->getLogger()->info(__METHOD__."filename: ".$filename);

    if(!isset($params['length']) || empty($params['length']))
      {
      $this->getLogger()->crit(__METHOD__."[ERROR]'length' parameter is not set");
      echo "[ERROR]'length' parameter is not set";
      return;
      }
    $length = $params['length'];

    if(!isset($params['uploadUniqueIdentifier']) || empty($params['uploadUniqueIdentifier']))
      {
      $this->getLogger()->crit(__METHOD__."[ERROR]'uploadUniqueIdentifier' parameter is not set");
      echo "[ERROR]'uploadUniqueIdentifier' parameter is not set";
      return;
      }
    $uploadUniqueIdentifier = $params['uploadUniqueIdentifier'];
    //check ifthe temporary file exists
    $pathTemporaryFilename = BASE_PATH.'/tmp/misc/'.$uploadUniqueIdentifier;
    if(!file_exists($pathTemporaryFilename))
      {
      $this->getLogger()->crit(__METHOD__."[ERROR]'uploadUniqueIdentifier' parameter is incorrect: ".$uploadUniqueIdentifier);
      echo "[ERROR]'uploadUniqueIdentifier' parameter is incorrect: ".$uploadUniqueIdentifier;
      return;
      }
    else
      {
      $uploadOffset = filesize($pathTemporaryFilename);
      $this->getLogger()->info(__METHOD__.$filename." exists - uploadOffset:".$uploadOffset);
      }

    set_time_limit(0); // Timeout of the PHP script set to Infinite
    ignore_user_abort(TRUE);

    if(isset($params['testingmode']))
      {
      $test = $params['testingmode'];
      }
    if(isset($test) && !empty($test))
      {
      if(!isset($params['path']) || empty($params['path']))
        {
        $this->getLogger()->crit(__METHOD__."[ERROR]'path' parameter is not set");
        echo "[ERROR]'path' parameter is not set";
        return;
        }
      $in = fopen($params['path'], "rb"); // Stream (Applet -> Server) Mode: Read, Binary
      }
    else
      {
      $in = fopen("php://input", "rb"); // Stream (Applet -> Server) Mode: Read, Binary
      }

    $out = fopen($pathTemporaryFilename, "ab"); // Stream (Server -> TempFile) Mode: Append, Binary

    $bufSize = 10485760;
    $bufSize = ($length < $bufSize) ? $length : $bufSize;
    $this->getLogger()->info(__METHOD__."bufSize: ".$bufSize);
    //$cstatus = connection_status();
    //$this->log("connection_status: $cstatus", LOG_DEBUG);
    // read from input and write into file
    while(connection_status() == CONNECTION_NORMAL && $uploadOffset < $length && ($buf = fread($in, $bufSize)))
      {
      $uploadOffset += strlen($buf);
      //$this->log("uploadOffset: $uploadOffset", LOG_DEBUG);
      fwrite($out, $buf);
      if($length - $uploadOffset < $bufSize)
        {
        $bufSize = $length - $uploadOffset;
        }
      }
    //$cstatus = connection_status();
    //$this->log("connection_status: $cstatus", LOG_DEBUG);
    fclose($in);
    fclose($out);

    //$this->log("uploadOffset: $uploadOffset", LOG_DEBUG);

    if($uploadOffset < $length)
      {
      $this->getLogger()->crit(__METHOD__."[ERROR]Failed to upload file (".($uploadOffset / $length)." bytes)");
      echo "[ERROR]Failed to upload file (".($uploadOffset / $length)." bytes)";
      return;
      }

    return array ($filename, $pathTemporaryFilename, $length);
    }

  /** get_http_upload_unique_identifier*/
  function get_http_upload_unique_identifier($params)
    {
    //check parameter
    if(!isset($params['filename']) || empty($params['filename']))
      {
      $this->getLogger()->crit(__METHOD__."[ERROR]"."get_http_upload_unique_identifier: 'filename' parameter is missing");
      echo "[ERROR]"."get_http_upload_unique_identifier: 'filename' parameter is missing";
      return;
      }
    $filename = $params['filename'];
    $unique_identifier = basename(tempnam(BASE_PATH.'/tmp/misc/', $filename));
    if(!$unique_identifier)
      {
      $this->getLogger()->crit(__METHOD__."[ERROR]"."get_http_upload_unique_identifier: Failed to create unique identifier");
      echo "[ERROR]"."get_http_upload_unique_identifier: Failed to create unique identifier";
      return;
      }
    $this->getLogger()->info(__METHOD__."[OK]".$unique_identifier);
    echo "[OK]".$unique_identifier;
    }

  /** used to see how much of a file made it to the server during an
   * interrupted upload attempt **/
  function get_http_upload_offset($params)
    {
    //check parameters
    if(!isset($params['uploadUniqueIdentifier']) || empty($params['uploadUniqueIdentifier']))
      {
      echo "[ERROR]'uploadUniqueIdentifier' parameter is not set";
      return;
      }
    $uploadUniqueIdentifier = $params['uploadUniqueIdentifier'];
    
    echo sprintf("[OK]%u", filesize(BASE_PATH.'/tmp/misc/'."$uploadUniqueIdentifier"));
    }
  }
