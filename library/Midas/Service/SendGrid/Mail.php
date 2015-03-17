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

/** SendGrid mail service. */
class Midas_Service_SendGrid_Mail implements Midas_Service_Mail
{
    /** @var \SendGrid */
    protected $_client = null;

    /** @var array */
    protected $_config = array();

    /** @var string */
    protected $_key = null;

    /** @var string */
    protected $_user = null;

    /**
     * Constructor.
     *
     * @param string $user user
     * @param string $key key
     * @param array $config config
     */
    public function __construct($user, $key, array $config = array())
    {
        $this->_config = $config;
        $this->_key = $key;
        $this->_user = $user;

        // TODO: Implement __construct() method.
    }

    /**
     * Return the client.
     *
     * @return \SendGrid client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Set the client.
     *
     * @param \SendGrid $client
     */
    public function setClient(\SendGrid $client)
    {
        $this->_client = $client;
    }

    /**
     * Return the config.
     *
     * @return array config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Return the key.
     *
     * @return string key
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Return the user.
     *
     * @return string user
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Send the mail.
     *
     * @param Midas_Mail $mail mail instance
     * @throws Midas_Service_SendGrid_Exception
     */
    public function sendMail(Midas_Mail $mail)
    {
        if (is_null($this->_client)) {
            $this->_client = new \SendGrid($this->_user, $this->_key, $this->_config);
        }

        $email = new \SendGrid\Email();
        $email->setBccs($mail->getBcc());
        $email->setCcs($mail->getCc());
        $email->setHtml($mail->getBodyHtml(true));
        $email->setFrom($mail->getFrom());
        $email->setReplyTo($mail->getReplyTo());
        $email->setSubject($mail->getSubject());
        $email->setText($mail->getBodyText(true));
        $email->setTos($mail->getTo());

        $response = $this->_client->send($email);

        if ($response->code !== 200) {
            throw new Midas_Service_SendGrid_Exception('Could not send mail: '.$response->raw_body);
        }
    }
}
