<?php
class Scheduler_ConfigForm extends AppForm
{
 
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/scheduler/config/index')
          ->setMethod('post'); 
    
    $dot = new Zend_Form_Element_Text('dot');
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($dot, $submit));
    return $form;
    }
   
} // end class
?>