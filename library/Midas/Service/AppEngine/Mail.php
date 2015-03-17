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

/** Google App Engine mail service. */
class Midas_Service_AppEngine_Mail implements Midas_Service_Mail
{
    /** @var \google\appengine\api\mail\Message */
    protected $_client = null;

    /** @var array */
    protected $_config = array();

    /**
     * Constructor.
     *
     * @param array $config config
     */
    public function __construct(array $config = array())
    {
        $this->_config = $config;
    }

    /**
     * Return the client.
     *
     * @return \google\appengine\api\mail\Message client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Set the client.
     *
     * @param \google\appengine\api\mail\Message $client
     */
    public function setClient(\google\appengine\api\mail\Message $client)
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
     * Send the mail.
     *
     * @param Midas_Mail $mail mail instance
     * @throws Midas_Service_AppEngine_Exception
     */
    public function sendMail(Midas_Mail $mail)
    {
        if (is_null($this->_client)) {
            $this->_client = new \google\appengine\api\mail\Message();
        }

        $this->_client->addBcc($mail->getBcc());
        $this->_client->addCc($mail->getCc());
        $this->_client->addTo($mail->getTo());
        $this->_client->setHtmlBody($mail->getBodyHtml(true));
        $this->_client->setReplyTo($mail->getReplyTo());
        $this->_client->setSender($mail->getFrom());
        $this->_client->setSubject($mail->getSubject());
        $this->_client->setTextBody($mail->getBodyText(true));

        try {
            $this->_client->send();
        } catch (\Exception $exception) {
            throw new Midas_Service_AppEngine_Exception($exception);
        }
    }
}
