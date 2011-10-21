<?php
class Remoteprocessing_ConfigForm extends AppForm
{

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/remoteprocessing/config/index')
          ->setMethod('post');

    $securitykey = new Zend_Form_Element_Text('securitykey');

    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($securitykey, $submit));
    return $form;
    }

} // end class
?>