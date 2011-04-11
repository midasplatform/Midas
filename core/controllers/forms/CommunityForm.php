<?php
class CommunityForm extends AppForm
{
 /** create create community form */
  public function createCreateForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/community/create')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $description = new Zend_Form_Element_Textarea('description');
    
    $privacy = new Zend_Form_Element_Radio('privacy');
    $privacy->addMultiOptions( array(
                 MIDAS_COMMUNITY_PUBLIC => $this->t("Public"),
                 MIDAS_COMMUNITY_PRIVATE => $this->t("Private"),
                 MIDAS_COMMUNITY_SEMIPRIVATE => $this->t("Semi-private")
                  ))
            ->setRequired(true)
            ->setValue(MIDAS_COMMUNITY_PUBLIC);
   
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Create"));
    
    $form->addElements(array($name,$description,$privacy,$submit));
    return $form;
    }
  
  /** create a group*/
  public function createCreateGroupForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/community/manage')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);
   
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save"));
    
    $form->addElements(array($name,$submit));
    return $form;
    }
 
} // end class
?>
