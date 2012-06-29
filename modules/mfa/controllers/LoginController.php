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

/** Login controller for MFA module */
class Mfa_LoginController extends Mfa_AppController
{
  public $_models = array('User');
  public $_moduleModels = array('Otpdevice');
  public $_moduleComponents = array();

  /**
   * Renders the dialog for the user to enter his or her OTP
   */
  function dialogAction()
    {
    $this->disableLayout();

    Zend_Session::start();
    $mfaSession = new Zend_Session_Namespace('Mfa_Temp_User');
    $user = $mfaSession->Dao;
    
    $otpDevice = $this->Mfa_Otpdevice->getByUser($user);
    if(!$otpDevice)
      {
      throw new Zend_Exception('User '.$user->getKey().' is not an OTP device user');
      }
    $this->view->user = $user;
    } 

  /**
   * When the user actually submits their otp, this authenticates it
   */
  function submitAction()
    {
    $this->disableLayout();
    $this->disableView();
    // TODO call the OTP authentication component to finish the login process
    }

}//end class
