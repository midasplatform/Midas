<?php
/** Admin forms*/
class AdminForm extends AppForm
{
  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/admin/index')
          ->setMethod('post');
    
    $lang = new Zend_Form_Element_Select('lang');
    $lang ->addMultiOptions(array(
                    'en' => 'English',
                    'fr' => 'French' 
                        ));    
    
    $timezone = new Zend_Form_Element_Select('timezone');
    $timezone ->addMultiOptions(array(
                    'America/New_York' => 'America/New_York',
                    'Europe/Paris' => 'Europe/Paris' 
                        ));    
    
    $environment = new Zend_Form_Element_Select('environment');
    $environment ->addMultiOptions(array(
                    'production' => 'Production',
                    'development' => 'Development'                    
                        ));    
    
    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);
    
    $smartoptimizer = new Zend_Form_Element_Checkbox("smartoptimizer");  
    
    
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save configuration');
     
    $form->addElements(array($timezone, $environment, $lang, $name, $smartoptimizer, $submit));
    return $form;
    }
} // end class