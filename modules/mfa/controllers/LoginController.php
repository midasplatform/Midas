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
  public $_moduleComponents = array('Otp');

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

    Zend_Session::start();
    $mfaSession = new Zend_Session_Namespace('Mfa_Temp_User');
    $user = $mfaSession->Dao;

    if(!isset($user) || !$user)
      {
      echo JsonComponent::encode(array('status' => 'error', 'message' => 'Session has expired, refresh and try again'));
      return;
      }

    $otpDevice = $this->Mfa_Otpdevice->getByUser($user);
    if(!$otpDevice)
      {
      throw new Zend_Exception('User does not have an OTP device');
      }
    $token = $this->_getParam('token');
    try
      {
      $valid = $this->ModuleComponent->Otp->authenticate($otpDevice, $token);
      }
    catch(Zend_Exception $exc)
      {
      $this->getLogger()->crit($exc->getMessage());
      echo JsonComponent::encode(array('status' => 'error', 'message' => $exc->getMessage()));
      return;
      }

    if($valid)
      {
      session_start();
      $authUser = new Zend_Session_Namespace('Auth_User');
      $authUser->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
      $authUser->Dao = $user;
      $authUser->lock();
      $this->getLogger()->info(__METHOD__ . " Log in : " . $user->getFullName());

      echo JsonComponent::encode(array('status' => 'ok'));
      }
    else
      {
      echo JsonComponent::encode(array('status' => 'error', 'message' => 'Incorrect token'));
      }
    }

  } // end class
