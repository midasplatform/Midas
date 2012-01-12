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
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/admin/index')
          ->setMethod('post');

    $lang = new Zend_Form_Element_Select('lang');
    $lang ->addMultiOptions(array(
                    'en' => 'English',
                    'fr' => 'French'
                        ));

    $description = new Zend_Form_Element_Textarea('description');

    $keywords = new Zend_Form_Element_Textarea('keywords');

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

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $smartoptimizer = new Zend_Form_Element_Checkbox("smartoptimizer");
    $dynamichelp = new Zend_Form_Element_Checkbox("dynamichelp");


    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($dynamichelp, $keywords, $description, $timezone, $environment, $lang, $name, $smartoptimizer, $submit));
    return $form;
    }
} // end class