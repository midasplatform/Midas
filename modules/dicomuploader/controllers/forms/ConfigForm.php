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

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/dicomuploader/config/index')
         ->setMethod('post');

    $dcm2xml = new Zend_Form_Element_Text('dcm2xml');
    $storescp = new Zend_Form_Element_Text('storescp');
    $tmpdir = new Zend_Form_Element_Text('tmpdir');
    $tmpdir->setRequired(true)
           ->addValidator('NotEmpty', true);
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($dcm2xml, $storescp, $tmpdir, $submit));
    return $form;
    }

} // end class
?>