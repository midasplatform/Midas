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

/** Admin form for the mail module. */
class Mail_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('mail_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('5qzSHzCdNuPfYaT99Jq5WcKe');
        $csrf->setDecorators(array('ViewHelper'));

        $provider = new Zend_Form_Element_Select(MAIL_PROVIDER_KEY);
        $provider->setLabel('Provider');
        $provider->setRequired(true);
        $provider->addValidator('NotEmpty', true);
        $provider->addMultiOptions(array(
            MAIL_PROVIDER_APP_ENGINE => 'Google App Engine',
            MAIL_PROVIDER_MAIL => 'PHP Mail Function',
            MAIL_PROVIDER_SEND_GRID => 'SendGrid Service',
            MAIL_PROVIDER_SMTP => 'External SMTP Server',
        ));

        $fromAddress = new Zend_Form_Element_Text(MAIL_FROM_ADDRESS_KEY);
        $fromAddress->setLabel('From email address');
        $fromAddress->setRequired(true);
        $fromAddress->addValidator('NotEmpty', true);
        $fromAddress->addValidator('EmailAddress', true);

        $addressVerification = new Zend_Form_Element_Checkbox(MAIL_ADDRESS_VERIFICATION_KEY);
        $addressVerification->setLabel('Require email address verification');

        $this->addDisplayGroup(array($provider, $fromAddress, $addressVerification), 'global');

        $sendGridUsername = new Zend_Form_Element_Text(MAIL_SEND_GRID_USERNAME_KEY);
        $sendGridUsername->setLabel('SendGrid User Name');
        $sendGridUsername->addValidator('NotEmpty', true);

        $sendGridPassword = new Zend_Form_Element_Password(MAIL_SEND_GRID_PASSWORD_KEY);
        $sendGridPassword->setLabel('SendGrid Password');
        $sendGridPassword->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($sendGridUsername, $sendGridPassword), 'send_grid');

        $smtpHost = new Zend_Form_Element_Text(MAIL_SMTP_HOST_KEY);
        $smtpHost->setLabel('Server name');
        $smtpHost->addValidator('NotEmpty', true);
        $smtpHost->addValidator('Hostname', true);

        $smtpPort = new Zend_Form_Element_Text(MAIL_SMTP_PORT_KEY);
        $smtpPort->setLabel('Port');
        $smtpPort->addValidator('NotEmpty', true);
        $smtpPort->addValidator('Digits', true);
        $smtpPort->addValidator('Between', true, array('min' => 1, 'max' => 65535));
        $smtpPort->setAttrib('maxlength', 5);

        $smtpUseSsl = new Zend_Form_Element_Checkbox(MAIL_SMTP_USE_SSL_KEY);
        $smtpUseSsl->setLabel('Use SSL');

        $smtpUsername = new Zend_Form_Element_Text(MAIL_SMTP_USERNAME_KEY);
        $smtpUsername->setLabel('User name');
        $smtpUsername->addValidator('NotEmpty', true);

        $smtpPassword = new Zend_Form_Element_Password(MAIL_SMTP_PASSWORD_KEY);
        $smtpPassword->setLabel('Password');
        $smtpPassword->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($smtpHost, $smtpPort, $smtpUseSsl, $smtpUsername, $smtpPassword), 'smtp');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $provider, $fromAddress, $addressVerification, $smtpHost, $smtpPort, $smtpUseSsl, $smtpUsername, $smtpPassword, $submit));
    }
}
