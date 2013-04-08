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

/** Apikey controller */
class Rest_ApikeyController extends AppController
{
  public $_models = array('User', 'Userapi');
  public $_forms = array('Api');
  public $_components = array('Date');

  /**
   * Configuration action for a user's api keys
   * @param userId The id of the user to display
   */
  function usertabAction()
    {
    $this->disableLayout();
    if(!$this->logged)
      {
      throw new Zend_Exception('Please Log in');
      }

    $userId = $this->_getParam('userId');
    if($this->userSession->Dao->getKey() != $userId &&
       !$this->userSession->Dao->isAdmin())
      {
      throw new Zend_Exception('Only admins can view other user api keys');
      }
    $user = $this->User->load($userId);

    $this->view->Date = $this->Component->Date;

    $form = $this->Form->Api->createKeyForm();
    $formArray = $this->getFormAsArray($form);
    $formArray['expiration']->setValue('100');
    $this->view->form = $formArray;
    // Create a new API key
    $createAPIKey = $this->_getParam('createAPIKey');
    $deleteAPIKey = $this->_getParam('deleteAPIKey');
    if(isset($createAPIKey))
      {
      $this->disableView();
      $applicationName      = $this->_getParam('appplication_name');
      $tokenExperiationTime = $this->_getParam('expiration');
      $userapiDao = $this->Userapi->createKey($user, $applicationName, $tokenExperiationTime);
      if($userapiDao != false)
        {
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        }
      else
        {
        echo JsonComponent::encode(array(false, $this->t('Error')));
        }
      }
    else if(isset($deleteAPIKey))
      {
      $this->disableView();
      $element = $this->_getParam('element');
      $userapiDao = $this->Userapi->load($element);
      // Make sure the key belongs to the user
      if($userapiDao != false && ($userapiDao->getUserId() == $userId || $this->userSession->Dao->isAdmin()))
        {
        $this->Userapi->delete($userapiDao);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        }
      else
        {
        echo JsonComponent::encode(array(false, $this->t('Error')));
        }
      }

    // List the previously generated API keys
    $apikeys = array();
    $userapiDaos = $this->Userapi->getByUser($user);
    $this->view->userapiDaos = $userapiDaos;
    $this->view->user = $user;
    }

}//end class
