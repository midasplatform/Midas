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

/** Admin form for the visualize module. */
class Visualize_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('visualize_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('E7am5Gj3nMLN5ELy2L2tQZ4e');
        $csrf->setDecorators(array('ViewHelper'));

        $temporaryDirectory = new Zend_Form_Element_Text(VISUALIZE_TEMPORARY_DIRECTORY_KEY);
        $temporaryDirectory->setLabel('Temp Directory');
        $temporaryDirectory->addValidator('NotEmpty', true);

        $useParaViewWeb = new Zend_Form_Element_Checkbox(VISUALIZE_USE_PARAVIEW_WEB_KEY);
        $useParaViewWeb->setLabel('Use ParaviewWeb Server');

        $useWebGl = new Zend_Form_Element_Checkbox(VISUALIZE_USE_WEB_GL_KEY);
        $useWebGl->setLabel('Use WebGL Viewer');

        $useSymlinks = new Zend_Form_Element_Checkbox(VISUALIZE_USE_SYMLINKS_KEY);
        $useSymlinks->setLabel('Use Symlinks Instead of Copying');

        $tomcatRootUrl = new Zend_Form_Element_Text(VISUALIZE_TOMCAT_ROOT_URL_KEY);
        $tomcatRootUrl->setLabel('Tomcat Root URL');
        $tomcatRootUrl->addValidator('NotEmpty', true);
        $tomcatRootUrl->addValidator('Callback', true, array('callback' => array('Zend_Uri', 'check')));

        $pvbatchCommand = new Zend_Form_Element_Text(VISUALIZE_PVBATCH_COMMAND_KEY);
        $pvbatchCommand->setLabel('pvbatch Command');
        $pvbatchCommand->addValidator('NotEmpty', true);

        $paraViewWebWorkDirectory = new Zend_Form_Element_Text(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY);
        $paraViewWebWorkDirectory->setLabel('ParaviewWeb Server Work Directory');
        $paraViewWebWorkDirectory->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($temporaryDirectory, $useParaViewWeb, $useWebGl, $useSymlinks, $tomcatRootUrl, $pvbatchCommand, $paraViewWebWorkDirectory), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $temporaryDirectory, $useParaViewWeb, $useWebGl, $useSymlinks, $tomcatRootUrl, $pvbatchCommand, $paraViewWebWorkDirectory, $submit));
    }
}
