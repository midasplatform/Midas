<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Admin form for the thumbnailcreator module. */
class Thumbnailcreator_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('thumbnailcreator_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('tKHLsBvnbLVYaWJNhrrafrBz');
        $csrf->setDecorators(array('ViewHelper'));

        $provider = new Zend_Form_Element_Select(MIDAS_THUMBNAILCREATOR_PROVIDER_KEY);
        $provider->setLabel('Provider');
        $provider->setRequired(true);
        $provider->addValidator('NotEmpty', true);
        $provider->addMultiOption(MIDAS_THUMBNAILCREATOR_PROVIDER_NONE, 'None');

        if (extension_loaded('gd')) {
            $provider->addMultiOption(MIDAS_THUMBNAILCREATOR_PROVIDER_GD, 'GD PHP Extension');
        }

        if (extension_loaded('imagick')) {
            $provider->addMultiOption(MIDAS_THUMBNAILCREATOR_PROVIDER_IMAGICK, 'ImageMagick PHP Extension');
        }

        $provider->addMultiOption(MIDAS_THUMBNAILCREATOR_PROVIDER_PHMAGICK, 'ImageMagick Program');

        $format = new Zend_Form_Element_Select(MIDAS_THUMBNAILCREATOR_FORMAT_KEY);
        $format->setLabel('Format');
        $format->setRequired(true);
        $format->addValidator('NotEmpty', true);
        $format->addMultiOptions(
            array(
                MIDAS_THUMBNAILCREATOR_FORMAT_GIF => 'GIF',
                MIDAS_THUMBNAILCREATOR_FORMAT_JPG => 'JPEG',
                MIDAS_THUMBNAILCREATOR_FORMAT_PNG => 'PNG',
            )
        );

        $this->addDisplayGroup(array($provider, $format), 'global');

        $imageMagick = new Zend_Form_Element_Text(MIDAS_THUMBNAILCREATOR_IMAGE_MAGICK_KEY);
        $imageMagick->setLabel('ImageMagick program location (path)');
        $imageMagick->addValidator('NotEmpty', true);

        $useThumbnailer = new Zend_Form_Element_Checkbox(MIDAS_THUMBNAILCREATOR_USE_THUMBNAILER_KEY);
        $useThumbnailer->setLabel('Have thumbnailer and want to use it to support more image formats');

        $thumbnailer = new Zend_Form_Element_Text(MIDAS_THUMBNAILCREATOR_THUMBNAILER_KEY);
        $thumbnailer->setLabel('Thumbnailer program location (path)');
        $thumbnailer->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($imageMagick, $useThumbnailer, $thumbnailer), 'phmagick');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $provider, $format, $imageMagick, $useThumbnailer, $thumbnailer, $submit));
    }
}
