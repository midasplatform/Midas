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

/**
 * Batchmake_ConfigForm
 */
class Batchmake_ConfigForm extends AppForm
{

  /**
   * @method createConfigForm
   * does what it says.
   */
  public function createConfigForm($configPropertiesRequirements)
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/batchmake/config/index')
          ->setMethod('post');

    $formElements = array();
    foreach($configPropertiesRequirements as $property => $requirements)
      {
      $textElement = new Zend_Form_Element_Text($property);
      $textElement->setRequired(true)->addValidator('NotEmpty', true);
      $formElements[] = $textElement;
      }


    $submit = new  Zend_Form_Element_Submit(MIDAS_BATCHMAKE_SUBMIT_CONFIG);
    $submit ->setLabel($this->t(MIDAS_BATCHMAKE_SAVE_CONFIGURATION_STRING));
    $formElements[] = $submit;


    $form->addElements($formElements);
    return $form;
    }
} // end class
?>
