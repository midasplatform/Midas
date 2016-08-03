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
 * Submission DAO for the tracker module.
 *
 * @method int getSubmissionId()
 * @method void setSubmissionId(int $submissionId)
 * @method int getProducerId()
 * @method void setProducerId(int $producerId)
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUuid()
 * @method string setUuid(string $uuid)
 * @method array getScalars()
 * @method void setScalars(array $scalarDaos)
 * @method int getOfficial()
 * @method void setOfficial(int $official)
 * @method string getBuildResultsUrl()
 * @method void setBuildResultsUrl(string $buildResultsUrl)
 * @method string getParams()
 * @method void setParams(string $params)
 * @method string getExtraUrls()
 * @method void setExtraUrls(string $extraUrls)
 * @method string getBranch()
 * @method void setBranch(string $branch)
 * @method string getSubmitTime()
 * @method void setSubmitTime(string $submitTime)
 * @method string getProducerRevision()
 * @method void setProducerRevision(string $producerRevision)
 * @method string getReproductionCommand()
 * @method void setReproductionCommand(string $reproductionCommand)
 * @method string getDocument()
 * @method void setDocument(string $document)
 */
class Tracker_SubmissionDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'Submission';

    /** @var string */
    public $_module = 'tracker';
}
