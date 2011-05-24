<?php
class Visualize_ConfigForm extends AppForm
{
 
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/api/config/index')
          ->setMethod('post'); 
    
    $methodprefix = new Zend_Form_Element_Text('methodprefix');
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($methodprefix, $submit));
    return $form;
    }
    
  /** create  form */
  public function createKeyForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/api/config/usertab')
          ->setMethod('post'); 
    
    $appplication_name = new Zend_Form_Element_Text('appplication_name');
    $expiration = new Zend_Form_Element_Text('expiration');
    
    $submit = new  Zend_Form_Element_Submit('createAPIKey');
    $submit ->setLabel($this->t('Generate Key'));
     
    $form->addElements(array($appplication_name, $expiration, $submit));
    return $form;
    }
} // end class
?>