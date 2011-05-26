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

/** Migrate form */
class MigrateForm extends AppForm
{
  /** Main form*/
  public function createMigrateForm($assetstores)
    {
    // Setup the form
    $form = new Zend_Form();
    $form->setAction('migratemidas2');
    $form->setName('migrateForm');
    $form->setMethod('post');
    $form->setAttrib('class', 'migrateForm');

    // Input directory
    $midas2_hostname = new Zend_Form_Element_Text('midas2_hostname', array('label' => $this->t('MIDAS2 Hostname'), 'size' => 60,'value' => 'localhost'));
    $midas2_hostname->setRequired(true);
    $form->addElement($midas2_hostname);
    
    $midas2_port = new Zend_Form_Element_Text('midas2_port', array('label' => $this->t('MIDAS2 Port'), 'size' => 4, 'value' => '5432'));
    $midas2_port->setRequired(true);
    $midas2_port->setValidators(array(new Zend_Validate_Digits()));
    $form->addElement($midas2_port);

    $midas2_user = new Zend_Form_Element_Text('midas2_user', array('label' => $this->t('MIDAS2 User'), 'size' => 60, 'value' => 'midas'));
    $midas2_user->setRequired(true);
    $form->addElement($midas2_user);
    
    $midas2_password = new Zend_Form_Element_Password('midas2_password', array('label' => $this->t('MIDAS2 Password'), 'size' => 60, 'value' => 'midas'));
    $midas2_password->setRequired(true);
    $form->addElement($midas2_password);
    
    $midas2_database = new Zend_Form_Element_Text('midas2_database', array('label' => $this->t('MIDAS2 Database'),' size' => 60, 'value' => 'midas'));
    $midas2_database->setRequired(true);
    $form->addElement($midas2_database);
    
    $midas2_assetstore = new Zend_Form_Element_Text('midas2_assetstore', array('label' => $this->t('MIDAS2 Assetstore Path'), 'size' => 60, 'value' => 'C:/xampp/midas/assetstore'));
    $midas2_assetstore->setRequired(true);
    $form->addElement($midas2_assetstore);
    
    // Assetstore
    $assetstoredisplay = array();
    $assetstoredisplay[0] = $this->t('Choose one...');
    
    // Initialize with the first type (MIDAS)
    foreach($assetstores as $assetstore)
      {
      if($assetstore->getType() == 0)
        {  
        $assetstoredisplay[$assetstore->getAssetstoreId()] = $assetstore->getName();
        }
      }

    $assetstore = new Zend_Form_Element_Select('assetstore');
    $assetstore->setLabel($this->t('MIDAS3 Assetstore'));
    $assetstore->setMultiOptions($assetstoredisplay);
    $assetstore->setDescription(' <a class="load-newassetstore" href="#newassetstore-form" rel="#newassetstore-form" title="'.$this->t('Add a new assetstore').'"> '.$this->t('Add a new assetstore').'</a>')
        ->setDecorators(array(
        'ViewHelper',
        array('Description', array('escape' => false, 'tag' => false)),
        array('HtmlTag', array('tag' => 'dd')),
        array('Label', array('tag' => 'dt')),
        'Errors',
      ));
    $assetstore->setRequired(true);
    $assetstore->setValidators(array(new Zend_Validate_GreaterThan(array('min' => 0))));
    $assetstore->setRegisterInArrayValidator(false); // This array is dynamic so we disable the validator
    $form->addElement($assetstore);
           
    // Submit
    $submit = new Zend_Form_Element_Button('migratesubmit', $this->t('Migrate'));
    $form->addElement($submit);

    return $form;
    }
} // end class
?>
