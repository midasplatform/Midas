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

/** Admin form for the batchmake module. */
class Batchmake_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('batchmake_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash(MIDAS_BATCHMAKE_CSRF_TOKEN);
        $csrf->setSalt('KZVfYdsDTzQh5T7eQDXmADfu');
        $csrf->setDecorators(array('ViewHelper'));

        $tmpDirectory = new Zend_Form_Element_Text(MIDAS_BATCHMAKE_TMP_DIR_PROPERTY);
        $tmpDirectory->setLabel('BatchMake Temporary Directory');
        $tmpDirectory->setRequired(true);
        $tmpDirectory->addValidator('NotEmpty', true);

        $binDirectory = new Zend_Form_Element_Text(MIDAS_BATCHMAKE_BIN_DIR_PROPERTY);
        $binDirectory->setLabel('BatchMake Binary Directory');
        $binDirectory->setRequired(true);
        $binDirectory->addValidator('NotEmpty', true);

        $scriptDirectory = new Zend_Form_Element_Text(MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY);
        $scriptDirectory->setLabel('BatchMake Script Directory');
        $scriptDirectory->setRequired(true);
        $scriptDirectory->addValidator('NotEmpty', true);

        $appDirectory = new Zend_Form_Element_Text(MIDAS_BATCHMAKE_APP_DIR_PROPERTY);
        $appDirectory->setLabel('BatchMake Application Directory');
        $appDirectory->setRequired(true);
        $appDirectory->addValidator('NotEmpty', true);

        $dataDirectory = new Zend_Form_Element_Text(MIDAS_BATCHMAKE_DATA_DIR_PROPERTY);
        $dataDirectory->setLabel('BatchMake Data Directory');
        $dataDirectory->setRequired(true);
        $dataDirectory->addValidator('NotEmpty', true);

        $condorBinDirectory = new Zend_Form_Element_Text(MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY);
        $condorBinDirectory->setLabel('Condor Binary Directory');
        $condorBinDirectory->setRequired(true);
        $condorBinDirectory->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($tmpDirectory, $binDirectory, $scriptDirectory, $appDirectory, $dataDirectory, $condorBinDirectory), 'global');

        $submit = new Zend_Form_Element_Submit(MIDAS_BATCHMAKE_SUBMIT_CONFIG);
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $tmpDirectory, $binDirectory, $scriptDirectory, $appDirectory, $dataDirectory, $condorBinDirectory, $submit));
    }
}
