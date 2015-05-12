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
 * Threshold notification DAO for the tracker module.
 *
 * @method int getThresholdId()
 * @method void setThresholdId(int $thresholdId)
 * @method int getTrendId()
 * @method void setTrendId(int $trendId)
 * @method float getValue()
 * @method void setValue(float $value)
 * @method string getComparison()
 * @method void setComparison(string $comparison)
 * @method string getAction()
 * @method void setAction(string $action)
 * @method int getRecipientId()
 * @method void setRecipientId(int $recipientId)
 * @method Tracker_TrendDao getTrend()
 * @method void setTrend(Tracker_TrendDao $trendDao)
 * @method UserDao getRecipient()
 * @method void setRecipient(UserDao $recipient)
 */
class Tracker_ThresholdNotificationDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'ThresholdNotification';

    /** @var string */
    public $_module = 'tracker';
}
