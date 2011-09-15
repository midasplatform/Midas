<?php
/** Forms */
class Oai_ConfigForm extends AppForm
{

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/oai/config/index')
          ->setMethod('post');

    $repositoryname = new Zend_Form_Element_Text('repositoryname');
    $adminemail = new Zend_Form_Element_Text('adminemail');
    $repositoryidentifier = new Zend_Form_Element_Text('repositoryidentifier');

    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');

    $form->addElements(array($repositoryidentifier, $adminemail, $repositoryname, $submit));
    return $form;
    }

} // end class