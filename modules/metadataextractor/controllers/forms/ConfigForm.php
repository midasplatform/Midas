<?php
class Metadataextractor_ConfigForm extends AppForm
{
 
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/metadataextractor/config/index')
          ->setMethod('post'); 
    
    $hachoir = new Zend_Form_Element_Text('hachoir');
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($hachoir, $submit));
    return $form;
    }
   
} // end class
?>