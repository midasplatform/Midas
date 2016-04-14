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
 * Upgrade the tracker module to version 2.0.0.
 */
class Tracker_Upgrade_2_0_0 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        // THIS UPGRADE DOES NOTHING. TODO(cpatrick).
        // ../mysql/upgrade_to_2.0.0.sql should be run after installing the
        // four stored procedures (all in ../mysql/ and named the same):
        //    create_submissions
        //    migrate_params
        //    migrate_items_to_submissions
        //    scalar_to_submission
    }
}
