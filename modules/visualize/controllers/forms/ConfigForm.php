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

class Visualize_ConfigForm extends AppForm
{
 
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/visualize/config/index')
          ->setMethod('post'); 

    $useparaview = new Zend_Form_Element_Checkbox("useparaview");  
    $userwebgl = new Zend_Form_Element_Checkbox("userwebgl");  
    $usesymlinks = new Zend_Form_Element_Checkbox("usesymlinks");  
    $paraviewworkdir = new Zend_Form_Element_Text("paraviewworkdir");  
    $customtmp = new Zend_Form_Element_Text("customtmp");  
    $PWApp = new Zend_Form_Element_Text("pwapp");  
    $pvbatch = new Zend_Form_Element_Text("pvbatch");  
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($pvbatch, $PWApp, $usesymlinks, $userwebgl, $paraviewworkdir, $customtmp, $useparaview, $submit));
    return $form;
    }
    
} // end class
?>