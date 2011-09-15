<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

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

    $description = new Zend_Form_Element_Textarea('description');

    $keywords = new Zend_Form_Element_Textarea('keywords');

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

    $form->addElements(array($keywords, $description, $timezone, $environment, $lang, $name, $smartoptimizer, $submit));
    return $form;
    }
} // end class