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

/**
 * Communityagreement_ConfigForm
 *
 * @category   Midas modules
 * @package    communityagreement
 * @copyright  Copyright (c) Kitware SAS. See Copyright.txt for details.
 */

class Communityagreement_ConfigForm extends AppForm
{
  /**
   * Create create_agreement form
   *
   * @param string $community_id
   * @return Zend_Form
   */
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
