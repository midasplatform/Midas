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

class Ldap_ConfigForm extends AppForm
{
 
     /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/ldap/config/index')
          ->setMethod('post'); 
    
    $hostname = new Zend_Form_Element_Text('hostname');
    $hostname ->setRequired(true)
          ->addValidator('NotEmpty', true);
    $basedn = new Zend_Form_Element_Text('basedn');
    $basedn ->setRequired(true)
          ->addValidator('NotEmpty', true);
    $protocolVersion = new Zend_Form_Element_Text('protocolVersion');
    $protocolVersion ->setRequired(true)
          ->addValidator('NotEmpty', true);
    $search = new Zend_Form_Element_Text('search');
    $search ->setRequired(true)
          ->addValidator('NotEmpty', true);
    $proxyBasedn = new Zend_Form_Element_Text('proxyBasedn');
    $backup = new Zend_Form_Element_Text('backup');
    $bindn = new Zend_Form_Element_Text('bindn');
    $bindpw = new Zend_Form_Element_Password('bindpw');
    $proxyPassword = new Zend_Form_Element_Password('proxyPassword');
    
    $autoAddUnknownUser = new Zend_Form_Element_Select('autoAddUnknownUser');
    $autoAddUnknownUser ->addMultiOptions(array(
                    'true' => 'true',
                    'false' => 'false' 
                        ));   
    $useActiveDirectory = new Zend_Form_Element_Select('useActiveDirectory');
    $useActiveDirectory ->addMultiOptions(array(
                    'true' => 'true',
                    'false' => 'false' 
                        ));   
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($backup,$bindpw,$bindn,$proxyPassword,$hostname,$basedn,$protocolVersion,$search,$proxyBasedn,$autoAddUnknownUser,$useActiveDirectory,$submit));
    return $form;
    }
} // end class
?>