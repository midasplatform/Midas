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
