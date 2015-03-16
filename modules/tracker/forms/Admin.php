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

/**
 * Admin form for the tracker module.
 *
 * @package Modules\Tracker\Database
 */
class Tracker_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('tracker_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('APSey8FJpXfDssQU5CqA9qe5');
        $csrf->setDecorators(array('ViewHelper'));

        $tempScalarTtl = new Zend_Form_Element_Text(MIDAS_TRACKER_TEMP_SCALAR_TTL_KEY);
        $tempScalarTtl->setLabel('Unofficial Scalar TTL');
        $tempScalarTtl->setRequired(true);
        $tempScalarTtl->addValidator('NotEmpty', true);
        $tempScalarTtl->addValidator('Digits', true);
        $tempScalarTtl->addValidator('GreaterThan', true, array('min' => 0));

        $this->addDisplayGroup(array($tempScalarTtl), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $tempScalarTtl, $submit));
    }
}
