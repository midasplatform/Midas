<?php
class UserForm extends AppForm
{
 /** create login form */
  public function createLoginForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/user/login')
          ->setMethod('post')
          ->setAttrib('id', 'loginForm');

    $email = new Zend_Form_Element_Text('email');
    $email
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator('EmailAddress');

    $password = new Zend_Form_Element_Password('password');
    $password
             ->addValidator('NotEmpty', true)
             ->setRequired(true);    
    $rememberMe = new Zend_Form_Element_Checkbox('remerberMe');
   
    if(isset($this->uri))
      {
      $url= new Zend_Form_Element_Hidden("url");
      $form->addElement($url);
      }
    
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Login"))
            ->setAttrib('class', 'globalButton');
     
    $form->addElements(array($email,$password,$rememberMe,$submit));
    return $form;
    }
    
     /** register  form */
  public function createRegisterForm()
    {
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/user/register')
          ->setMethod('post')
          ->setAttrib('id', 'registerForm')
          ->setAttrib('class', 'genericForm');


    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator('EmailAddress');
          
    
    $firstname = new Zend_Form_Element_Text('firstname');
    $firstname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator(new Zend_Validate_Alnum());
    
    $lastname = new Zend_Form_Element_Text('lastname');
    $lastname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator(new Zend_Validate_Alnum());

    $password1 = new Zend_Form_Element_Password('password1');
    $password1
             ->addValidator('NotEmpty', true)
             ->setRequired(true);
    
    $password2 = new Zend_Form_Element_Password('password2');
    $password2
             ->addValidator('NotEmpty', true)
             ->setRequired(true);
           
    $condiftions = new Zend_Form_Element_Checkbox('conditions');

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Register"));
    
    $form->addElements(array($email,$firstname,$lastname,$password1,$password2,$condiftions,$submit));

    return $form;
    }
        
    
      /** acount  form */
  public function createAccountForm($firstname_value=null,$lastname_value=null,$company_value=null,$policy_value=null)
    {
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/user/settings')
          ->setMethod('post');
              
    $firstname = new Zend_Form_Element_Text('firstname');
    $firstname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator(new Zend_Validate_Alnum());
    
    $lastname = new Zend_Form_Element_Text('lastname');
    $lastname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator(new Zend_Validate_Alnum());
    
    $company = new Zend_Form_Element_Text('company');
    $company
          ->addValidator(new Zend_Validate_Alnum());

    $submit = new  Zend_Form_Element_Submit('modifyAccount');
    $submit ->setLabel($this->t("Modify"));
    
    $privacy = new Zend_Form_Element_Radio('privacy');
    $privacy->addMultiOptions( array(
                 MIDAS_USER_PUBLIC => $this->t("Public (Everyone can access to my information)"),
                 MIDAS_USER_PRIVATE => $this->t("Private (Nobody can access to my page)"),
                  ))
          ->setRequired(true)
          ->setValue(MIDAS_COMMUNITY_PUBLIC);
    
    if($firstname_value!=null)
      {
      $firstname->setValue($firstname_value);
      }
    if($lastname_value!=null)
      {
      $lastname->setValue($lastname_value);
      }
    if($company_value!=null)
      {
      $company->setValue($company_value);
      }
    if($policy_value!=null)
      {
      $privacy->setValue($policy_value);
      }
    
    $form->addElements(array($firstname,$lastname,$company,$privacy,$submit));

    return $form;
    }
} // end class
?>