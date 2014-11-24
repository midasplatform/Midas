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

/** Admin form for the solr module. */
class Solr_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('solr_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('VhyLUG4sRzUwa43P96W7kVM8');
        $csrf->setDecorators(array('ViewHelper'));

        $host = new Zend_Form_Element_Text(SOLR_HOST_KEY);
        $host->setLabel('Solr Host');
        $host->setRequired(true);
        $host->addValidator('NotEmpty', true);
        $host->addValidator('Hostname', true);

        $port = new Zend_Form_Element_Text(SOLR_PORT_KEY);
        $port->setLabel('Solr Port');
        $port->setRequired(true);
        $port->addValidator('NotEmpty', true);
        $port->addValidator('Digits', true);
        $port->addValidator('Between', true, array('min' => 1, 'max' => 65535));
        $port->setAttrib('maxlength', 5);

        $webroot = new Zend_Form_Element_Text(SOLR_WEBROOT_KEY);
        $webroot->setLabel('Solr Webroot');
        $webroot->setRequired(true);
        $webroot->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($host, $port, $webroot), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $host, $port, $webroot, $submit));
    }
}
