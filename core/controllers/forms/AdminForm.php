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

/** Admin forms*/
class AdminForm extends AppForm
  {
  /** create form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/admin/index')
         ->setMethod('post');

    $lang = new Zend_Form_Element_Select('lang');
    $lang->addMultiOptions(array(
      'en' => 'English',
      'fr' => 'French'));

    $description = new Zend_Form_Element_Textarea('description');

    $timezone = new Zend_Form_Element_Select('timezone');
    $timezone->addMultiOptions(array(
      'America/Anchorage' => 'America/Anchorage',
      'America/Chicago' => 'America/Chicago',
      'America/Denver' => 'America/Denver',
      'America/Los_Angeles' => 'America/Los Angeles',
      'America/New_York' => 'America/New York',
      'America/Phoenix' => 'America/Phoenix',
      'Europe/London' => 'Europe/London',
      'Europe/Paris' => 'Europe/Paris',
      'Pacific/Honolulu' => 'Pacific/Honolulu',
      'UTC' => 'UTC'));

    $environment = new Zend_Form_Element_Select('environment');
    $environment->addMultiOptions(array(
      'production' => 'Production',
      'development' => 'Development'));

    $name = new Zend_Form_Element_Text('name');
    $name->setRequired(true)
         ->addValidator('NotEmpty', true);

    $dynamichelp = new Zend_Form_Element_Checkbox('dynamichelp');
    $gravatar = new Zend_Form_Element_Checkbox('gravatar');
    $closeRegistration = new Zend_Form_Element_Checkbox('closeregistration');
    $verifyEmail = new Zend_Form_Element_Checkbox('verifyemail');
    $logtrace = new Zend_Form_Element_Checkbox('logtrace');

    $httpProxy = new Zend_Form_Element_Text('httpProxy');

    $smtpServer = new Zend_Form_Element_Text('smtpserver');
    $smtpUser = new Zend_Form_Element_Text('smtpuser');
    $smtpPassword = new Zend_Form_Element_Text('smtppassword');
    $smtpFromAddress = new Zend_Form_Element_Text('smtpfromaddress');

    $submit = new Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(
      array($dynamichelp, $description, $timezone, $environment, $gravatar,
      $lang, $name, $closeRegistration, $submit, $logtrace, $httpProxy,
      $smtpServer, $smtpUser, $smtpPassword, $smtpFromAddress, $verifyEmail));
    return $form;
    }
  } // end class
