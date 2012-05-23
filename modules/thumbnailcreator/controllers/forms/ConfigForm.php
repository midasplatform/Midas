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
/** Thumbnailcreator_ConfigForm class*/
class Thumbnailcreator_ConfigForm extends AppForm
{

  /** create  form */
  public function createConfigForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/thumbnailcreator/config/index')
          ->setMethod('post');

    $imagemagick = new Zend_Form_Element_Text('imagemagick');
    $submit = new  Zend_Form_Element_Submit('submitConfig');
    $submit ->setLabel('Save image magick configuration');

    $form->addElements(array($imagemagick, $submit));
    return $form;
    }

  /** create  form */
  public function createThumbnailerForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/thumbnailcreator/config/index')
          ->setMethod('post');

    $useThumbnailer = new Zend_Form_Element_Radio('useThumbnailer');
    $useThumbnailer->addMultiOptions(array(
                 MIDAS_THUMBNAILCREATOR_NOT_USE_THUMBNAILER => $this->t("Do not have thumbnailer or do not want to use it."),
                 MIDAS_THUMBNAILCREATOR_USE_THUMBNAILER => $this->t("Have thumbnailer and want to use it to support more image formats."),
                  ))
            ->setRequired(true)
            ->setValue(MIDAS_THUMBNAILCREATOR_NOT_USE_THUMBNAILER);
    $thumbnailer = new Zend_Form_Element_Text('thumbnailer');
    $submit = new  Zend_Form_Element_Submit('submitThumbnailer');
    $submit ->setLabel('Save thumbnailer configuration');

    $form->addElements(array($useThumbnailer, $thumbnailer, $submit));
    return $form;
    }

} // end class
?>
