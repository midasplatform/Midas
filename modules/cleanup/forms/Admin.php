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

/** Admin form for the cleanup module. */
class Cleanup_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('cleanup_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('BM8CwGYZBs8VqCUvHKsZ85g5');
        $csrf->setDecorators(array('ViewHelper'));

        $daysToKeepPartialFiles = new Zend_Form_Element_Text(CLEANUP_DAYS_TO_KEEP_PARTIAL_FILES_KEY);
        $daysToKeepPartialFiles->setLabel('Days to Keep Partial Files');
        $daysToKeepPartialFiles->setRequired(true);
        $daysToKeepPartialFiles->addValidator('NotEmpty', true);
        $daysToKeepPartialFiles->addValidator('Digits', true);
        $daysToKeepPartialFiles->addValidator('GreaterThan', true, array('min' => 1));

        $this->addDisplayGroup(array($daysToKeepPartialFiles), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $daysToKeepPartialFiles, $submit));
    }
}
