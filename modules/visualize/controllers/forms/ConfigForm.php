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

class Visualize_ConfigForm extends AppForm
{
 
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/visualize/config/index')
          ->setMethod('post'); 

    $useparaview = new Zend_Form_Element_Checkbox("useparaview");  
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($useparaview, $submit));
    return $form;
    }
    
} // end class
?>