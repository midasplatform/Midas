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
    // TODO
    }
} // end class
