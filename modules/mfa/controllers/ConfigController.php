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

/** Config controller for MFA module */
class Mfa_ConfigController extends Mfa_AppController
{
  public $_models = array('Setting', 'User');
  public $_moduleModels = array('Otpdevice');
  public $_moduleDaos = array('Otpdevice');
  public $_moduleForms = array();
  public $_components = array();

  /**
   * Admin config page
   */
  function indexAction()
    {
    $userOtpSetting = $this->Setting->GetValueByName('userOtpControl', 'mfa');
    $this->view->userOtpControl = $userOtpSetting === 'true';
    }

  /**
   * Form submission handler for admin config page
   * @param userOtpControl (checkbox value) non empty string will make this true
   */
  function submitAction()
    {
    $this->disableLayout();
    $this->disableView();

    $userOtpControl = $this->_getParam('userOtpControl');
    $value = $userOtpControl ? 'true' : 'false';
    $this->Setting->setConfig('userOtpControl', $value, 'mfa');

    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Changes saved'));
    }

  /**
   * User configuration tab for OTP parameters
   * @param userId The id of the user to edit
   */
  function usertabAction()
    {
    $this->disableLayout();
    $userId = $this->_getParam('userId');
    $userOtpSetting = $this->Setting->GetValueByName('userOtpControl', 'mfa');
    $userOtpControl = $userOtpSetting === 'true';
    if(!$userOtpControl && !$this->userSession->Dao->isAdmin())
      {
      throw new Zend_Exception('Only administrators are allowed to manage OTP settings');
      }
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a userId parameter');
      }
    $user = $this->User->load($userId);
    if(!$user)
      {
      throw new Zend_Exception('Invalid userId');
      }
    $currentUser = $this->userSession->Dao;
    if(!$currentUser)
      {
      throw new Zend_Exception('Must be logged in');
      }
    if($currentUser->getKey() != $user->getKey() && !$currentUser->isAdmin())
      {
      throw new Zend_Exception('Permission denied');
      }
    $otpDevice = $this->Mfa_Otpdevice->getByUser($user);
    if($otpDevice)
      {
      $this->view->useOtp = true;
      $this->view->secret = $otpDevice->getSecret();
      $this->view->algorithm = $otpDevice->getAlgorithm();
      $this->view->length = $otpDevice->getLength();
      }
    else
      {
      $this->view->useOtp = false;
      $this->view->secret = '';
      $this->view->algorithm = '';
      $this->view->length = '';
      }
    $this->view->user = $user;
    $this->view->algList = array(
      MIDAS_MFA_PAM => 'PAM');
    } 

  /**
   * The form for user OTP device configuration submits to this action.
   * @param userId The user id to check
   * @param useOtp If set, enable OTP device, otherwise delete OTP device record
   * @param algorithm The OTP algorithm to use (see constants.php)
   * @param secret The device key or secret to use
   * @param length The length of the client tokens
   */
  function usersubmitAction()
    {
    $this->disableLayout();
    $this->disableView();

    $userOtpSetting = $this->Setting->GetValueByName('userOtpControl', 'mfa');
    $userOtpControl = $userOtpSetting === 'true';
    if(!$userOtpControl && !$this->userSession->Dao->isAdmin())
      {
      throw new Zend_Exception('Only administrators are allowed to manage OTP settings');
      }

    $userId = $this->_getParam('userId');
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a userId parameter');
      }
    $user = $this->User->load($userId);
    if(!$user)
      {
      throw new Zend_Exception('Invalid userId');
      }
    $currentUser = $this->userSession->Dao;
    if(!$currentUser)
      {
      throw new Zend_Exception('Must be logged in');
      }
    if($currentUser->getKey() != $user->getKey() && !$currentUser->isAdmin())
      {
      throw new Zend_Exception('Permission denied');
      }
    $otpDevice = $this->Mfa_Otpdevice->getByUser($user);
    $useOtp = $this->_getParam('useOtp');
    if(!isset($useOtp))
      {
      if($otpDevice)
        {
        $this->Mfa_Otpdevice->delete($otpDevice);
        }
      echo JsonComponent::encode(array('status' => 'warning', 'message' => 'OTP Authentication disabled'));
      }
    else
      {
      if(!$otpDevice)
        {
        $otpDevice = new Mfa_OtpdeviceDao();
        $otpDevice->setUserId($user->getKey());
        $otpDevice->setCounter('0');
        }
      $otpDevice->setAlgorithm($this->_getParam('algorithm'));
      $otpDevice->setSecret($this->_getParam('secret'));
      $otpDevice->setLength($this->_getParam('length'));
      $this->Mfa_Otpdevice->save($otpDevice);
      echo JsonComponent::encode(array('status' => 'ok', 'message' => 'OTP Authentication enabled'));
      }
    }
}//end class

