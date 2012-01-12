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
  public function createDBForm($type)
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/install/step2')
          ->setMethod('post');
    $type = new Zend_Form_Element_Hidden('type');

    $host = new Zend_Form_Element_Text('host');
    $host
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setValue('localhost');

    $username = new Zend_Form_Element_Text('username');
    $username
          ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $password = new Zend_Form_Element_Password('password');

    $dbname = new Zend_Form_Element_Text('dbname');
    $dbname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setValue('midas');

    $port = new Zend_Form_Element_Text('port');
    $port
          ->setRequired(true)
          ->addValidator('NotEmpty', true);


    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator('EmailAddress');

    $userpassword1 = new Zend_Form_Element_Password('userpassword1');
    $userpassword1
             ->addValidator('NotEmpty', true)
             ->setRequired(true);

    $userpassword2 = new Zend_Form_Element_Password('userpassword2');
    $userpassword2
             ->addValidator('NotEmpty', true)
             ->setRequired(true);

    $firstname = new Zend_Form_Element_Text('firstname');
    $firstname
          ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $lastname = new Zend_Form_Element_Text('lastname');
    $lastname
          ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel('Set up database');

    $form->addElements(array($port, $type, $host, $username, $password, $dbname, $submit, $lastname, $firstname, $userpassword2, $userpassword1, $email));
    return $form;
    } //end createDBForm

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/install/step3')
          ->setMethod('post');

    $lang = new Zend_Form_Element_Select('lang');
    $lang ->addMultiOptions(array(
                    'en' => 'English',
                    'fr' => 'French'
                        ));

    $process = new Zend_Form_Element_Select('process');
    $process ->addMultiOptions(array(
                    'onthefly' => 'On the fly',
                    'cron' => 'External'
                        ));

    $timezone = new Zend_Form_Element_Select('timezone');
    $timezone ->addMultiOptions(array(
                    'America/New_York' => 'America/New_York',
                    'Europe/Paris' => 'Europe/Paris'
                        ));

    $environment = new Zend_Form_Element_Select('environment');
    $environment ->addMultiOptions(array(
                    'production' => 'Production',
                    'development' => 'Development'
                        ));

    $description = new Zend_Form_Element_Textarea('description');

    $keywords = new Zend_Form_Element_Textarea('keywords');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $smartoptimizer = new Zend_Form_Element_Checkbox("smartoptimizer");


    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel('Create configuration');

    $form->addElements(array($keywords, $description, $process, $timezone, $environment, $lang, $name, $smartoptimizer, $submit));
    return $form;
    }
} // end class