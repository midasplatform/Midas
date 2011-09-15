<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
