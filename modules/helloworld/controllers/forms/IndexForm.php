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

class Helloworld_IndexForm extends AppForm
{
  public function createIndexForm()
    {
   $form = new Zend_Form;

    $form->setAction("")
          ->setMethod('post');
 
    $name = new Zend_Form_Element_Text('name');
    $name
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setValue('Test');
    
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel('Submit');
     
    $form->addElements(array($name,$submit));
    return $form;
    }
} // end class
?>
