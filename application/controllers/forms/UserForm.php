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
} // end class
?>