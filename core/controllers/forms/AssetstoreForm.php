<?php
/** Assetstore forms*/
class AssetstoreForm extends AppForm
{
  /** Create assetstore form*/
  public function createAssetstoreForm($action='assetstore/add')
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
                             '1' => $this->t('Remotely linked'),
                             '2' => $this->t('Amazon S3'));
    
    $assetstoretype = new Zend_Form_Element_Select('type', array('id' => 'assetstoretype'));
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
