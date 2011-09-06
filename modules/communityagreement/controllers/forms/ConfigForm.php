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

/** Community Agreement forms*/

class Communityagreement_ConfigForm extends AppForm
{
  /** create create agreement form */
  public function createCreateAgreementForm($community_id)
    {
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/communityagreement/config/agreementtab?communityId='.$community_id)
         ->setMethod('post');
    $agreement = new Zend_Form_Element_Textarea('agreement');
   
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit->setLabel($this->t("Save"));
    
    $form->addElements(array($agreement, $submit));
    return $form;
    }
    
} // end class
?>
