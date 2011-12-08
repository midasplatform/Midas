<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
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

/**
 * This component is used for large uploads and is used by
 * the web api and the java uploader.  It generates an authenticated
 * upload token that can be used to start or resume an upload.
 */
class HttpuploadComponent extends AppComponent
  {

  var $tmpDirectory = '';
  var $tokenParamName = 'uploadtoken';
  var $testingEnable = false;

  /** Set the upload temporary directory */
  public function setTmpDirectory($dir)
    {
    $this->tmpDirectory = $dir;
    }

  /** Set whether we are in testing mode or not (boolean) */
  public function setTestingMode($testing)
    {
    $this->testingEnable = $testing;
    }

  /** Set the name of the uploadtoken parameter that is being passed */
  public function setTokenParamName($name)
    {
    $this->tokenParamName = $name;
    }

  /**
   * Generate an upload token that will act as the authentication token for the upload.
   * This token is the filename of a guaranteed unique file which will be placed under the
   * directory specified by the dirname parameter, which should be used to ensure that
   * the user can only write into a certain logical space.
   */
  public function generateToken($args, $dirname = '')
    {
    if(!array_key_exists('filename', $args))
      {
      throw new Exception('Parameter filename is not defined', -150);
      }
    $dir = $dirname == '' ? '' : '/'.$dirname;
    $dir = $this->tmpDirectory.$dir;

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
      throw new Exception('Failed to generate upload token', -140);
      }
    return array('token' => $unique_identifier);
    }

  /** Handle the upload */
  public function process($args)
    {
    $uploadOffset = (float)0; // bytes received

    if(!array_key_exists('filename', $args))
      {
      throw new Exception('Parameter filename is not defined', -150);
      }
    $filename = $args['filename'];

    if(!array_key_exists($this->tokenParamName, $args))
      {
      throw new Exception('Parameter '.$this->tokenParamName.' is not defined', -150);
      }
    $uploadToken = $args[$this->tokenParamName];

    if(!array_key_exists('length', $args))
      {
      throw new Exception('Parameter length is not defined', -150);
      }
    $length = (float)($args['length']);

    if($this->testingEnable && array_key_exists('localinput', $args))
      {
      $localinput = array_key_exists('localinput', $args) ? $args['localinput'] : false;
      }

    //check if the temporary file exists
    $pathTemporaryFilename = $this->tmpDirectory.'/'.$uploadToken;
    if(!file_exists($pathTemporaryFilename))
      {
      throw new Exception('Invalid upload token', -141);
      }
    else
      {
      $uploadOffset = filesize($pathTemporaryFilename);
      }

    // can't do streaming checksum if we have a partial file already.
    $streamChecksum = $uploadOffset == 0;

    set_time_limit(0); // Timeout of the PHP script set to Infinite
    ignore_user_abort(true);

    $inputfile = 'php://input'; // Stream (Client -> Server) Mode: Read, Binary
    if($this->testingEnable && array_key_exists('localinput', $args))
      {
      $inputfile = $localinput; // Stream (LocalServerFile -> Server) Mode: Read, Binary
      }

    $in = fopen($inputfile, 'rb'); // Stream (LocalServerFile -> Server) Mode: Read, Binary
    if($in === false)
      {
      throw new Exception('Failed to open ['.$inputfile.'] source', -142);
      }

    // open target output
    $out = fopen($pathTemporaryFilename, 'ab'); // Stream (Server -> TempFile) Mode: Append, Binary
    if($out === false)
      {
      throw new Exception('Failed to open output file ['.$pathTemporaryFilename.']', -143);
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
      throw new Exception('Failed to upload file - '.$uploadOffset.'/'.$length.' bytes transferred', -105);
      }

    $data['filename'] = $filename;
    $data['path']     = $pathTemporaryFilename;
    $data['size']     = $uploadOffset;
    $data['md5']      = $streamChecksum ? hash_final($hashctx) : '';

    return $data;
    }

  /** Get the amount of data already uploaded */
  public function getOffset($args)
    {
    //check parameters
    if(!array_key_exists($this->tokenParamName, $args))
      {
      throw new Exception('Parameter '.$this->tokenParamName.' is not defined', -150);
      }
    $uploadToken = $args[$this->tokenParamName];
    $offset = filesize($this->tmpDirectory.'/'.$uploadToken);
    return array('offset' => $offset);
    }
  }
