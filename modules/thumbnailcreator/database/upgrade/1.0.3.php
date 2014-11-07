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

/** Upgrade the thumbnailcreator module to version 1.0.3. */
class Thumbnailcreator_Upgrade_1_0_3 extends MIDASUpgrade
{
    /** @var string */
    public $moduleName = 'thumbnailcreator';

    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig('provider', 'phmagick', $this->moduleName);
        $settingModel->setConfig('format', 'jpg', $this->moduleName);

        $configPath = LOCAL_CONFIGS_PATH.DIRECTORY_SEPARATOR.$this->moduleName.'.local.ini';

        if (file_exists($configPath)) {
            $config = new Zend_Config_Ini($configPath, 'global');

            if ($config->get('imagemagick')) {
                $settingModel->setConfig('image_magick', $config->get('imagemagick'), $this->moduleName);
            }

            $settingModel->setConfig('use_thumbnailer', $config->get('useThumbnailer', 0), $this->moduleName);

            if ($config->get('thumbnailer')) {
                $settingModel->setConfig('thumbnailer', $config->get('thumbnailer'), $this->moduleName);
            }

            $config = new Zend_Config_Ini($configPath, null, true);
            unset($config->global->imageFormats);
            unset($config->global->imagemagick);
            unset($config->global->thumbnailer);
            unset($config->global->useThumbnailer);

            $writer = new Zend_Config_Writer_Ini();
            $writer->setConfig($config);
            $writer->setFilename($configPath);
            $writer->write();
        } else {
            $settingModel->setConfig('use_thumbnailer', 0, $this->moduleName);
        }
    }
}
