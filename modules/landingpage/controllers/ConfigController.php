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

/** Config controller for the landingpage module */
class Landingpage_ConfigController extends Landingpage_AppController
  {
  public $_moduleForms = array('Config');
  public $_components = array('Utility');
  public $_moduleModels = array('Text');
  public $_moduleDaos = array('Text');

  /** index action */
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $textDaos = $this->Landingpage_Text->getAll();
    if(isset($textDaos[0]))
      {
      $textDao = $textDaos[0];
      }
    else
      {
      $textDao = new Landingpage_TextDao();
      $textDao->setText('Landing page content goes here. '.
                        'You may use plain text or markdown.');
      }
    $text = $textDao->getText();
    $configForm = $this->ModuleForm->Config->createForm();

    $formArray = $this->getFormAsArray($configForm);
    $formArray['text']->setValue($text);

    $this->view->configForm = $formArray;

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->getParam('submit');
      if(isset($submitConfig))
        {
        $landingpageText = $this->getParam('text');
        $textDao->setText($landingpageText);
        $this->Landingpage_Text->save($textDao);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    }
  } // end class
