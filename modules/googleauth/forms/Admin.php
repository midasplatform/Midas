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

/** Admin form for the googleauth module. */
class Googleauth_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('googleauth_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('qsJm32258fFFcBRjbSHHu628');
        $csrf->setDecorators(array('ViewHelper'));

        $clientId = new Zend_Form_Element_Text(GOOGLE_AUTH_CLIENT_ID_KEY);
        $clientId->setLabel('Client ID');
        $clientId->setRequired(true);
        $clientId->addValidator('NotEmpty', true);

        $clientSecret = new Zend_Form_Element_Text(GOOGLE_AUTH_CLIENT_SECRET_KEY);
        $clientSecret->setLabel('Client Secret');
        $clientSecret->setRequired(true);
        $clientSecret->addValidator('NotEmpty', true);

        $additionalScopes = new Zend_Form_Element_Textarea(GOOGLE_AUTH_CLIENT_ADDITIONAL_SCOPES_KEY);
        $additionalScopes->setLabel('Additional Scopes (One per Line)');
        $additionalScopes->addValidator('NotEmpty', true);
        $additionalScopes->setAttrib('cols', '80');
        $additionalScopes->setAttrib('rows', '4');

        $this->addDisplayGroup(array($clientId, $clientSecret, $additionalScopes), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $clientId, $clientSecret, $additionalScopes, $submit));
    }
}
