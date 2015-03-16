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

/**
 * Condor job DAO for the batchmake module.
 *
 * @method int getCondorJobId()
 * @method void CondorJobId(int $condorJobId)
 * @method int getCondorDagId()
 * @method void CondorDagId(int $condorDagId)
 * @method string getJobdefinitionFilename()
 * @method void setJobdefinitionFilename(string $jobdefinitionFilename)
 * @method string getOutputFilename()
 * @method void setOutputFilename(string $outputFilename)
 * @method string getErrorFilename()
 * @method void setErrorFilename(string $errorFilename)
 * @method string getLogFilename()
 * @method void setLogFilename(string $logFilename)
 * @method string getPostFilename()
 * @method void setPostFilename(string $postFilename)
 * @package Modules\Batchmake\DAO
 */
class Batchmake_CondorJobDao extends AppDao
{
    /** @var string */
    public $_model = 'CondorJob';

    /** @var string */
    public $_module = 'batchmake';
}
