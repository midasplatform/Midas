<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Admin form for the oai module. */
class Oai_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('oai_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('YTVEA8QwsJqFaPfcnEugjKrM');
        $csrf->setDecorators(array('ViewHelper'));

        $repositoryName = new Zend_Form_Element_Text(OAI_REPOSITORY_NAME_KEY);
        $repositoryName->setLabel('OAI Repository Name');
        $repositoryName->setRequired(true);
        $repositoryName->addValidator('NotEmpty', true);

        $repositoryIdentifier = new Zend_Form_Element_Text(OAI_REPOSITORY_IDENTIFIER_KEY);
        $repositoryIdentifier->setLabel('OAI Repository Identifier');
        $repositoryIdentifier->setRequired(true);
        $repositoryIdentifier->addValidator('NotEmpty', true);

        $adminEmail = new Zend_Form_Element_Text(OAI_ADMIN_EMAIL_KEY);
        $adminEmail->setLabel('Admin Email Address');
        $adminEmail->setRequired(true);
        $adminEmail->addValidator('NotEmpty', true);
        $adminEmail->addValidator('EmailAddress', true);

        $this->addDisplayGroup(array($repositoryName, $repositoryIdentifier, $adminEmail), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $repositoryName, $repositoryIdentifier, $adminEmail, $submit));
    }
}
