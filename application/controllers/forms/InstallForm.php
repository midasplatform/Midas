<?php
class InstallForm extends AppForm
{
 /** create  form */
  public function createDBForm($type)
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/install/step2')
          ->setMethod('post');
    $type= new Zend_Form_Element_Hidden('type');
    
    $host = new Zend_Form_Element_Text('host');
    $host
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setValue('localhost');
    
    $username = new Zend_Form_Element_Text('username');
    $username
          ->setRequired(true)
          ->addValidator('NotEmpty', true);
    
    $password = new Zend_Form_Element_Password('password');
    
    $dbname = new Zend_Form_Element_Text('dbname');
    $dbname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setValue('midas');
    

    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator('EmailAddress');
    
    $userpassword1 = new Zend_Form_Element_Password('userpassword1');
    $userpassword1
             ->addValidator('NotEmpty', true)
             ->setRequired(true);
    
    $userpassword2 = new Zend_Form_Element_Password('userpassword2');
    $userpassword2
             ->addValidator('NotEmpty', true)
             ->setRequired(true);
    
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
    
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel('Set up database');
     
    $form->addElements(array($type,$host,$username,$password,$dbname,$submit,$lastname,$firstname,$userpassword2,$userpassword1,$email));
    return $form;
    } //end createDBForm
    
     /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/install/step3')
          ->setMethod('post');
    
    $lang = new Zend_Form_Element_Select('lang');
    $lang ->addMultiOptions(array(
                    'en' => 'English',
                    'fr' => 'French' 
                        ));    
    
    $process = new Zend_Form_Element_Select('process');
    $process ->addMultiOptions(array(
                    'onthefly' => 'On the fly',
                    'cron' => 'External' 
                        ));    
    
    $timezone = new Zend_Form_Element_Select('timezone');
    $timezone ->addMultiOptions(array(
                    'America/New_York' => 'America/New_York',
                    'Europe/Paris' => 'Europe/Paris' 
                        ));    
    
    $environment = new Zend_Form_Element_Select('environment');
    $environment ->addMultiOptions(array(
                    'production' => 'Production' ,
                    'development' => 'Development'                    
                        ));    
    
    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);
    
    $smartoptimizer = new Zend_Form_Element_Checkbox("smartoptimizer");  
    
    $assetstore = new Zend_Form_Element_Select('assetstore');
    
    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel('Create configuration');
     
    $form->addElements(array($process,$timezone,$assetstore,$environment,$lang,$name,$smartoptimizer,$submit));
    return $form;
    }
} // end class
?>