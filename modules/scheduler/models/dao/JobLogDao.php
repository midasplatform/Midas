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
 * Job log DAO for the scheduler module.
 *
 * @method int getLogId()
 * @method void setLogId(int $logId)
 * @method int getJobId()
 * @method void setJobId(int $jobId)
 * @method string getDate()
 * @method void setDate(string $date)
 * @method string getLog()
 * @method void setLog(string $log)
 * @method Scheduler_JobDao getJob()
 * @method void setJob(Scheduler_JobDao $job)
 */
class Scheduler_JobLogDao extends Scheduler_AppDao
{
    /** @var string */
    public $_model = 'JobLog';

    /** @var string */
    public $_module = 'scheduler';
}
