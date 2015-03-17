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

/** Notification manager for the mail module. */
class Mail_Notification extends MIDAS_Notification
{
    /** @var string */
    public $moduleName = 'mail';

    /** @var array */
    public $_models = array('Setting');

    /** Initialize the notification process */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_SEND_MAIL_MESSAGE', 'handleSendMailMessage');
    }

    /**
     * Send mail message handler.
     *
     * @param array $params parameters
     * @return bool true on success
     */
    protected function handleSendMailMessage($params)
    {
        $provider = $this->Setting->getValueByName(MAIL_PROVIDER_KEY, $this->moduleName);

        if ($provider === MAIL_PROVIDER_APP_ENGINE) {
            $service = new Midas_Service_AppEngine_Mail();
            $transport = new Midas_Mail_Transport_Service($service);
        } elseif ($provider === MAIL_PROVIDER_SEND_GRID) {
            $username = $this->Setting->getValueByName(MAIL_SEND_GRID_USERNAME_KEY, $this->moduleName);
            $password = $this->Setting->getValueByName(MAIL_SEND_GRID_PASSWORD_KEY, $this->moduleName);
            $service = new Midas_Service_SendGrid_Mail($username, $password);
            $transport = new Midas_Mail_Transport_Service($service);
        } elseif ($provider === MAIL_PROVIDER_SMTP) {
            $host = $this->Setting->getValueByName(MAIL_SMTP_HOST_KEY, $this->moduleName);
            $port = $this->Setting->getValueByName(MAIL_SMTP_PORT_KEY, $this->moduleName);
            $ssl = $this->Setting->getValueByName(MAIL_SMTP_USE_SSL_KEY, $this->moduleName);
            $username = $this->Setting->getValueByName(MAIL_SMTP_USERNAME_KEY, $this->moduleName);
            $password = $this->Setting->getValueByName(MAIL_SMTP_PASSWORD_KEY, $this->moduleName);
            $config = array();

            if (!empty($port)) {
                $config['port'] = $port;
            }

            if ($ssl === '1') {
                $config['ssl'] = 'tls';
            }

            if (!empty($username) && !empty($password)) {
                $config['auth'] = 'login';
                $config['username'] = $username;
                $config['password'] = $password;
            }

            $transport = new Zend_Mail_Transport_Smtp($host, $config);

        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }

        $mail = new Midas_Mail();
        $mail->setFrom(htmlspecialchars($this->Setting->getValueByName(MAIL_FROM_ADDRESS_KEY, $this->moduleName), ENT_QUOTES, 'UTF-8'));

        if (isset($params['bcc'])) {
            $mail->addBcc(htmlspecialchars($params['bcc'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($params['cc'])) {
            $mail->addCc(htmlspecialchars($params['cc'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($params['html'])) {
            $mail->setBodyHtml($params['html']);
        }

        if (isset($params['subject'])) {
            $mail->setSubject(htmlspecialchars($params['subject'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($params['text'])) {
            $mail->setBodyText(htmlspecialchars($params['text'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($params['to'])) {
            $mail->addTo(htmlspecialchars($params['to'], ENT_QUOTES, 'UTF-8'));
        }

        try {
            $mail->send($transport);
        } catch (Zend_Exception $exception) {
            return false;
        }

        return true;
    }
}
