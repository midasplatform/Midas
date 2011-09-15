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
class ItemForm extends AppForm
{
  /** create edit folder form */
  public function createEditForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/item/edit')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $description = new Zend_Form_Element_Textarea('description');
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save"));

    $form->addElements(array($name, $description, $submit));
    return $form;
    }

} // end class
?>
