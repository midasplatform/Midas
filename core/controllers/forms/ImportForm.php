<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/** Import forms*/
class ImportForm extends AppForm
{
  /** Main form*/
  public function createImportForm($assetstores)
    {
    // Setup the form
    $form = new Zend_Form();
    $form->setAction('import/import');
    $form->setName('importForm');
    $form->setMethod('post');
    $form->setAttrib('class', 'importForm');

    // Hidden upload id
    srand(time());

    $uploadId = new Zend_Form_Element_Hidden('uploadid', array('value' => rand()));
    $uploadId->setDecorators(array(
        'ViewHelper',
        array('HtmlTag', array('style' => 'display:none')),
        array('Label', array('style' => 'display:none')),
      ));
    $form->addElement($uploadId);

    // Input directory
    $inputDirectory = new Zend_Form_Element_Text('inputdirectory', array('label' => $this->t('Directory on the server'),
                                                 'id' => 'inputdirectory', 'size' => 60));
    $inputDirectory->setRequired(true);
    $form->addElement($inputDirectory);

    // Button to select the directory on the server
    $inputDirectoryButton = new Zend_Form_Element_Button('inputdirectorybutton', $this->t('Choose'));
    $inputDirectoryButton->setDecorators(array(
        'ViewHelper',
        array('HtmlTag', array('tag' => 'div', 'class' => 'browse-button')),
        array('Label', array('tag' => 'div', 'style' => 'display:none'))
      ));
    $form->addElement($inputDirectoryButton);

    // Select the assetstore type
    $assetstoretypes = array();
    $assetstoretypes[MIDAS_ASSETSTORE_LOCAL] = $this->t('Copy data on this server'); // manage by MIDAS
    $assetstoretypes[MIDAS_ASSETSTORE_REMOTE] = $this->t('Link data from its current location'); // link the data
    // Amazon support is not yet implemented, don't present it as an option
    //$assetstoretypes[MIDAS_ASSETSTORE_AMAZON] = $this->t('Copy data to the cloud'); // Amazon support

    $assetstoretype = new Zend_Form_Element_Select('importassetstoretype');
    $assetstoretype->setLabel($this->t('Storage type'));
    $assetstoretype->setMultiOptions($assetstoretypes);
    $assetstoretype->setAttrib('onChange', 'assetstoretypeChanged()');
    $form->addElement($assetstoretype);

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
    $assetstore->setLabel($this->t('Assetstore'));
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

    // Import empty directories
    $emptydirs = new Zend_Form_Element_Checkbox('importemptydirectories');
    $emptydirs->setLabel($this->t('Import empty directories'))->setChecked(true);
    $form->addElement($emptydirs);

    // Where to import the data
    $importFolder = new Zend_Form_Element_Text('importFolder', array('label' => $this->t('Folder Id'),
                                               'id' => 'importFolder', 'size' => 3, 'value' => 1));
    $importFolder->setRequired(true);
    $form->addElement($importFolder);


    // Hidden filed to pass the translation of the stop import
    $stopimport = new Zend_Form_Element_Hidden('importstop', array('value' => $this->t('Stop import')));
    $stopimport->setDecorators(array(
        'ViewHelper',
        array('HtmlTag', array()),
        array('Label', array()),
        'Errors',
      ));
    $form->addElement($stopimport);

    // Submit
    $submit = new Zend_Form_Element_Button('importsubmit', $this->t('Import data'));
    $form->addElement($submit);



    return $form;
    }
} // end class
?>
