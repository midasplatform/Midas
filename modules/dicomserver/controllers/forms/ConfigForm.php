<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** Configuration form for the dicom server */
class Dicomserver_ConfigForm extends AppForm
  {
  /** create createConfigform */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/dicomserver/config/index')
         ->setMethod('post');

    $dcm2xml = new Zend_Form_Element_Text('dcm2xml');
    $storescp = new Zend_Form_Element_Text('storescp');
    $storescp_port = new Zend_Form_Element_Text('storescp_port');
    $storescp_study_timeout = new Zend_Form_Element_Text('storescp_study_timeout');
    $receptiondir = new Zend_Form_Element_Text('receptiondir');
    $receptiondir->setRequired(true)
           ->addValidator('NotEmpty', true);
    $pydas_dest_folder = new Zend_Form_Element_Text('pydas_dest_folder');
    $dcmqrscp = new Zend_Form_Element_Text('dcmqrscp');
    $dcmqridx = new Zend_Form_Element_Text('dcmqridx');
    $dcmqrscp_port = new Zend_Form_Element_Text('dcmqrscp_port');
    $server_ae_title = new Zend_Form_Element_Text('server_ae_title');
    $peer_aes = new Zend_Form_Element_Textarea('peer_aes');
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($dcm2xml, $storescp, $storescp_port,
       $storescp_study_timeout, $receptiondir, $pydas_dest_folder,
       $dcmqrscp, $dcmqridx, $dcmqrscp_port, $server_ae_title, $peer_aes,
       $submit));
    return $form;
    }
  } // end class
