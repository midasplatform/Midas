<?php
/** Cleanup module configuration form */
class Cleanup_ConfigForm extends AppForm
{

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/cleanup/config/index')
         ->setMethod('post');

    $olderThan = new Zend_Form_Element_Text('olderThan');

    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit->setLabel('Save configuration');

    $form->addElements(array($olderThan, $submit));
    return $form;
    }

} // end class
?>
