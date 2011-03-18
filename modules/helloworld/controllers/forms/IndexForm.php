<?php
class Helloworld_IndexForm extends AppForm
{
  public function createIndexForm()
    {
   $form = new Zend_Form;

    $form->setAction("")
          ->setMethod('post');
 
    $name = new Zend_Form_Element_Text('name');
    $name
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setValue('Test');
    
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel('Submit');
     
    $form->addElements(array($name,$submit));
    return $form;
    }
} // end class
?>
