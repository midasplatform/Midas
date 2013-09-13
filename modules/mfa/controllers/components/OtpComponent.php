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
      case MIDAS_MFA_PAM:
        return $this->_pamAuth($otpDevice, $token);
      case MIDAS_MFA_OATH_HOTP:
        return $this->_hotpAuth($otpDevice, $token);
      case MIDAS_MFA_RSA_SECURID:
        return $this->_securIdAuth($otpDevice, $token);
      case MIDAS_MFA_RADIUS:
        return $this->_radiusauth($otpDevice, $token);
      default:
        throw new Zend_Exception('Unknown OTP algorithm for user '.$otpDevice->getUserId());
      }
    }

  /**
   * Perform RSA SecurID Authentication
   * In the current implementation, we rely on a correctly configured PAM setup
   * on the server.
   */
  protected function _pamAuth($otpDevice, $token)
    {
    if(!function_exists('pam_auth'))
      {
      throw new Zend_Exception('PAM is not enabled on the server');
      }
    $err = '';
    return pam_auth($otpDevice->getSecret(), $token, $err, false);
    }

  /**
   * STUB: Perform OATH HOTP authentication
   */
  protected function _hotpAuth($otpDevice, $token)
    {
    return true;
    }

  /**
   * STUB: Perform RSA SecurID Authentication
   */
  protected function _securIdAuth($otpDevice, $token)
    {
    return true;
    }

  /**
   * Perform authentication using a RADIUS server.
   */
  protected function _radiusauth($otpDevice, $token)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $settingModel = $modelLoader->loadModel('Setting');

    $radiusserver = $settingModel->GetValueByName('radiusServer', 'mfa');
    $radiusport = $settingModel->GetValueByName('radiusPort', 'mfa');
    $radiuspw = $settingModel->GetValueByName('radiusPassword', 'mfa');
    $radiusTimeout = $settingModel->GetValueByName('radiusTimeout', 'mfa');
    $radiusMaxTries = $settingModel->GetValueByName('radiusMaxTries', 'mfa');

    if(!function_exists('radius_auth_open'))
      {
      throw new Zend_Exception('RADIUS is not enabled on the server');
      }

    $this->getLogger()->info("MIDAS RADIUS trying to authenticate user: " .
      $otpDevice->getSecret());

    $rh = radius_auth_open();
    if(!radius_add_server($rh, $radiusserver, $radiusport, $radiuspw,
      $radiusTimeout, $radiusMaxTries))
      {
        throw new Zend_Exception('Cannot connect to the RADIUS server: ' .
          radius_strerror($rh));
      }

    if(!radius_create_request($rh, RADIUS_ACCESS_REQUEST)) {
      throw new Zend_Exception('Cannot process requests to RADIUS server: ' .
        radius_strerror($rh));
    }


    /* this is the key parameter */
    radius_put_attr($rh, RADIUS_USER_NAME, $otpDevice->getSecret());

    /* this is the one time pin + 6-digit hard token or 8 digit smart token */
    radius_put_attr($rh, RADIUS_USER_PASSWORD, $token);

    switch (radius_send_request($rh))
      {
      case RADIUS_ACCESS_ACCEPT:
        $this->getLogger()->info("MIDAS RADIUS successful authentication " .
          "for " . $otpDevice->getSecret());
        return true;
      case RADIUS_ACCESS_REJECT:
        $this->getLogger()->info("MIDAS RADIUS failed authentication for " .
          $otpDevice->getSecret());
        return false;
      case RADIUS_ACCESS_CHALLENGE:
        $this->getLogger()->info("MIDAS RADIUS challenge requested for " .
          $otpDevice->getSecret());
        return false;
      default:
        $this->getLogger()->info("MIDAS RADIUS error during authentication " .
          "for " . $otpDevice->getSecret() . " with Token: " . $token .
          ". Error: ". radius_strerror($rh));
        throw new Zend_Exception('Error during RADIUS authentication: ' .
          radius_strerror($rh));
      }
    }

}
