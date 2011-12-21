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

/** Assetstore forms*/
class AssetstoreForm extends AppForm
{
  /** Create assetstore form*/
  public function createAssetstoreForm($action = 'assetstore/add')
    {
    $form = new Zend_Form();
    $form->setAction($action);
    $form->setName('assetstoreForm');
    $form->setMethod('post');
    $form->setAttrib('class', 'assetstoreForm');

    // Name of the assetstore
    $inputDirectory = new Zend_Form_Element_Text('name', array('label' => $this->t('Give a name'),
                                                 'id' => 'assetstorename'));
    $inputDirectory->setRequired(true);
    $form->addElement($inputDirectory);


    // Input directory
    $basedirectory = new Zend_Form_Element_Text('basedirectory', array('label' => $this->t('Pick a base directory'),
                                                'id' => 'assetstoreinputdirectory'));
    $basedirectory->setRequired(true);
    $form->addElement($basedirectory);

    // Assetstore type
    $assetstoretypes = array('0' => $this->t('Managed by MIDAS'),
                             '1' => $this->t('Remotely linked'));
    // Amazon support is not yet implemented, don't present it as an option
    //                          '2' => $this->t('Amazon S3'));

    $assetstoretype = new Zend_Form_Element_Select('assetstoretype', array('id' => 'assetstoretype'));
    $assetstoretype->setLabel('Select a type')->setMultiOptions($assetstoretypes);
    // Add a loading image
    $assetstoretype->setDescription('<div class="assetstoreLoading" style="display:none"><img src="'.$this->webroot.'/core/public/images/icons/loading.gif"/></div>')
        ->setDecorators(array(
        'ViewHelper',
        array('Description', array('escape' => false, 'tag' => false)),
        array('HtmlTag', array('tag' => 'dd')),
        array('Label', array('tag' => 'dt')),
        'Errors',
      ));
    $form->addElement($assetstoretype);

    // Submit
    $addassetstore = new Zend_Form_Element_Submit('addassetstore', $this->t('Add this assetstore'));
    $addassetstore->setDescription($this->t('or').' <a href="#" onClick="return newAssetstoreHide()">'.$this->t('cancel').'</a>')
        ->setDecorators(array(
        'ViewHelper',
        array('Description', array('escape' => false, 'tag' => false)),
        array('HtmlTag', array('tag' => 'dd', 'class' => 'submit-element')),
        array('Label', array('tag' => 'dt')),
        'Errors',
      ));
    $form->addElement($addassetstore);

    return $form;
    }
} // end class
?>
