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
 * Batchmake_ConfigForm
 */
class Batchmake_ConfigForm extends AppForm
{
    /**
     * does what it says.
     */
    public function createConfigForm($configPropertiesRequirements)
    {
        $form = new Zend_Form();

        $form->setAction($this->webroot.'/batchmake/config/index')->setMethod('post');

        $formElements = array();
        foreach ($configPropertiesRequirements as $property => $requirements) {
            $textElement = new Zend_Form_Element_Text($property);
            $textElement->setRequired(true)->addValidator('NotEmpty', true);
            $formElements[] = $textElement;
        }

        $submit = new  Zend_Form_Element_Submit(MIDAS_BATCHMAKE_SUBMIT_CONFIG);
        $submit->setLabel($this->t(MIDAS_BATCHMAKE_SAVE_CONFIGURATION_STRING));
        $formElements[] = $submit;

        $form->addElements($formElements);

        return $form;
    }
}
