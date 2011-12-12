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

/** Upload forms*/
class UploadForm extends AppForm
{
  /** create upload link form */
  public function createUploadLinkForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/upload/savelink')
          ->setMethod('post');

    $validator = new Zend_Validate_Callback(array('Zend_Uri', 'check'));

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true);
    $url = new Zend_Form_Element_Text('url');
    $url  ->setValue('http://')
          ->setRequired(true)
          ->addValidator($validator)
          ->addValidator('NotEmpty', true);


    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save Link"))
            ->setAttrib('class', 'globalButton');

    $form->addElements(array($name, $url, $submit));
    return $form;
    }

} // end class