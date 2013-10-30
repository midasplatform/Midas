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

/** Configuration form for the dicom extractor */
class Dicomextractor_ConfigForm extends AppForm
{

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/dicomextractor/config/index')
         ->setMethod('post');

    $dcm2xml = new Zend_Form_Element_Text('dcm2xml');
    $dcmj2pnm = new Zend_Form_Element_Text('dcmj2pnm');
    $dcmftest = new Zend_Form_Element_Text('dcmftest');
    $dcmdictpath = new Zend_Form_Element_Text('dcmdictpath');

    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($dcm2xml, $dcmj2pnm, $dcmftest, $dcmdictpath,
      $submit));
    return $form;
    }

} // end class
