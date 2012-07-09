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
 * This component performs the actual verification of a OTP token
 * for all of the supported OTP technologies.
 */
class Mfa_OtpComponent extends AppComponent
{
  /** Constructor */
  function __construct()
    {
    }

  /**
   * Call this function to perform the authentication if a user has an OTP device.
   * It will determine which technology to use and switch to the appropriate method accordingly.
   * @param otpDevice The Otpdevice_Dao correspoding to the user
   * @param token The current one-time password displayed on the device
   * @return true If authentication is successful, false otherwise
   */
  public function authenticate($otpDevice, $token)
    {
    $alg = $otpDevice->getAlgorithm();
    switch($alg)
      {
      case MIDAS_MFA_OATH_HOTP:
        return $this->_hotpAuth($otpDevice, $token);
      case MIDAS_MFA_RSA_SECURID:
        return $this->_securIdAuth($otpDevice, $token);
      default:
        throw new Zend_Exception('Unknown OTP algorithm for user '.$otpDevice->getUserId());
      }
    }

  /**
   * STUB: Perform OATH HOTP authentication
   */
  protected function _hotpAuth($otpDevice, $token)
    {
    return true;
    }

  /**
   * Perform RSA SecurID Authentication
   * In the current implementation, we rely on a correctly configured PAM setup
   * on the server.
   */
  protected function _securIdAuth($otpDevice, $token)
    {
    $err = '';
    return pam_auth($otpDevice->getSecret(), $token, $err, false);
    }
}
