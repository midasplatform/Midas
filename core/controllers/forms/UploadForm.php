<?php
/** Upload forms*/
class UploadForm extends AppForm
{
  /** create upload link form */
  public function createUploadLinkForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/upload/savelink')
          ->setMethod('post');

    $validator = new Zend_Validate_Callback(array('Zend_Uri', 'check'));
    
    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true);
    $url = new Zend_Form_Element_Text('url');
    $url  ->setValue('http://')
          ->setRequired(true)
          ->addValidator($validator)
          ->addValidator('NotEmpty', true);

    
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save Link"))
            ->setAttrib('class', 'globalButton');
     
    $form->addElements(array($name, $url, $submit));
    return $form;
    }
   
} // end class