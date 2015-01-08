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

/** Mail service transport. */
class Midas_Mail_Transport_Service extends Zend_Mail_Transport_Abstract
{
    /** @var Midas_Mail */
    protected $_mail = null;

    /** @var Midas_Service_Mail */
    protected $_service = null;

    /**
     * Constructor.
     *
     * @param Midas_Service_Mail $service
     */
    public function __construct(Midas_Service_Mail $service)
    {
        $this->$_service = $service;
    }

    /**
     * Return the service.
     *
     * @return Midas_Service_Mail
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     * Set the service.
     *
     * @param Midas_Service_Mail $service
     */
    public function setService(Midas_Service_Mail $service)
    {
        $this->_service = $service;
    }

    /**
     * Send the email.
     *
     * @throws Zend_Mail_Transport_Exception
     */
    protected function _sendMail()
    {
        if (!$this->_mail instanceof Midas_Mail) {
            throw new Zend_Mail_Transport_Exception('An instance of Midas_Mail is required.');
        }

        $this->_service->sendMail($this->_mail);
    }
}
