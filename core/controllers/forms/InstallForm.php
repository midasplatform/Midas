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

/** Install forms*/
class InstallForm extends AppForm
  {
  /** create  form */
  public function createDBForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/install/step2')
         ->setMethod('post');
    $type = new Zend_Form_Element_Hidden('type');

    $host = new Zend_Form_Element_Text('host');
    $host->setValue('localhost');

    $port = new Zend_Form_Element_Text('port');
    $port->addValidator('Digits', true);

    $unixsocket = new Zend_Form_Element_Text('unix_socket');

    $dbname = new Zend_Form_Element_Text('dbname');
    $dbname->setRequired(true)
           ->addValidator('NotEmpty', true)
           ->setValue('midas');

    $username = new Zend_Form_Element_Text('username');
    $username->setRequired(true)
             ->addValidator('NotEmpty', true);

    $password = new Zend_Form_Element_Password('password');

    $firstname = new Zend_Form_Element_Text('firstname');
    $firstname->setRequired(true)
              ->addValidator('NotEmpty', true);

    $lastname = new Zend_Form_Element_Text('lastname');
    $lastname->setRequired(true)
             ->addValidator('NotEmpty', true);

    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator('EmailAddress');

    $userpassword1 = new Zend_Form_Element_Password('userpassword1');
    $userpassword1->addValidator('NotEmpty', true)
                  ->setRequired(true);

    $userpassword2 = new Zend_Form_Element_Password('userpassword2');
    $userpassword2->addValidator('NotEmpty', true)
                  ->setRequired(true);

    $gravatar = new Zend_Form_Element_Checkbox('gravatar');

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit->setLabel('Setup database and account');

    $form->addElements(array($type, $host, $port, $unixsocket, $dbname, $username, $password, $firstname, $lastname,  $email, $userpassword1, $userpassword2, $gravatar, $submit));
    return $form;
    }

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/install/step3')
         ->setMethod('post');

    $lang = new Zend_Form_Element_Select('lang');
    $lang->addMultiOptions(array(
                    'en' => 'English',
                    'fr' => 'French'
                        ));

    $process = new Zend_Form_Element_Select('process');
    $process->addMultiOptions(array(
                    'onthefly' => 'On the fly',
                    'cron' => 'External'
                        ));

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
                    'UTC' => 'UTC'
                        ));

    $environment = new Zend_Form_Element_Select('environment');
    $environment->addMultiOptions(array(
                    'production' => 'Production',
                    'development' => 'Development'
                        ));

    $description = new Zend_Form_Element_Textarea('description');

    $name = new Zend_Form_Element_Text('name');
    $name->setRequired(true)
         ->addValidator('NotEmpty', true);

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit->setLabel('Create configuration');

    $form->addElements(array($description, $process, $timezone, $environment, $lang, $name, $submit));
    return $form;
    }
  }
