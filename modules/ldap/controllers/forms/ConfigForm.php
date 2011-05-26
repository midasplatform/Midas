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