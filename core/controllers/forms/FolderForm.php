<?php
class FolderForm extends AppForm
{
  /** create edit folder form */
  public function createEditForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/folder/edit')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $description = new Zend_Form_Element_Textarea('description');
    $teaser = new Zend_Form_Element_Text('teaser');
    $teaser->setAttrib('MAXLENGTH', '250');
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save"));
    
    $form->addElements(array($name,$description,$submit,$teaser));
    return $form;
    }
  
} // end class
?>
