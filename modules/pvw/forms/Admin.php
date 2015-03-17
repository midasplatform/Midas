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

/** Admin form for the pvw module. */
class Pvw_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('pvw_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('y9RNWEVLJUWDBhkrbLmGFmZj');
        $csrf->setDecorators(array('ViewHelper'));

        $pvPython = new Zend_Form_Element_Text(MIDAS_PVW_PVPYTHON_KEY);
        $pvPython->setLabel('pvpython Command');
        $pvPython->setRequired(true);
        $pvPython->addValidator('NotEmpty', true);

        $ports = new Zend_Form_Element_Text(MIDAS_PVW_PORTS_KEY);
        $ports->setLabel('Ports');
        $ports->setRequired(true);
        $ports->addValidator('NotEmpty', true);

        $displayEnv = new Zend_Form_Element_Text(MIDAS_PVW_DISPLAY_ENV_KEY);
        $displayEnv->setLabel('DISPLAY Environment Variable');
        $displayEnv->setRequired(true);
        $displayEnv->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($pvPython, $ports, $displayEnv), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $pvPython, $ports, $displayEnv, $submit));
    }
}
