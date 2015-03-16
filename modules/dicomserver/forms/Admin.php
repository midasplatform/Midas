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

/** Admin form for the dicomserver module. */
class Dicomserver_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('dicomserver_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('sAfDFebTDemATWR4gGrYGMSE');
        $csrf->setDecorators(array('ViewHelper'));

        $dcm2xmlCommand = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_DCM2XML_COMMAND_KEY);
        $dcm2xmlCommand->setLabel('dcm2xml Command');
        $dcm2xmlCommand->setRequired(true);
        $dcm2xmlCommand->addValidator('NotEmpty', true);

        $storescpCommand = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_STORESCP_COMMAND_KEY);
        $storescpCommand->setLabel('storescp Command');
        $storescpCommand->setRequired(true);
        $storescpCommand->addValidator('NotEmpty', true);

        $storescpPort = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_STORESCP_PORT_KEY);
        $storescpPort->setLabel('storescp Port');
        $storescpPort->setRequired(true);
        $storescpPort->addValidator('NotEmpty', true);
        $storescpPort->addValidator('Digits', true);
        $storescpPort->addValidator('Between', true, array('min' => 1, 'max' => 65535));
        $storescpPort->setAttrib('maxlength', 5);

        $storescpStudyTimeout = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_KEY);
        $storescpStudyTimeout->setLabel('storescp Study Timeout (in Seconds)');
        $storescpStudyTimeout->setRequired(true);
        $storescpStudyTimeout->addValidator('NotEmpty', true);
        $storescpStudyTimeout->addValidator('Digits', true);
        $storescpStudyTimeout->addValidator('GreaterThan', true, array('min' => 0));

        $receptionDirectory = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY);
        $receptionDirectory->setLabel('Reception Directory for DICOM Files');
        $receptionDirectory->setRequired(true);
        $receptionDirectory->addValidator('NotEmpty', true);

        $destinationFolder = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_DESTINATION_FOLDER_KEY);
        $destinationFolder->setLabel('Upload Destination Folder');
        $destinationFolder->setRequired(true);
        $destinationFolder->addValidator('NotEmpty', true);

        $dcmqrscpCommand = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_KEY);
        $dcmqrscpCommand->setLabel('dcmqrscp Command');
        $dcmqrscpCommand->setRequired(true);
        $dcmqrscpCommand->addValidator('NotEmpty', true);

        $dcmqrscpPort = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_DCMQRSCP_PORT_KEY);
        $dcmqrscpPort->setLabel('dcmqrscp Port');
        $dcmqrscpPort->setRequired(true);
        $dcmqrscpPort->addValidator('NotEmpty', true);
        $dcmqrscpPort->addValidator('Digits', true);
        $dcmqrscpPort->addValidator('Between', true, array('min' => 1, 'max' => 65535));
        $dcmqrscpPort->setAttrib('maxlength', 5);

        $dcmqridxCommand = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_KEY);
        $dcmqridxCommand->setLabel('dcmqridx Command');
        $dcmqridxCommand->setRequired(true);
        $dcmqridxCommand->addValidator('NotEmpty', true);

        $serverAeTitle = new Zend_Form_Element_Text(MIDAS_DICOMSERVER_SERVER_AE_TITLE_KEY);
        $serverAeTitle->setLabel('Server Application Entity Title');
        $serverAeTitle->setRequired(true);
        $serverAeTitle->addValidator('NotEmpty', true);

        $peerAes = new Zend_Form_Element_Textarea(MIDAS_DICOMSERVER_PEER_AES_KEY);
        $peerAes->setLabel('Peer Application Entities Allowed to Use Query/Retrieve Services');
        $peerAes->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($dcm2xmlCommand, $storescpCommand, $storescpPort, $storescpStudyTimeout, $receptionDirectory, $destinationFolder, $dcmqrscpCommand, $dcmqrscpPort, $dcmqridxCommand, $serverAeTitle, $peerAes), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $dcm2xmlCommand, $storescpCommand, $storescpPort, $storescpStudyTimeout, $receptionDirectory, $destinationFolder, $dcmqrscpCommand, $dcmqrscpPort, $dcmqridxCommand, $serverAeTitle, $peerAes, $submit));
    }
}
