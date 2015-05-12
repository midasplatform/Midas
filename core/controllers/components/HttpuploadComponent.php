<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

// HTTPUPLOAD error codes
define('MIDAS_HTTPUPLOAD_UPLOAD_FAILED', -105);
define('MIDAS_HTTPUPLOAD_UPLOAD_TOKEN_GENERATION_FAILED', -140);
define('MIDAS_HTTPUPLOAD_INVALID_UPLOAD_TOKEN', -141);
define('MIDAS_HTTPUPLOAD_INPUT_FILE_OPEN_FAILED', -142);
define('MIDAS_HTTPUPLOAD_OUTPUT_FILE_OPEN_FAILED', -143);
define('MIDAS_HTTPUPLOAD_TMP_DIR_CREATION_FAILED', -144);
define('MIDAS_HTTPUPLOAD_PARAM_UNDEFINED', -150);

/**
 * This component is used for large uploads and is used by
 * the web api and the java uploader.  It generates an authenticated
 * upload token that can be used to start or resume an upload.
 */
class HttpuploadComponent extends AppComponent
{
    /** @var string */
    public $tokenParamName = 'uploadtoken';

    /** @var bool */
    public $testingEnable = false;

    /**
     * Set whether we are in testing mode or not (boolean).
     *
     * @param bool $testing
     */
    public function setTestingMode($testing)
    {
        $this->testingEnable = $testing;
    }

    /**
     * Set the name of the uploadtoken parameter that is being passed.
     *
     * @param string $name
     */
    public function setTokenParamName($name)
    {
        $this->tokenParamName = $name;
    }

    /**
     * Generate an upload token that will act as the authentication token for the upload.
     * This token is the filename of a unique file which will be placed under the
     * directory specified by the dirname parameter, which should be used to ensure that
     * the user can only write into a certain logical space.
     *
     * @param array $args
     * @param string $dirname
     * @return array
     * @throws Exception
     */
    public function generateToken($args, $dirname = '')
    {
        if (!array_key_exists('filename', $args)) {
            throw new Exception('Parameter filename is not defined', MIDAS_HTTPUPLOAD_FILENAME_PARAM_UNDEFINED);
        }

        $tempDirectory = UtilityComponent::getTempDirectory();
        $dir = $dirname === '' ? '' : '/'.$dirname;
        $dir = $tempDirectory.$dir;

        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception('Failed to create temporary upload dir', MIDAS_HTTPUPLOAD_TMP_DIR_CREATION_FAILED);
            }
        }
        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $uniqueIdentifier = $randomComponent->generateString(64);
        if ($dirname != '') {
            $uniqueIdentifier = $dirname.'/'.$uniqueIdentifier;
        }

        $path = $tempDirectory.'/'.$uniqueIdentifier;
        if (file_exists($path)) {
            throw new Exception('Failed to generate upload token', MIDAS_HTTPUPLOAD_UPLOAD_TOKEN_GENERATION_FAILED);
        }

        if (touch($path) === false) {
            mkdir($path, 0777, true);
            $uniqueIdentifier .= '/';
        }

        return array('token' => $uniqueIdentifier);
    }

    /**
     * Handle the upload.
     *
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function process($args)
    {
        if (!array_key_exists('filename', $args)) {
            throw new Exception('Parameter filename is not defined', MIDAS_HTTPUPLOAD_PARAM_UNDEFINED);
        }
        $filename = $args['filename'];

        if (!array_key_exists($this->tokenParamName, $args)) {
            throw new Exception(
                'Parameter '.$this->tokenParamName.' is not defined',
                MIDAS_HTTPUPLOAD_PARAM_UNDEFINED
            );
        }
        $uploadToken = $args[$this->tokenParamName];

        if (!array_key_exists('length', $args)) {
            throw new Exception('Parameter length is not defined', MIDAS_HTTPUPLOAD_PARAM_UNDEFINED);
        }
        $length = (int) ($args['length']);

        if ($this->testingEnable && array_key_exists('localinput', $args)) {
            $localInput = array_key_exists('localinput', $args) ? $args['localinput'] : false;
        }

        $temporaryPath = UtilityComponent::getTempDirectory().'/'.$uploadToken;
        if (!file_exists($temporaryPath)) {
            throw new Exception(
                'Invalid upload token '.$uploadToken, MIDAS_HTTPUPLOAD_INVALID_UPLOAD_TOKEN
            );
        }

        if (substr($temporaryPath, -1) === '/') {
            @rmdir($temporaryPath);
        }

        $uploadOffset = file_exists($temporaryPath) ? UtilityComponent::fileSize($temporaryPath) : 0;

        // can't do streaming checksum if we have a partial file already.
        $streamChecksum = $uploadOffset === 0;

        ignore_user_abort(true);

        // open target output
        $out = fopen($temporaryPath, 'ab'); // Stream (Server -> TempFile) Mode: Append, Binary
        if ($out === false) {
            throw new Exception(
                'Failed to open output file ['.$temporaryPath.']',
                MIDAS_HTTPUPLOAD_OUTPUT_FILE_OPEN_FAILED
            );
        }

        $inputFile = 'php://input'; // Stream (Client -> Server) Mode: Read, Binary
        if ($this->testingEnable && array_key_exists('localinput', $args)) {
            $inputFile = $localInput; // Stream (LocalServerFile -> Server) Mode: Read, Binary
        }

        $in = fopen($inputFile, 'rb'); // Stream (LocalServerFile -> Server) Mode: Read, Binary
        if ($in === false) {
            fclose($out);
            throw new Exception('Failed to open ['.$inputFile.'] source', MIDAS_HTTPUPLOAD_INPUT_FILE_OPEN_FAILED);
        }

        if ($streamChecksum) {
            $hashContext = hash_init('md5');
        } else {
            $hashContext = null;
        }

        // read from input and write into file
        $bufSize = 5242880;
        $bufSize = $length < $bufSize ? $length : $bufSize;
        while (connection_status() == CONNECTION_NORMAL && $uploadOffset < $length && ($buf = fread($in, $bufSize))) {
            $uploadOffset += strlen($buf);
            fwrite($out, $buf);
            if ($length - $uploadOffset < $bufSize) {
                $bufSize = $length - $uploadOffset;
            }
            if ($streamChecksum) {
                hash_update($hashContext, $buf);
            }
        }
        fclose($in);
        fclose($out);

        if ($uploadOffset < $length) {
            throw new Exception(
                'Failed to upload file - '.$uploadOffset.'/'.$length.' bytes transferred',
                MIDAS_HTTPUPLOAD_UPLOAD_FAILED
            );
        }

        $data['filename'] = $filename;
        $data['path'] = $temporaryPath;
        $data['size'] = $uploadOffset;
        $data['md5'] = $streamChecksum ? hash_final($hashContext) : '';

        return $data;
    }

    /**
     * Get the amount of data already uploaded.
     *
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function getOffset($args)
    {
        // check parameters
        if (!array_key_exists($this->tokenParamName, $args)) {
            throw new Exception(
                'Parameter '.$this->tokenParamName.' is not defined',
                MIDAS_HTTPUPLOAD_PARAM_UNDEFINED
            );
        }
        $uploadToken = $args[$this->tokenParamName];
        $offset = UtilityComponent::fileSize(UtilityComponent::getTempDirectory().'/'.$uploadToken);

        return array('offset' => $offset);
    }
}
