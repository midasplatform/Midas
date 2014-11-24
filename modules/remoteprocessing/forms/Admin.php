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

/** Admin form for the remoteprocessing module. */
class Remoteprocessing_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('remoteprocessing_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('fwqASktheDxMaZa3Byb4qSV3');
        $csrf->setDecorators(array('ViewHelper'));

        $securityKey = new Zend_Form_Element_Text(MIDAS_REMOTEPROCESSING_SECURITY_KEY_KEY);
        $securityKey->setLabel('Security Key');
        $securityKey->addValidator('NotEmpty', true);

        $showButton = new Zend_Form_Element_Checkbox(MIDAS_REMOTEPROCESSING_SHOW_BUTTON_KEY);
        $showButton->setLabel('Show Process Button');

        $this->addDisplayGroup(array($securityKey, $showButton), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $securityKey, $showButton, $submit));
    }
}
