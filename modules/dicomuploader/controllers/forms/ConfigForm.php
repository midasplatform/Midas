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

/** Configuration form for the dicom uploader */
class Dicomuploader_ConfigForm extends AppForm
{

  /** create createConfigform */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/dicomuploader/config/index')
         ->setMethod('post');

    $dcm2xml = new Zend_Form_Element_Text('dcm2xml');
    $storescp = new Zend_Form_Element_Text('storescp');
    $storescp_port = new Zend_Form_Element_Text('storescp_port');
    $storescp_study_timeout = new Zend_Form_Element_Text('storescp_study_timeout');
    $receptiondir = new Zend_Form_Element_Text('receptiondir');
    $receptiondir->setRequired(true)
           ->addValidator('NotEmpty', true);
    $pydas_dest_folder = new Zend_Form_Element_Text('pydas_dest_folder');
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($dcm2xml, $storescp, $storescp_port,
       $storescp_study_timeout, $receptiondir, $pydas_dest_folder,
       $submit));
    return $form;
    }

} // end class
?>