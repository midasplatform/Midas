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

/** Folder forms*/
class FolderForm extends AppForm
{
  /** create edit folder form */
  public function createEditForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/folder/edit')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setAttribs(array('placeholder' => $this->t('Name of the folder'), 'autofocus' => 'autofocus', 'required' => 'required'))
          ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $description = new Zend_Form_Element_Textarea('description');
    $description ->setAttribs(array('placeholder' => $this->t('Optional')));
    $teaser = new Zend_Form_Element_Text('teaser');
    $teaser ->setAttribs(array('placeholder' => $this->t('Optional')));
    $teaser->setAttrib('MAXLENGTH', '250');
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save"));

    $form->addElements(array($name, $description, $submit, $teaser));
    return $form;
    }

} // end class
?>
