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

/** Admin form for the mail module. */
class Mail_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('mail_config');
        $this->setMethod('POST');

        $provider = new Zend_Form_Element_Select('provider');
        $provider->setLabel('Provider');
        $provider->setRequired(true);
        $provider->addValidator('NotEmpty', true);

        if (class_exists('\google\appengine\api\mail\Message', false)) {
            $provider->addMultiOption('app_engine', 'Google App Engine');
        }

        $provider->addMultiOptions(array(
            'mail' => 'PHP Mail Function',
            'send_grid' => 'SendGrid Service',
            'smtp' => 'External SMTP Server',
        ));

        $fromAddress = new Zend_Form_Element_Text('from_address');
        $fromAddress->setLabel('From email address');
        $fromAddress->setRequired(true);
        $fromAddress->addValidator('NotEmpty', true);
        $fromAddress->addValidator('EmailAddress', true);

        $addressVerification = new Zend_Form_Element_Checkbox('address_verification');
        $addressVerification->setLabel('Require email address verification');

        $this->addDisplayGroup(array($provider, $fromAddress, $addressVerification), 'global');

        $sendGridUsername = new Zend_Form_Element_Text('send_grid_username');
        $sendGridUsername->setLabel('SendGrid User Name');
        $sendGridUsername->addValidator('NotEmpty', true);

        $sendGridPassword = new Zend_Form_Element_Text('send_grid_password');
        $sendGridPassword->setLabel('SendGrid Password');
        $sendGridPassword->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($sendGridUsername, $sendGridPassword), 'send_grid');

        $smtpHost = new Zend_Form_Element_Text('smtp_host');
        $smtpHost->setLabel('Server name');
        $smtpHost->addValidator('NotEmpty', true);
        $smtpHost->addValidator('Hostname', true);

        $smtpPort = new Zend_Form_Element_Text('smtp_port');
        $smtpPort->setLabel('Port');
        $smtpPort->addValidator('NotEmpty', true);
        $smtpPort->addValidator('Digits', true);
        $smtpPort->addValidator('Between', array('min' => 1, 'max' => 65535));

        $smtpUseSsl = new Zend_Form_Element_Checkbox('smtp_use_ssl');
        $smtpUseSsl->setLabel('Use SSL');

        $smtpUsername = new Zend_Form_Element_Text('smtp_username');
        $smtpUsername->setLabel('User name');
        $smtpUsername->addValidator('NotEmpty', true);

        $smtpPassword = new Zend_Form_Element_Text('smtp_password');
        $smtpPassword->setLabel('Password');
        $smtpPassword->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($smtpHost, $smtpPort, $smtpUseSsl, $smtpUsername, $smtpPassword), 'smtp');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($provider, $fromAddress, $addressVerification, $smtpHost, $smtpPort, $smtpUseSsl, $smtpUsername, $smtpPassword, $submit));
    }
}
