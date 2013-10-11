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

require_once BASE_PATH.'/modules/mfa/models/base/OtpdeviceModelBase.php';

/**
 * PDO-level implementation of the OTP device model.
 */
class Mfa_OtpdeviceModel extends Mfa_OtpdeviceModelBase
{
  /**
   * Get the user's OTP device dao.
   * @param userDao The user dao
   * @return The Otpdevice dao corresponding to the user, or null if this user doesn't have one
   */
  function getByUser($userDao)
    {
    if($userDao == null) {
      return null;
    }
    $row = $this->database->fetchRow($this->database->select()->where('user_id = ?', $userDao->getKey()));
    return $this->initDao('Otpdevice', $row, 'mfa');
    }
}
