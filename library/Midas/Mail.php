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

/** Extend Zend Mail for compatibility with mail services. */
class Midas_Mail extends Zend_Mail
{
    /** @var array */
    protected $_bcc = array();

    /** @var array */
    protected $_cc = array();

    /** @var false|string */
    protected $_unencodedBodyText = false;

    /** @var false|string */
    protected $_unencodedBodyHtml = false;

    /**
     * Add one or more "BCC" email addresses.
     *
     * @param array|string $emails "BCC" email address or addresses
     * @return $this this mail instance
     */
    public function addBcc($emails)
    {
        parent::addBcc($emails);

        if (!is_array($emails)) {
            $emails = array($emails);
        }

        foreach ($emails as $email) {
            $_bcc[] = $email;
        }

        return $this;
    }

    /**
     * Return the "BCC" email addresses.
     *
     * @return array "BCC" email addresses
     */
    public function getBcc()
    {
        return $this->_bcc;
    }

    /**
     * Add one or more "CC" email addresses.
     *
     * @param array|string $emails "CC" email address or addresses
     * @param string $name provided for compatibility with Zend Mail
     * @return $this this mail instance
     */
    public function addCc($emails, $name = '')
    {
        parent::addCc($emails, $name);

        if (!is_array($emails)) {
            $emails = array($emails);
        }

        foreach ($emails as $email) {
            $_cc[] = $email;
        }

        return $this;
    }

    /**
     * Return the "CC" email addresses.
     *
     * @return array "CC" email addresses
     */
    public function getCc()
    {
        return $this->_cc;
    }

    /**
     * Return the "To" email addresses.
     *
     * @return array "To" email addresses
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * Return the unencoded HTML message body.
     *
     * @return string unencoded HTML message body
     */
    public function getUnencodedBodyHtml()
    {
        return $this->_unencodedBodyHtml;
    }

    /**
     * Return the unencoded plain text message body.
     *
     * @return string unencoded plain text message body
     */
    public function getUnencodedBodyText()
    {
        return $this->_unencodedBodyText;
    }

    /**
     * Set the HTML message body.
     *
     * @param string $html HTML message body
     * @param string $charset message character set
     * @param string $encoding message encoding
     * @return $this this mail instance
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        parent::setBodyHtml($html, $charset, $encoding);

        $this->_unencodedBodyHtml = $html;

        return $this;
    }

    /**
     * Set the plain text message body.
     *
     * @param string $text plain text message body
     * @param string $charset charset
     * @param string $encoding encoding
     * @return $this this mail instance
     */
    public function setBodyText($text, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        parent::setBodyText($text, $charset, $encoding);

        $this->_unencodedBodyText = $text;

        return $this;
    }
}
