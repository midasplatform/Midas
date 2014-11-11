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

/** Admin form for the thumbnailcreator module. */
class Thumbnailcreator_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('thumbnailcreator_config');
        $this->setMethod('POST');

        $provider = new Zend_Form_Element_Select('provider');
        $provider->setLabel('Provider');
        $provider->addMultiOption('none', 'None');
        $provider->setRequired(true);
        $provider->addValidator('NotEmpty', true);

        if (extension_loaded('gd')) {
            $provider->addMultiOption('gd', 'GD PHP Extension');
        }

        if (extension_loaded('imagick')) {
            $provider->addMultiOption('imagick', 'ImageMagick PHP Extension');
        }

        $isAppEngine = class_exists('\google\appengine\api\app_identity\AppIdentityService', false);

        if (!$isAppEngine) {
            $provider->addMultiOption('phmagick', 'ImageMagick Program');
        }

        $format = new Zend_Form_Element_Select('format');
        $format->setLabel('Format');
        $format->addMultiOption('gif', 'GIF');
        $format->addMultiOption('jpg', 'JPEG');
        $format->addMultiOption('png', 'PNG');
        $format->setRequired(true);
        $format->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($provider, $format), 'global');

        $imageMagick = new Zend_Form_Element_Text('image_magick');
        $imageMagick->setLabel('ImageMagick program location (path)');
        $imageMagick->addValidator('NotEmpty', true);

        $useThumbnailer = new Zend_Form_Element_Checkbox('use_thumbnailer');
        $useThumbnailer->setLabel('Have thumbnailer and want to use it to support more image formats');

        $thumbnailer = new Zend_Form_Element_Text('thumbnailer');
        $thumbnailer->setLabel('Thumbnailer program location (path)');
        $thumbnailer->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($imageMagick, $useThumbnailer, $thumbnailer), 'phmagick');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        if ($isAppEngine) {
            $this->addElements(array($provider, $format, $submit));
        } else {
            $this->addElements(array($provider, $format, $imageMagick, $useThumbnailer, $thumbnailer, $submit));
        }
    }
}
