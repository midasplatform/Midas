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

/** Configuration form for the dicom server */
class Dicomserver_ConfigForm extends AppForm
{
    /** create createConfigform */
    public function createConfigForm()
    {
        $form = new Zend_Form();

        $form->setAction($this->webroot.'/dicomserver/config/index')->setMethod('post');

        $dcm2xml = new Zend_Form_Element_Text('dcm2xml');
        $storescp = new Zend_Form_Element_Text('storescp');
        $storescp_port = new Zend_Form_Element_Text('storescp_port');
        $storescp_study_timeout = new Zend_Form_Element_Text('storescp_study_timeout');
        $receptiondir = new Zend_Form_Element_Text('receptiondir');
        $receptiondir->setRequired(true)->addValidator('NotEmpty', true);
        $pydas_dest_folder = new Zend_Form_Element_Text('pydas_dest_folder');
        $dcmqrscp = new Zend_Form_Element_Text('dcmqrscp');
        $dcmqridx = new Zend_Form_Element_Text('dcmqridx');
        $dcmqrscp_port = new Zend_Form_Element_Text('dcmqrscp_port');
        $server_ae_title = new Zend_Form_Element_Text('server_ae_title');
        $peer_aes = new Zend_Form_Element_Textarea('peer_aes');
        $submit = new  Zend_Form_Element_Submit('submitConfig');
        $submit->setLabel('Save configuration');

        $form->addElements(
            array(
                $dcm2xml,
                $storescp,
                $storescp_port,
                $storescp_study_timeout,
                $receptiondir,
                $pydas_dest_folder,
                $dcmqrscp,
                $dcmqridx,
                $dcmqrscp_port,
                $server_ae_title,
                $peer_aes,
                $submit,
            )
        );

        return $form;
    }
}
