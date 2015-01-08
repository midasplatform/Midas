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

/**
 * Job DAO for the scheduler module.
 *
 * @method int getJobId()
 * @method void setJobId(int $jobId)
 * @method string getTask()
 * @method void setTask(string $task)
 * @method int getRunOnlyOnce()
 * @method void setRunOnlyOnce(int $runOnlyOnce)
 * @method string getFireTime()
 * @method void setFireTime(string $fireTime)
 * @method string getTimeLastFired()
 * @method void setTimeLastFired(string $timeLastFired)
 * @method int getTimeInterval()
 * @method void setTimeInterval(int $timeInterval)
 * @method int getPriority()
 * @method void setPriority(int $priority)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method string getParams()
 * @method void setParams(string $params)
 * @method int getCreatorId()
 * @method void setCreatorId(int $creatorId)
 * @method array getLogs()
 * @method void setLogs(array $logs)
 * @method UserDao getCreator()
 * @method void setCreator(UserDao $creator)
 * @package Modules\Scheduler\DAO
 */
class Scheduler_JobDao extends Scheduler_AppDao
{
    /** @var string */
    public $_model = 'Job';

    /** @var string */
    public $_module = 'scheduler';
}
