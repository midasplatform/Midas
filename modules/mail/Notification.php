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
        $provider = $this->Setting->getValueByName('provider', $this->moduleName);

        if ($provider === 'app_engine') {
            $service = new Midas_Service_AppEngine_Mail();
            $transport = new Midas_Mail_Transport_Service($service);
        } elseif ($provider === 'send_grid') {
            $username = $this->Setting->getValueByName('send_grid_username', $this->moduleName);
            $password = $this->Setting->getValueByName('send_grid_password', $this->moduleName);
            $service = new Midas_Service_SendGrid_Mail($username, $password);
            $transport = new Midas_Mail_Transport_Service($service);
        } elseif ($provider = 'smtp') {
            $host = $this->Setting->getValueByName('smtp_host', $this->moduleName);
            $port = $this->Setting->getValueByName('smtp_port', $this->moduleName);
            $ssl = $this->Setting->getValueByName('smtp_use_ssl', $this->moduleName);
            $username = $this->Setting->getValueByName('smtp_username', $this->moduleName);
            $password = $this->Setting->getValueByName('smtp_password', $this->moduleName);
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
        $mail->setFrom($this->Setting->getValueByName('from_address', $this->moduleName));

        if (isset($params['bcc'])) {
            $mail->addBcc($params['bcc']);
        }

        if (isset($params['cc'])) {
            $mail->addCc($params['cc']);
        }

        if (isset($params['html'])) {
            $mail->setBodyHtml($params['html']);
        }

        if (isset($params['subject'])) {
            $mail->setSubject($params['subject']);
        }

        if (isset($params['text'])) {
            $mail->setBodyText($params['text']);
        }

        if (isset($params['to'])) {
            $mail->addTo($params['to']);
        }

        try {
            $mail->send($transport);
        } catch (Zend_Exception $exception) {
            return false;
        }

        return true;
    }
}