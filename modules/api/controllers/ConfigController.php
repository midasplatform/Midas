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

/** api config controller */
class Api_ConfigController extends Api_AppController
{
  public $_models = array('User');
  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Date');
  public $_moduleModels = array('Userapi');

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

    $form = $this->ModuleForm->Config->createKeyForm();
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
      $userapiDao = $this->Api_Userapi->createKey($user, $applicationName, $tokenExperiationTime);
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
      $userapiDao = $this->Api_Userapi->load($element);
      // Make sure the key belongs to the user
      if($userapiDao != false && ($userapiDao->getUserId() == $userId || $this->userSession->Dao->isAdmin()))
        {
        $this->Api_Userapi->delete($userapiDao);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        }
      else
        {
        echo JsonComponent::encode(array(false, $this->t('Error')));
        }
      }

    // List the previously generated API keys
    $apikeys = array();
    $userapiDaos = $this->Api_Userapi->getByUser($user);
    $this->view->userapiDaos = $userapiDaos;
    $this->view->user = $user;
    }

  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();

    if(file_exists(BASE_PATH."/core/configs/api.local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/api.local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/api/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();

    $formArray = $this->getFormAsArray($configForm);
    $formArray['methodprefix']->setValue($applicationConfig['global']['methodprefix']);

    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH."/core/configs/api.local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/api.local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/api.local.ini"))
          {
          rename(BASE_PATH."/core/configs/api.local.ini", BASE_PATH."/core/configs/api.local.ini.old");
          }
        $applicationConfig['global']['methodprefix'] = $this->_getParam('methodprefix');
        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/api.local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }

}//end class
