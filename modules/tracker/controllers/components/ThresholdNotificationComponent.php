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

/** Component for performing threshold notifications */
class Tracker_ThresholdNotificationComponent extends AppComponent
  {
  /**
   * Add scheduled tasks for emailing users that the threshold was crossed
   */
  public function scheduleNotifications($scalar, $notifications)
    {
    $jobModel = MidasLoader::loadModel('Job', 'scheduler');
    foreach($notifications as $notification)
      {
      $job = MidasLoader::newDao('JobDao', 'scheduler');
      $job->setTask('TASK_TRACKER_SEND_THRESHOLD_NOTIFICATION');
      $job->setPriority(1);
      $job->setRunOnlyOnce(1);
      $job->setFireTime(date('Y-m-j G:i:s'));
      $job->setTimeInterval(0);
      $job->setStatus(SCHEDULER_JOB_STATUS_TORUN);
      $job->setCreatorId($notification->getRecipientId());
      $job->setParams(JsonComponent::encode(array('notification' => $notification,
                                                  'scalar' => $scalar)));
      $jobModel->save($job);
      }
    }
  } // end class
